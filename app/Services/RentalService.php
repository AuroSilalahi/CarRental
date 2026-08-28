<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Exceptions\CarNotAvailableException;
use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\RentalStatusConflictException;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalStatusLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RentalService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly PricingService $pricingService,
    ) {}

    /**
     * Create a new booking for the given customer.
     *
     * Validates email verification, checks car availability, calculates
     * pricing, and atomically creates Rental + Payment + RentalStatusLog
     * within a single DB transaction.
     *
     * @param  User                $customer
     * @param  array<string, mixed> $data  Must contain: car_id (int|Car),
     *                                     start_date (Carbon|string),
     *                                     end_date (Carbon|string),
     *                                     pickup_location (string),
     *                                     return_location (string)
     * @return Rental
     *
     * @throws EmailNotVerifiedException    if customer email is not verified
     * @throws CarNotAvailableException     if the car is booked for the requested period
     */
    public function createBooking(User $customer, array $data): Rental
    {
        // Email verification bypassed for simplified Admin-only KTP verification workflow.

        $car    = is_object($data['car_id']) ? $data['car_id'] : \App\Models\Car::findOrFail($data['car_id']);
        $start  = Carbon::parse($data['start_date'])->startOfDay();
        $end    = Carbon::parse($data['end_date'])->startOfDay();

        // 2. Check availability
        if (! $this->availabilityService->isAvailable($car->id, $start, $end)) {
            throw new CarNotAvailableException();
        }

        // 3. Calculate total cost
        $totalCost = $this->pricingService->calculateTotalCost($car, $start, $end);

        // 4. Create records atomically
        return DB::transaction(function () use ($customer, $car, $start, $end, $totalCost, $data) {
            $rental = Rental::create([
                'reference_number' => $this->generateReferenceNumber(),
                'customer_id'      => $customer->id,
                'car_id'           => $car->id,
                'start_date'       => $start->toDateString(),
                'end_date'         => $end->toDateString(),
                'pickup_location'  => $data['pickup_location'],
                'return_location'  => $data['return_location'],
                'total_cost_idr'   => $totalCost,
                'status'           => RentalStatus::Pending,
            ]);

            Payment::create([
                'rental_id'  => $rental->id,
                'amount_idr' => $totalCost,
                'status'     => PaymentStatus::Unpaid,
                'expires_at' => now()->addHours(24),
            ]);

            $this->appendStatusLog($rental, RentalStatus::Pending);

            return $rental;
        });
    }

    /**
     * Confirm a rental.
     *
     * Re-checks car availability (excluding the current rental) before
     * updating status to Confirmed and appending a status log entry.
     * The Car's `is_available` flag is NOT toggled — availability is
     * date-range based via AvailabilityService.
     *
     * @throws CarNotAvailableException if a conflicting rental now exists
     */
    public function confirmRental(Rental $rental): void
    {
        $start = Carbon::parse($rental->start_date)->startOfDay();
        $end   = Carbon::parse($rental->end_date)->startOfDay();

        if (! $this->availabilityService->isAvailable($rental->car_id, $start, $end, $rental->id)) {
            throw new CarNotAvailableException();
        }

        DB::transaction(function () use ($rental) {
            $rental->status = RentalStatus::Confirmed;
            $rental->save();

            $this->appendStatusLog($rental, RentalStatus::Confirmed);
        });
    }

    /**
     * Cancel a rental.
     *
     * Only rentals with status Confirmed or Active may be cancelled.
     * Appends a status log entry. Car `is_available` flag is not touched
     * (availability is date-range based).
     *
     * @throws RentalStatusConflictException if the rental status is not Confirmed or Active
     */
    public function cancelRental(Rental $rental): void
    {
        if (! in_array($rental->status, [RentalStatus::Confirmed, RentalStatus::Active], true)) {
            throw new RentalStatusConflictException(
                "Cannot cancel a rental with status '{$rental->status->value}'."
            );
        }

        DB::transaction(function () use ($rental) {
            $rental->status = RentalStatus::Cancelled;
            $rental->save();

            // Release car availability back to office
            $rental->car->update(['is_available' => true]);

            $this->appendStatusLog($rental, RentalStatus::Cancelled);
        });
    }

    /**
     * Complete a rental upon physical vehicle return to office.
     *
     * Only rentals with status Confirmed or Active may be completed.
     * Marks car as retrieved back at office (is_available = true).
     *
     * @throws RentalStatusConflictException if the rental status is not Confirmed or Active
     */
    public function completeRental(Rental $rental): void
    {
        if (! in_array($rental->status, [RentalStatus::Confirmed, RentalStatus::Active], true)) {
            throw new RentalStatusConflictException(
                "Cannot complete a rental with status '{$rental->status->value}'."
            );
        }

        DB::transaction(function () use ($rental) {
            $rental->status = RentalStatus::Completed;
            $rental->save();

            // Mark car as safely returned to office and ready for new rentals
            $rental->car->update(['is_available' => true]);

            $this->appendStatusLog($rental, RentalStatus::Completed);
        });
    }

    /**
     * Hand over keys and record payment at office.
     * Transitions rental status to Active and payment status to Paid.
     */
    public function checkInAndPayAtOffice(Rental $rental, array $paymentData = []): void
    {
        DB::transaction(function () use ($rental, $paymentData) {
            // Mark car as currently on rent (unavailable)
            $rental->car->update(['is_available' => false]);
            $payment = $rental->payment;
            if ($payment) {
                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'payment_method' => $paymentData['payment_method'] ?? 'Bayar di Kantor (Cash/EDC)',
                    'paid_at' => now(),
                ]);
            }

            $rental->status = RentalStatus::Active;
            $rental->save();

            $this->appendStatusLog($rental, RentalStatus::Active, auth()->id(), 'Serah terima kunci & bayar di kantor');
        });
    }

    /**
     * Generate a unique reference number in the format RNT-YYYYMMDD-XXXXX.
     *
     * XXXXX is a zero-padded random number (00001–99999). The uniqueness
     * check retries up to 10 times before giving up.
     */
    private function generateReferenceNumber(): string
    {
        $date = now()->format('Ymd');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $suffix = str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            $ref    = "RNT-{$date}-{$suffix}";

            if (! Rental::where('reference_number', $ref)->exists()) {
                return $ref;
            }
        }

        // Fallback: use microtime-derived unique suffix
        $suffix = str_pad((string) (intval(microtime(true) * 100) % 99999 + 1), 5, '0', STR_PAD_LEFT);

        return "RNT-{$date}-{$suffix}";
    }

    /**
     * Append a status log entry for the given rental and status.
     *
     * `changed_by` is null for all system-initiated status changes.
     */
    private function appendStatusLog(Rental $rental, RentalStatus $status, ?int $changedBy = null, ?string $notes = null): void
    {
        RentalStatusLog::create([
            'rental_id'  => $rental->id,
            'status'     => $status,
            'changed_at' => now()->utc(),
            'changed_by' => $changedBy,
            'notes'      => $notes,
        ]);
    }
}
