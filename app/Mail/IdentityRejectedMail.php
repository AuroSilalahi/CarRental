<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IdentityRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $customer,
        public readonly string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan Penolakan Dokumen KTP — CarRental',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.identity-rejected',
            with: [
                'customer' => $this->customer,
                'reason' => $this->reason,
            ],
        );
    }
}
