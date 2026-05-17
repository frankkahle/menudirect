<?php

namespace App\Jobs;

use App\Mail\CateringInquiryConfirmation;
use App\Mail\NewCateringInquiry;
use App\Models\CateringInquiry;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCateringInquiryNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        protected CateringInquiry $inquiry
    ) {}

    public function handle(SmsService $smsService): void
    {
        $this->inquiry->load('restaurantSite.client', 'cateringPackage');

        // Skip notifications for demo accounts
        if ($this->inquiry->restaurantSite->isDemoSite()) {
            Log::debug('Skipping catering notifications for demo site', [
                'inquiry_id' => $this->inquiry->id,
            ]);
            return;
        }

        $settings = $this->inquiry->restaurantSite->getCateringSettings();

        // Email to restaurant
        $restaurantEmail = $settings['notification_email'] ?? $this->inquiry->restaurantSite->email;
        if ($restaurantEmail) {
            $this->sendEmail($restaurantEmail, new NewCateringInquiry($this->inquiry));
        }

        // SMS to restaurant
        $restaurantPhone = $settings['notification_phone'] ?? $this->inquiry->restaurantSite->phone;
        if ($restaurantPhone && $smsService->isEnabled()) {
            $smsService->sendNewCateringInquiryAlert(
                $restaurantPhone,
                $this->inquiry->inquiry_number,
                $this->inquiry->customer_name,
                $this->inquiry->guest_count,
                $this->inquiry->formatted_date
            );
        }

        // Confirmation email to customer
        if ($this->inquiry->customer_email) {
            $this->sendEmail(
                $this->inquiry->customer_email,
                new CateringInquiryConfirmation($this->inquiry)
            );
        }
    }

    protected function sendEmail(string $to, $mailable): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (\Exception $e) {
            Log::error('Failed to send catering email', [
                'inquiry_id' => $this->inquiry->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendCateringInquiryNotificationsJob failed', [
            'inquiry_id' => $this->inquiry->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
