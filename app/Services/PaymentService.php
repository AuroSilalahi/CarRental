<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Exceptions\PaymentAlreadyPaidException;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalStatusLog;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Initiate a payment for the given rental.
     *
     * Creates a Payment record with status = unpaid and expires_at = now() + 24 hours.
     * Idempotent: if a Payment already exists for this Rental, return it unchanged.
     *
     * @param  Rental  $rental
     * @return Payment
     */
    public function initiatePayment(Rental $rental): Payment
    {
        $existing = Payment::where('rental_id', $rental->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        return Payment::create([
            'rental_id'  => $rental->id,
            'amount_idr' => $rental->total_cost_idr,
            'status'     => PaymentStatus::Unpaid,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Record a payment for the given rental.
     *
     * Validates that the Payment is not already paid, then atomically updates:
     * - payments.status = paid, paid_at = now(), payment_method (if provided)
     * - rentals.status = confirmed
     * - Appends a RentalStatusLog entry with changed_by = null
     *
     * Uses DB::afterCommit() to dispatch confirmation email job (placeholder).
     *
     * @param  Rental              $rental
     * @param  array<string, mixed> $data  Optional: payment_method (string)
     * @return Payment
     *
     * @throws PaymentAlreadyPaidException if the Payment already has status = paid
     */
    public function recordPayment(Rental $rental, array $data): Payment
    {
        /** @var Payment $payment */
        $payment = Payment::where('rental_id', $rental->id)->firstOrFail();

        if ($payment->status === PaymentStatus::Paid) {
            throw new PaymentAlreadyPaidException();
        }

        return DB::transaction(function () use ($payment, $rental, $data): Payment {
            $payment->status         = PaymentStatus::Paid;
            $payment->paid_at        = now();
            $payment->payment_method = $data['payment_method'] ?? null;
            $payment->save();

            $rental->status = RentalStatus::Confirmed;
            $rental->save();

            RentalStatusLog::create([
                'rental_id'  => $rental->id,
                'status'     => RentalStatus::Confirmed,
                'changed_at' => now()->utc(),
                'changed_by' => null,
                'notes'      => null,
            ]);

            // TODO: dispatch SendPaymentConfirmationEmail job after commit
            // DB::afterCommit(fn () => SendPaymentConfirmationEmail::dispatch($rental));

            return $payment;
        });
    }

    /**
     * Expire the given payment.
     *
     * Within a DB transaction:
     * - Updates Payment.status = expired
     * - Updates Rental.status = expired
     * - Appends a RentalStatusLog entry with changed_by = null
     *
     * @param  Payment  $payment
     * @return void
     */
    public function expirePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            /** @var Rental $rental */
            $rental = $payment->rental()->lockForUpdate()->first();

            $payment->status = PaymentStatus::Expired;
            $payment->save();

            $rental->status = RentalStatus::Expired;
            $rental->save();

            RentalStatusLog::create([
                'rental_id'  => $rental->id,
                'status'     => RentalStatus::Expired,
                'changed_at' => now()->utc(),
                'changed_by' => null,
                'notes'      => null,
            ]);
        });
    }
}
