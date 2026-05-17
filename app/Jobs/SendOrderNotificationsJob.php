<?php

namespace App\Jobs;

use App\Mail\NewFoodOrder;
use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusUpdate;
use App\Models\FoodOrder;
use App\Models\OrderNotification;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected FoodOrder $order,
        protected string $notificationType = 'new_order',
        protected ?string $previousStatus = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        $this->order->load('restaurantSite.client', 'items');

        // Skip all notifications for demo accounts
        if ($this->order->restaurantSite->isDemoSite()) {
            Log::debug('Skipping order notifications for demo site', [
                'order_id' => $this->order->id,
            ]);
            return;
        }

        $orderingSettings = $this->order->restaurantSite->getOrderingSettings();

        match ($this->notificationType) {
            'new_order' => $this->handleNewOrder($smsService, $orderingSettings),
            'status_update' => $this->handleStatusUpdate($smsService, $orderingSettings),
            default => Log::warning("Unknown notification type: {$this->notificationType}"),
        };
    }

    /**
     * Handle new order notifications.
     */
    protected function handleNewOrder(SmsService $smsService, array $orderingSettings): void
    {
        // Send email to restaurant
        $restaurantEmail = $orderingSettings['notification_email'] ?? $this->order->restaurantSite->email;
        if ($restaurantEmail) {
            $this->sendEmail(
                $restaurantEmail,
                new NewFoodOrder($this->order),
                OrderNotification::TYPE_NEW_ORDER,
                OrderNotification::CHANNEL_EMAIL
            );
        }

        // Send SMS to restaurant
        $restaurantPhone = $orderingSettings['notification_phone'] ?? $this->order->restaurantSite->phone;
        if ($restaurantPhone && $smsService->isEnabled()) {
            $result = $smsService->sendNewOrderAlert(
                $restaurantPhone,
                $this->order->order_number,
                $this->order->formatted_total,
                $this->order->order_type_label
            );

            $this->logNotification(
                OrderNotification::CHANNEL_SMS,
                $restaurantPhone,
                OrderNotification::TYPE_NEW_ORDER,
                $result['success'],
                $result['error'] ?? null,
                ['sid' => $result['sid'] ?? null]
            );
        }

        // Send confirmation email to customer
        if ($this->order->customer_email) {
            $this->sendEmail(
                $this->order->customer_email,
                new OrderConfirmation($this->order),
                OrderNotification::TYPE_CUSTOMER_CONFIRMATION,
                OrderNotification::CHANNEL_EMAIL
            );
        }
    }

    /**
     * Handle status update notifications.
     */
    protected function handleStatusUpdate(SmsService $smsService, array $orderingSettings): void
    {
        $restaurantName = $this->order->restaurantSite->business_name;

        // Send email to customer
        if ($this->order->customer_email) {
            $this->sendEmail(
                $this->order->customer_email,
                new OrderStatusUpdate($this->order, $this->previousStatus ?? ''),
                OrderNotification::TYPE_CUSTOMER_STATUS_UPDATE,
                OrderNotification::CHANNEL_EMAIL
            );
        }

        // Send SMS for important status changes
        if ($smsService->isEnabled() && $this->order->customer_phone) {
            $result = null;

            switch ($this->order->status) {
                case FoodOrder::STATUS_CONFIRMED:
                    $estimatedTime = $this->order->estimated_ready_at?->format('g:i A');
                    $result = $smsService->sendOrderConfirmedAlert(
                        $this->order->customer_phone,
                        $this->order->order_number,
                        $restaurantName,
                        $estimatedTime
                    );
                    break;

                case FoodOrder::STATUS_READY:
                    $result = $smsService->sendOrderReadyAlert(
                        $this->order->customer_phone,
                        $this->order->order_number,
                        $restaurantName,
                        $this->order->isPickup()
                    );
                    break;

                case FoodOrder::STATUS_CANCELLED:
                    $result = $smsService->sendOrderCancelledAlert(
                        $this->order->customer_phone,
                        $this->order->order_number,
                        $restaurantName,
                        $this->order->cancellation_reason
                    );
                    break;
            }

            if ($result) {
                $this->logNotification(
                    OrderNotification::CHANNEL_SMS,
                    $this->order->customer_phone,
                    OrderNotification::TYPE_CUSTOMER_STATUS_UPDATE,
                    $result['success'],
                    $result['error'] ?? null,
                    ['sid' => $result['sid'] ?? null, 'status' => $this->order->status]
                );
            }
        }
    }

    /**
     * Send an email and log the notification.
     */
    protected function sendEmail(string $to, $mailable, string $type, string $channel): void
    {
        try {
            Mail::to($to)->send($mailable);

            $this->logNotification($channel, $to, $type, true);

        } catch (\Exception $e) {
            Log::error('Failed to send order email', [
                'order_id' => $this->order->id,
                'to' => $to,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            $this->logNotification($channel, $to, $type, false, $e->getMessage());
        }
    }

    /**
     * Log a notification to the database.
     */
    protected function logNotification(
        string $channel,
        string $recipient,
        string $type,
        bool $success,
        ?string $error = null,
        array $metadata = []
    ): void {
        $this->order->notifications()->create([
            'channel' => $channel,
            'recipient' => $recipient,
            'notification_type' => $type,
            'status' => $success ? OrderNotification::STATUS_SENT : OrderNotification::STATUS_FAILED,
            'sent_at' => $success ? now() : null,
            'error_message' => $error,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Handle a failed job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendOrderNotificationsJob failed', [
            'order_id' => $this->order->id,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
