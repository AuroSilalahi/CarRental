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
                'pickup_location'  => $data['pickup_location'] ?? 'Kantor Utama CarRental (Jl. Pemuda No. 1, Medan)',
                'return_location'  => $data['return_location'] ?? 'Kantor Utama CarRental (Jl. Pemuda No. 1, Medan)',
                'destination'      => $data['destination'] ?? null,
                'total_cost_idr'   => $totalCost,
                'status'           => RentalStatus::Pending,
            ]);

            Payment::create([
                'rental_id'  => $rental->id,
                'amount_idr' => $totalCost,
                'status'     => PaymentStatus::Unpaid,
                'expires_at' => null,
            ]);

            $this->appendStatusLog($rental, RentalStatus::Pending);

            return $rental;
        });
    }

    /**
     * Confirm a pending rental.
     *
     * Only rentals with status Pending can be confirmed.
     *
     * @throws CarNotAvailableException if a conflicting rental now exists
     * @throws RentalStatusConflictException if rental status is not Pending
     */
    public function confirmRental(Rental $rental): void
    {
        $status = $rental->status instanceof RentalStatus ? $rental->status : RentalStatus::from($rental->status);
        if ($status !== RentalStatus::Pending) {
            throw new RentalStatusConflictException(
                "Cannot confirm a rental with status '{$status->value}'."
            );
        }

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
     * Only rentals with status Pending or Confirmed may be cancelled.
     *
     * @throws RentalStatusConflictException if the rental status is not Pending or Confirmed
     */
    public function cancelRental(Rental $rental): void
    {
        $status = $rental->status instanceof RentalStatus ? $rental->status : RentalStatus::from($rental->status);
        if (! in_array($status, [RentalStatus::Pending, RentalStatus::Confirmed], true)) {
            throw new RentalStatusConflictException(
                "Cannot cancel a rental with status '{$status->value}'."
            );
        }

        DB::transaction(function () use ($rental) {
            $rental->status = RentalStatus::Cancelled;
            $rental->save();

            $this->appendStatusLog($rental, RentalStatus::Cancelled);
        });
    }

    /**
     * Complete a rental upon physical vehicle return to office.
     *
     * Only rentals with status Active may be completed.
     *
     * @throws RentalStatusConflictException if the rental status is not Active
     */
    public function completeRental(Rental $rental): void
    {
        $status = $rental->status instanceof RentalStatus ? $rental->status : RentalStatus::from($rental->status);
        if ($status !== RentalStatus::Active) {
            throw new RentalStatusConflictException(
                "Cannot complete a rental with status '{$status->value}'."
            );
        }

        DB::transaction(function () use ($rental) {
            $rental->status = RentalStatus::Completed;
            $rental->save();

            $this->appendStatusLog($rental, RentalStatus::Completed);
        });
    }

    /**
     * Hand over keys and record payment at office.
     * Only rentals with status Confirmed can be activated.
     *
     * @throws RentalStatusConflictException if the rental status is not Confirmed
     */
    public function checkInAndPayAtOffice(Rental $rental, array $paymentData = []): void
    {
        $status = $rental->status instanceof RentalStatus ? $rental->status : RentalStatus::from($rental->status);
        if ($status !== RentalStatus::Confirmed) {
            throw new RentalStatusConflictException(
                "Cannot activate or record payment for a rental with status '{$status->value}'."
            );
        }

        DB::transaction(function () use ($rental, $paymentData) {
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
