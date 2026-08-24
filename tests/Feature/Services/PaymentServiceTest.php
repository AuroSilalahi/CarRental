<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Exceptions\PaymentAlreadyPaidException;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalStatusLog;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
    }

    // -------------------------------------------------------------------------
    // initiatePayment
    // -------------------------------------------------------------------------

    /**
     * Requirements: 8.2, 8.6
     */
    #[Test]
    public function it_creates_a_payment_with_unpaid_status_and_24h_expiry(): void
    {
        $rental = Rental::factory()->pending()->create();

        $before  = now();
        $payment = $this->service->initiatePayment($rental);
        $after   = now()->addHours(24);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertTrue($payment->exists);
        $this->assertSame(PaymentStatus::Unpaid, $payment->status);
        $this->assertSame($rental->total_cost_idr, $payment->amount_idr);
        $this->assertSame($rental->id, $payment->rental_id);

        // expires_at should be approximately now() + 24 hours
        $this->assertNotNull($payment->expires_at);
        $this->assertTrue(
            $payment->expires_at->between($before->addHours(24)->subMinute(), $after->addMinute()),
            "expires_at ({$payment->expires_at}) should be ~24 hours from now"
        );
    }

    /**
     * Requirements: 8.2
     */
    #[Test]
    public function it_saves_the_payment_record_to_the_database(): void
    {
        $rental = Rental::factory()->pending()->create();

        $payment = $this->service->initiatePayment($rental);

        $this->assertDatabaseHas('payments', [
            'id'        => $payment->id,
            'rental_id' => $rental->id,
            'status'    => PaymentStatus::Unpaid->value,
        ]);
    }

    /**
     * Requirements: 8.2
     */
    #[Test]
    public function initiate_payment_is_idempotent_and_returns_same_payment(): void
    {
        $rental = Rental::factory()->pending()->create();

        $first  = $this->service->initiatePayment($rental);
        $second = $this->service->initiatePayment($rental);

        $this->assertSame($first->id, $second->id);
        $this->assertCount(1, Payment::where('rental_id', $rental->id)->get());
    }

    // -------------------------------------------------------------------------
    // recordPayment
    // -------------------------------------------------------------------------

    /**
     * Requirements: 8.2
     */
    #[Test]
    public function record_payment_marks_payment_as_paid_and_rental_as_confirmed(): void
    {
        $rental  = Rental::factory()->pending()->create();
        $payment = Payment::factory()->create([
            'rental_id'  => $rental->id,
            'status'     => PaymentStatus::Unpaid,
            'amount_idr' => $rental->total_cost_idr,
            'expires_at' => now()->addHours(24),
        ]);

        $result = $this->service->recordPayment($rental, ['payment_method' => 'bank_transfer']);

        // Reload from DB to verify persistence
        $payment->refresh();
        $rental->refresh();

        $this->assertSame(PaymentStatus::Paid, $result->status);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('bank_transfer', $payment->payment_method);

        $this->assertSame(RentalStatus::Confirmed, $rental->status);
    }

    /**
     * Requirements: 8.2
     */
    #[Test]
    public function record_payment_appends_a_status_log_entry(): void
    {
        $rental  = Rental::factory()->pending()->create();
        Payment::factory()->create([
            'rental_id'  => $rental->id,
            'status'     => PaymentStatus::Unpaid,
            'amount_idr' => $rental->total_cost_idr,
            'expires_at' => now()->addHours(24),
        ]);

        $this->service->recordPayment($rental, []);

        $this->assertDatabaseHas('rental_status_logs', [
            'rental_id'  => $rental->id,
            'status'     => RentalStatus::Confirmed->value,
            'changed_by' => null,
        ]);
        $this->assertSame(1, RentalStatusLog::where('rental_id', $rental->id)->count());
    }

    /**
     * Requirements: 8.2
     */
    #[Test]
    public function record_payment_works_without_payment_method(): void
    {
        $rental  = Rental::factory()->pending()->create();
        Payment::factory()->create([
            'rental_id'  => $rental->id,
            'status'     => PaymentStatus::Unpaid,
            'amount_idr' => $rental->total_cost_idr,
            'expires_at' => now()->addHours(24),
        ]);

        $result = $this->service->recordPayment($rental, []);

        $this->assertSame(PaymentStatus::Paid, $result->status);
        $this->assertNull($result->payment_method);
    }

    /**
     * Requirements: 8.7
     */
    #[Test]
    public function record_payment_throws_exception_when_already_paid(): void
    {
        $rental  = Rental::factory()->confirmed()->create();
        Payment::factory()->create([
            'rental_id'  => $rental->id,
            'status'     => PaymentStatus::Paid,
            'amount_idr' => $rental->total_cost_idr,
            'paid_at'    => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $this->expectException(PaymentAlreadyPaidException::class);

        $this->service->recordPayment($rental, ['payment_method' => 'cash']);
    }

    /**
     * Requirements: 8.2 — atomicity: if an exception occurs mid-transaction, neither
     * Payment nor Rental should change status.
     */
    #[Test]
    public function record_payment_rolls_back_on_exception(): void
    {
        $rental  = Rental::factory()->pending()->create();
        $payment = Payment::factory()->create([
            'rental_id'  => $rental->id,
            'status'     => PaymentStatus::Unpaid,
            'amount_idr' => $rental->total_cost_idr,
            'expires_at' => now()->addHours(24),
        ]);

        // Force a failure by observing the model event and throwing after the payment
        // is updated but before the rental is saved.
        Rental::saving(function () {
            throw new RuntimeException('Simulated mid-transaction failure');
        });

        try {
            $this->service->recordPayment($rental, []);
        } catch (RuntimeException $e) {
            // Expected — the transaction must have rolled back.
        }

        // Remove the observer so subsequent queries are unaffected.
        Rental::flushEventListeners();

        // Both statuses must remain unchanged.
        $payment->refresh();
        $rental->refresh();

        $this->assertSame(PaymentStatus::Unpaid, $payment->status);
        $this->assertSame(RentalStatus::Pending, $rental->status);
        $this->assertDatabaseMissing('rental_status_logs', ['rental_id' => $rental->id]);
    }

    // -------------------------------------------------------------------------
    // expirePayment
    // -------------------------------------------------------------------------

    /**
     * Requirements: 8.5
     */
    #[Test]
    public function expire_payment_sets_payment_and_rental_to_expired(): void
    {
        $rental  = Rental::factory()->pending()->create();
        $payment = Payment::factory()->create([
            'rental_id'  => $rental->id,
            'status'     => PaymentStatus::Unpaid,
            'amount_idr' => $rental->total_cost_idr,
            'expires_at' => now()->subHour(), // already past expiry
        ]);

        $this->service->expirePayment($payment);

        $payment->refresh();
        $rental->refresh();

        $this->assertSame(PaymentStatus::Expired, $payment->status);
        $this->assertSame(RentalStatus::Expired, $rental->status);
    }

    /**
     * Requirements: 8.5
     */
    #[Test]
    public function expire_payment_appends_a_status_log_entry(): void
    {
        $rental  = Rental::factory()->pending()->create();
        $payment = Payment::factory()->create([
            'rental_id'  => $rental->id,
            'status'     => PaymentStatus::Unpaid,
            'amount_idr' => $rental->total_cost_idr,
            'expires_at' => now()->subHour(),
        ]);

        $this->service->expirePayment($payment);

        $this->assertDatabaseHas('rental_status_logs', [
            'rental_id'  => $rental->id,
            'status'     => RentalStatus::Expired->value,
            'changed_by' => null,
        ]);
        $this->assertSame(1, RentalStatusLog::where('rental_id', $rental->id)->count());
    }
}
