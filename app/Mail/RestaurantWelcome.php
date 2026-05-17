<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\RestaurantSite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RestaurantWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public RestaurantSite $site,
        public Invoice $invoice,
        public bool $isNewAccount = false,
        public ?string $tempPassword = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Restaurant Website is Ready - {$this->site->business_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.restaurant-welcome',
        );
    }
}
