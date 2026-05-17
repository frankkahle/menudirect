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

class ReservationStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function envelope(): Envelope
    {
        $subject = match($this->reservation->status) {
            'confirmed' => 'Reservation Confirmed - ' . $this->reservation->confirmation_number,
            'cancelled' => 'Reservation Cancelled - ' . $this->reservation->confirmation_number,
            default => 'Reservation Update - ' . $this->reservation->confirmation_number,
        };

        return new Envelope(
            from: new Address('no-reply@menudirect.ca', 'MenuDirect'),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.status-update',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
