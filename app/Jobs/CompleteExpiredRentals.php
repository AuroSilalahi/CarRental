<?php

namespace App\Jobs;

use App\Enums\RentalStatus;
use App\Mail\AdminAlertMail;
use App\Models\Rental;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CompleteExpiredRentals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();

        $rentals = Rental::whereIn('status', [RentalStatus::Confirmed, RentalStatus::Active])
            ->where('end_date', '<', $today)
            ->get();

        /** @var RentalService $rentalService */
        $rentalService = app(RentalService::class);

        foreach ($rentals as $rental) {
            try {
                $rentalService->completeRental($rental);
            } catch (\Throwable $e) {
                Log::error("Failed auto-completing rental #{$rental->id}: {$e->getMessage()}");

                $rental->update(['status' => RentalStatus::ReviewRequired]);

                $adminEmail = config('mail.admin_address', 'admin@carrental.com');
                Mail::to($adminEmail)->send(new AdminAlertMail($rental, $e->getMessage()));
            }
        }
    }
}
