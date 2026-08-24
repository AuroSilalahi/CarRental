<?php

namespace App\Mail;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Rental $rental,
        public readonly string $errorMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ ALERT: Rental #' . $this->rental->reference_number . ' Memerlukan Tinjauan Admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-alert',
            with: [
                'rental' => $this->rental,
                'errorMessage' => $this->errorMessage,
            ],
        );
    }
}
