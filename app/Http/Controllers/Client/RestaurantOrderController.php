<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\RestaurantPlan;
use App\Models\RestaurantSite;
use App\Models\Service;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestaurantOrderController extends Controller
{
    /**
     * Display available restaurant plans
     */
    public function plans()
    {
        $plans = RestaurantPlan::active()->ordered()->get();

        return view('client.restaurant.order.plans', compact('plans'));
    }

    /**
     * Configure a new restaurant site order
     */
    public function configure(RestaurantPlan $plan)
    {
        if (!$plan->is_active) {
            return redirect()->route('client.restaurant.order.plans')
                ->with('error', 'This plan is no longer available.');
        }

        return view('client.restaurant.order.configure', compact('plan'));
    }

    /**
     * Process the order - validate and show checkout
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:menudirect.restaurant_plans,id',
            'business_name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|alpha_dash|unique:menudirect.restaurant_sites,slug',
            'billing_cycle' => 'required|in:monthly,annual',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'want_domain' => 'nullable|boolean',
            'domain_name' => 'nullable|required_if:want_domain,1|string|max:255',
            'domain_price' => 'nullable|required_if:want_domain,1|numeric|min:0',
            'domain_years' => 'nullable|integer|min:1|max:10',
        ]);

        $plan = RestaurantPlan::findOrFail($validated['plan_id']);

        // Calculate pricing
        $isAnnual = $validated['billing_cycle'] === 'annual';
        $recurringPrice = $isAnnual ? $plan->price_annual : $plan->price_monthly;
        $setupFee = $plan->setup_fee;
        $total = $recurringPrice + $setupFee;

        // Handle domain order
        $domainName = null;
        $domainPrice = 0;
        $domainYears = 1;

        if (!empty($validated['want_domain']) && !empty($validated['domain_name'])) {
            $domainName = strtolower(trim($validated['domain_name']));
            $domainPrice = (float) ($validated['domain_price'] ?? 0);
            $domainYears = (int) ($validated['domain_years'] ?? 1);
            $total += ($domainPrice * $domainYears);
        }

        // Store order details in session for processing
        session([
            'restaurant_order' => [
                'plan_id' => $plan->id,
                'business_name' => $validated['business_name'],
                'slug' => $validated['slug'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'billing_cycle' => $validated['billing_cycle'],
                'recurring_price' => $recurringPrice,
                'setup_fee' => $setupFee,
                'domain_name' => $domainName,
                'domain_price' => $domainPrice,
                'domain_years' => $domainYears,
                'total' => $total,
            ],
        ]);

        return view('client.restaurant.order.checkout', [
            'plan' => $plan,
            'orderDetails' => session('restaurant_order'),
            'client' => auth()->user(),
        ]);
    }

    /**
     * Process payment and create the restaurant site
     */
    public function process(Request $request)
    {
        $orderDetails = session('restaurant_order');

        if (!$orderDetails) {
            return redirect()->route('client.restaurant.order.plans')
                ->with('error', 'Order session expired. Please start again.');
        }

        $plan = RestaurantPlan::findOrFail($orderDetails['plan_id']);
        $client = auth()->user();

        // Validate slug is still available
        if (RestaurantSite::where('slug', $orderDetails['slug'])->exists()) {
            return back()->with('error', 'This URL is no longer available. Please choose a different one.');
        }

        try {
            DB::beginTransaction();

            // Create the restaurant site
            $site = RestaurantSite::create([
                'client_id' => $client->id,
                'restaurant_plan_id' => $plan->id,
                'slug' => $orderDetails['slug'],
                'business_name' => $orderDetails['business_name'],
                'phone' => $orderDetails['phone'],
                'email' => $orderDetails['email'],
                'address' => $orderDetails['address'],
                'status' => RestaurantSite::STATUS_DEMO, // Start as demo until setup complete
                'plan' => $this->mapPlanToType($plan->slug),
                'colors' => RestaurantSite::DEFAULT_COLORS,
                'hours' => RestaurantSite::DEFAULT_HOURS,
            ]);

            // Determine billing cycle
            $isAnnual = $orderDetails['billing_cycle'] === 'annual';
            $billingCycle = $isAnnual ? 'annual' : 'monthly';

            // Create the service record
            $nextBillDate = $isAnnual ? now()->addYear() : now()->addMonth();
            $service = Service::create([
                'client_id' => $client->id,
                'name' => "Restaurant Website: {$orderDetails['business_name']}",
                'type' => Service::TYPE_RESTAURANT,
                'serviceable_type' => RestaurantSite::class,
                'serviceable_id' => $site->id,
                'status' => Service::STATUS_PENDING,
                'billing_cycle' => $billingCycle,
                'amount' => $orderDetails['recurring_price'],
                'next_bill_date' => $nextBillDate,
                'starts_at' => now(),
                'auto_renew' => true,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'plan_slug' => $plan->slug,
                ],
            ]);

            // Create the invoice
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'status' => 'unpaid',
                'issue_date' => now(),
                'due_date' => now()->addDays(7),
                'subtotal' => $orderDetails['total'],
                'total' => $orderDetails['total'],
            ]);

            // Add invoice items
            if ($orderDetails['setup_fee'] > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => "{$plan->name} Restaurant Website - Setup Fee",
                    'quantity' => 1,
                    'unit_price' => $orderDetails['setup_fee'],
                    'total' => $orderDetails['setup_fee'],
                ]);
            }

            $cycleLabel = $isAnnual ? 'Annual' : 'Monthly';
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "{$plan->name} Restaurant Website - {$cycleLabel} Subscription",
                'quantity' => 1,
                'unit_price' => $orderDetails['recurring_price'],
                'total' => $orderDetails['recurring_price'],
                'service_id' => $service->id,
            ]);

            // Add domain to invoice if ordered
            $domainOrder = null;
            if (!empty($orderDetails['domain_name']) && $orderDetails['domain_price'] > 0) {
                $domainTotal = $orderDetails['domain_price'] * $orderDetails['domain_years'];
                $yearLabel = $orderDetails['domain_years'] == 1 ? '1 year' : "{$orderDetails['domain_years']} years";

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => "Domain Registration: {$orderDetails['domain_name']} ({$yearLabel})",
                    'quantity' => 1,
                    'unit_price' => $domainTotal,
                    'total' => $domainTotal,
                ]);

                // Create a pending domain order for processing after payment
                $domainOrder = Order::create([
                    'client_id' => $client->id,
                    'type' => 'register',
                    'years' => $orderDetails['domain_years'],
                    'status' => 'pending_payment',
                    'total_amount' => $domainTotal,
                    'currency' => 'CAD',
                    'idempotency_key' => Str::uuid()->toString(),
                    'metadata' => [
                        'domain' => $orderDetails['domain_name'],
                        'invoice_id' => $invoice->id,
                        'restaurant_site_id' => $site->id,
                        'for_restaurant' => true,
                    ],
                ]);

                // Update the site to indicate a domain is pending
                $site->update([
                    'custom_domain' => $orderDetails['domain_name'],
                    'settings' => array_merge($site->settings ?? [], [
                        'domain_order_id' => $domainOrder->id,
                        'domain_status' => 'pending_payment',
                    ]),
                ]);
            }

            // Create audit log
            AuditLog::create([
                'client_id' => $client->id,
                'action' => 'restaurant_site_ordered',
                'description' => "Ordered {$plan->name} restaurant website: {$orderDetails['business_name']}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            // Clear the session
            session()->forget('restaurant_order');

            // Redirect to invoice payment
            return redirect()->route('client.billing.invoice.show', $invoice)
                ->with('status', 'Your restaurant website order has been created! Please complete payment to activate your site.');

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'An error occurred processing your order. Please try again or contact support.');
        }
    }

    /**
     * Map plan slug to RestaurantSite plan type
     */
    protected function mapPlanToType(string $slug): string
    {
        return match ($slug) {
            'basic' => RestaurantSite::PLAN_BASIC,
            'sitefresh' => RestaurantSite::PLAN_SELFSERVICE,
            'sitefresh-pro' => RestaurantSite::PLAN_PREMIUM,
            'menudirect-max' => RestaurantSite::PLAN_MAX,
            default => RestaurantSite::PLAN_BASIC,
        };
    }
}
