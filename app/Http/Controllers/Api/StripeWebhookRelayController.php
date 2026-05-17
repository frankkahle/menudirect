<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderNotificationsJob;
use App\Models\FoodOrder;
use App\Models\RestaurantSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Receives Stripe webhook events relayed from the Manager server.
 *
 * The Manager verifies Stripe's signature and forwards the parsed event to us
 * via a shared secret. We update RestaurantSite state accordingly.
 *
 * Auth: Bearer token (PORTAL_WEBHOOK_RELAY_TOKEN env)
 */
class StripeWebhookRelayController extends Controller
{
    /**
     * Verify the relay Bearer token.
     */
    protected function verifyRelayToken(Request $request): bool
    {
        $expected = config('manager.webhook_relay_token');
        if (!$expected) return false;

        return hash_equals($expected, (string) $request->bearerToken());
    }

    /**
     * Handle account.updated — restaurant completed/changed onboarding, capabilities, requirements.
     *
     * POST /api/webhooks/stripe-account-updated
     * {
     *   "account_id": "acct_...",
     *   "charges_enabled": true,
     *   "payouts_enabled": true,
     *   "details_submitted": true,
     *   "requirements": {...}
     * }
     */
    public function accountUpdated(Request $request): JsonResponse
    {
        if (!$this->verifyRelayToken($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'account_id' => 'required|string',
            'charges_enabled' => 'required|boolean',
            'payouts_enabled' => 'required|boolean',
            'details_submitted' => 'required|boolean',
            'requirements' => 'nullable|array',
        ]);

        $site = RestaurantSite::where('stripe_account_id', $data['account_id'])->first();

        if (!$site) {
            Log::warning("Stripe account.updated relayed for unknown account: {$data['account_id']}");
            return response()->json(['error' => 'Account not found in Portal'], 404);
        }

        $updates = [
            'stripe_charges_enabled' => $data['charges_enabled'],
            'stripe_payouts_enabled' => $data['payouts_enabled'],
        ];

        // Update account status flag
        if ($data['charges_enabled']) {
            $updates['stripe_account_status'] = 'active';
            if (!$site->stripe_onboarded_at) {
                $updates['stripe_onboarded_at'] = now();
            }
        } elseif ($data['details_submitted']) {
            $updates['stripe_account_status'] = 'pending_review';
        } else {
            $updates['stripe_account_status'] = 'onboarding';
        }

        $site->update($updates);

        Log::info("Stripe Connect account updated for site {$site->id}", [
            'account_id' => $data['account_id'],
            'charges_enabled' => $data['charges_enabled'],
            'payouts_enabled' => $data['payouts_enabled'],
            'status' => $updates['stripe_account_status'],
        ]);

        return response()->json(['updated' => true, 'site_id' => $site->id]);
    }

    /**
     * Handle payment_intent.succeeded — customer successfully paid for an order.
     *
     * POST /api/webhooks/stripe-payment-succeeded
     * {
     *   "payment_intent_id": "pi_...",
     *   "account_id": "acct_...",
     *   "amount_received": 2500,
     *   "application_fee_amount": 25,
     *   "metadata": { "order_id": "123", "order_number": "ORD-..." }
     * }
     */
    public function paymentIntentSucceeded(Request $request): JsonResponse
    {
        if (!$this->verifyRelayToken($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'payment_intent_id' => 'required|string',
            'account_id' => 'nullable|string',
            'amount_received' => 'nullable|integer',
            'application_fee_amount' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        // Lock the order row to prevent race between parallel webhook deliveries.
        // Stripe occasionally retries webhooks; the lock + status check make this idempotent.
        $result = DB::transaction(function () use ($data) {
            $query = FoodOrder::where('payment_intent_id', $data['payment_intent_id']);
            $order = $query->lockForUpdate()->first();

            if (!$order && !empty($data['metadata']['order_id'])) {
                $order = FoodOrder::whereKey($data['metadata']['order_id'])->lockForUpdate()->first();
            }

            if (!$order) return ['not_found' => true];

            if ($order->payment_status === 'paid') {
                return ['already_paid' => true, 'order' => $order];
            }

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'platform_fee_cents' => $data['application_fee_amount'] ?? $order->platform_fee_cents,
            ]);

            // Auto-confirm if enabled for this restaurant
            $settings = $order->restaurantSite->getOrderingSettings();
            if (!empty($settings['auto_confirm']) && $order->status === FoodOrder::STATUS_PENDING) {
                $prepTime = (int) ($settings['estimated_prep_time_minutes'] ?? 30);
                $order->confirm($prepTime);
            }

            return ['updated' => true, 'order' => $order];
        });

        if (!empty($result['not_found'])) {
            Log::warning("Stripe payment_intent.succeeded for unknown order", [
                'payment_intent_id' => $data['payment_intent_id'],
                'metadata' => $data['metadata'] ?? null,
            ]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order = $result['order'];

        if (!empty($result['already_paid'])) {
            return response()->json(['updated' => false, 'reason' => 'already_paid', 'order_id' => $order->id]);
        }

        // Dispatch notifications outside the transaction so the job doesn't race the commit
        SendOrderNotificationsJob::dispatch($order);

        Log::info("Stripe payment confirmed for order {$order->id}", [
            'payment_intent_id' => $data['payment_intent_id'],
            'order_number' => $order->order_number,
            'amount' => $data['amount_received'] ?? null,
            'platform_fee' => $data['application_fee_amount'] ?? null,
        ]);

        return response()->json([
            'updated' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    /**
     * Handle payment_intent.payment_failed — card declined or customer abandoned.
     *
     * POST /api/webhooks/stripe-payment-failed
     */
    public function paymentIntentFailed(Request $request): JsonResponse
    {
        if (!$this->verifyRelayToken($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'payment_intent_id' => 'required|string',
            'failure_message' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $order = FoodOrder::where('payment_intent_id', $data['payment_intent_id'])->first();

        if (!$order && !empty($data['metadata']['order_id'])) {
            $order = FoodOrder::find($data['metadata']['order_id']);
        }

        if (!$order) {
            Log::warning("Stripe payment_intent.payment_failed for unknown order", [
                'payment_intent_id' => $data['payment_intent_id'],
            ]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Idempotency: if already paid, a late-arriving failure is a no-op
        if ($order->payment_status === 'paid') {
            return response()->json(['updated' => false, 'reason' => 'already_paid']);
        }

        // Mark the order cancelled with the failure reason (order was never confirmed,
        // never notified the restaurant — safe to cancel).
        $order->cancel('Payment failed: ' . ($data['failure_message'] ?? 'card declined'));
        $order->update(['payment_status' => 'unpaid']);

        Log::info("Stripe payment failed for order {$order->id}", [
            'payment_intent_id' => $data['payment_intent_id'],
            'failure_message' => $data['failure_message'] ?? null,
        ]);

        return response()->json(['updated' => true, 'order_id' => $order->id]);
    }
}
