<?php

namespace App\Jobs;

use App\Mail\NewReservation;
use App\Mail\ReservationConfirmation;
use App\Mail\ReservationStatusUpdate;
use App\Models\Reservation;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReservationNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected Reservation $reservation,
        protected string $notificationType = 'new_reservation'
    ) {}

    public function handle(SmsService $smsService): void
    {
        $this->reservation->load('restaurantSite.client');

        // Skip all notifications for demo accounts
        if ($this->reservation->restaurantSite->isDemoSite()) {
            Log::debug('Skipping reservation notifications for demo site', [
                'reservation_id' => $this->reservation->id,
            ]);
            return;
        }

        $settings = $this->reservation->restaurantSite->getReservationSettings();

        match ($this->notificationType) {
            'new_reservation' => $this->handleNewReservation($smsService, $settings),
            'status_update' => $this->handleStatusUpdate($smsService, $settings),
            default => Log::warning("Unknown reservation notification type: {$this->notificationType}"),
        };
    }

    protected function handleNewReservation(SmsService $smsService, array $settings): void
    {
        // Email to restaurant
        $restaurantEmail = $settings['notification_email'] ?? $this->reservation->restaurantSite->email;
        if ($restaurantEmail) {
            $this->sendEmail($restaurantEmail, new NewReservation($this->reservation));
        }

        // SMS to restaurant
        $restaurantPhone = $settings['notification_phone'] ?? $this->reservation->restaurantSite->phone;
        if ($restaurantPhone && $smsService->isEnabled()) {
            $smsService->sendNewReservationAlert(
                $restaurantPhone,
                $this->reservation->confirmation_number,
                $this->reservation->customer_name,
                $this->reservation->party_size,
                $this->reservation->formatted_date,
                $this->reservation->formatted_time
            );
        }

        // Confirmation email to customer
        if ($this->reservation->customer_email) {
            $this->sendEmail(
                $this->reservation->customer_email,
                new ReservationConfirmation($this->reservation)
            );
        }

        // Confirmation SMS to customer
        if ($this->reservation->customer_phone && $smsService->isEnabled()) {
            $smsService->sendReservationConfirmedAlert(
                $this->reservation->customer_phone,
                $this->reservation->restaurantSite->business_name,
                $this->reservation->confirmation_number,
                $this->reservation->formatted_date,
                $this->reservation->formatted_time,
                $this->reservation->party_size
            );
        }
    }

    protected function handleStatusUpdate(SmsService $smsService, array $settings): void
    {
        // Email to customer on status changes
        if ($this->reservation->customer_email) {
            $this->sendEmail(
                $this->reservation->customer_email,
                new ReservationStatusUpdate($this->reservation)
            );
        }

        // SMS for cancellations
        if ($this->reservation->status === Reservation::STATUS_CANCELLED) {
            if ($this->reservation->customer_phone && $smsService->isEnabled()) {
                $smsService->sendReservationCancelledAlert(
                    $this->reservation->customer_phone,
                    $this->reservation->restaurantSite->business_name,
                    $this->reservation->confirmation_number,
                    $this->reservation->cancellation_reason
                );
            }

            // Also notify restaurant if cancelled by customer
            if ($this->reservation->cancelled_by === 'customer') {
                $restaurantPhone = $settings['notification_phone'] ?? $this->reservation->restaurantSite->phone;
                if ($restaurantPhone && $smsService->isEnabled()) {
                    $smsService->sendReservationCancelledAlert(
                        $restaurantPhone,
                        $this->reservation->customer_name,
                        $this->reservation->confirmation_number,
                        'Cancelled by customer'
                    );
                }
            }
        }

        // SMS for confirmations
        if ($this->reservation->status === Reservation::STATUS_CONFIRMED && $this->reservation->customer_phone && $smsService->isEnabled()) {
            $smsService->sendReservationConfirmedAlert(
                $this->reservation->customer_phone,
                $this->reservation->restaurantSite->business_name,
                $this->reservation->confirmation_number,
                $this->reservation->formatted_date,
                $this->reservation->formatted_time,
                $this->reservation->party_size
            );
        }
    }

    protected function sendEmail(string $to, $mailable): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (\Exception $e) {
            Log::error('Failed to send reservation email', [
                'reservation_id' => $this->reservation->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendReservationNotificationsJob failed', [
            'reservation_id' => $this->reservation->id,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
