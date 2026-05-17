<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReservation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no-reply@menudirect.ca', 'MenuDirect'),
            subject: 'New Reservation ' . $this->reservation->confirmation_number . ' - Action Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.new-reservation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
