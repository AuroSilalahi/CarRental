<?php

namespace App\Jobs;

use App\Mail\PaymentConfirmationMail;
use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Rental $rental
    ) {}

    /**
     * Execute the job.
     *
     * Requirements: 8.4
     */
    public function handle(): void
    {
        Mail::to($this->rental->customer->email)->send(
            new PaymentConfirmationMail($this->rental)
        );
    }
}
