<?php

namespace App\Jobs;

use App\Mail\BookingConfirmationMail;
use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmationEmail implements ShouldQueue
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
     * Requirements: 7.7
     */
    public function handle(): void
    {
        Mail::to($this->rental->customer->email)->send(
            new BookingConfirmationMail($this->rental)
        );
    }
}
