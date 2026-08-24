<?php

namespace Tests\Feature\Api;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for Payment API endpoints (Task 5.5)
 *
 * GET /api/v1/payments/{rental} — payment detail (owner only)
 * POST /api/v1/payments/{rental}/pay — process payment (owner only)
 *
 * Validates: Requirements 8.1, 8.2, 8.3, 8.4
 */
class PaymentEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    private function createRentalWithPayment(User $user, array $rentalData = [], array $paymentData = []): Rental
    {
        $car = Car::factory()->create();

        $rental = Rental::factory()->create(array_merge([
            'customer_id' => $user->id,
            'car_id'      => $car->id,
        ], $rentalData));

        Payment::factory()->create(array_merge([
            'rental_id' => $rental->id,
        ], $paymentData));

        return $rental->fresh(['car', 'customer', 'payment']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/payments/{rental} — Payment detail
    // -------------------------------------------------------------------------

    /**
     * Requirement 8.1 — owner can view payment detail
     */
    #[Test]
    public function it_returns_payment_detail_for_owner(): void
    {
        $user   = $this->createVerifiedUser();
        $rental = $this->createRentalWithPayment($user);

        $response = $this->actingAs($user)->getJson("/api/v1/payments/{$rental->id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success'])
                 ->assertJsonStructure([
                     'data' => [
                         'id', 'rentalId', 'amountIdr', 'status',
                         'paymentMethod', 'paidAt', 'expiresAt',
                     ],
                 ]);

        $this->assertSame($rental->id, $response->json('data.rentalId'));
    }

    /**
     * Requirement 8.1 — non-owner cannot view payment (HTTP 403)
     */
    #[Test]
    public function it_returns_403_when_non_owner_tries_to_view_payment(): void
    {
        $owner     = $this->createVerifiedUser();
        $otherUser = $this->createVerifiedUser();
        $rental    = $this->createRentalWithPayment($owner);

        $response = $this->actingAs($otherUser)->getJson("/api/v1/payments/{$rental->id}");

        $response->assertStatus(403)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 8.1 — non-existent rental returns HTTP 404
     */
    #[Test]
    public function it_returns_404_when_rental_not_found_for_payment_detail(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->getJson('/api/v1/payments/9999');

        $response->assertStatus(404)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 9.3 — unauthenticated request returns HTTP 401
     */
    #[Test]
    public function payment_detail_returns_401_when_not_authenticated(): void
    {
        $user   = $this->createVerifiedUser();
        $rental = $this->createRentalWithPayment($user);

        $response = $this->getJson("/api/v1/payments/{$rental->id}");

        $response->assertStatus(401)
                 ->assertJson(['status' => 'error']);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/payments/{rental}/pay — Process payment
    // -------------------------------------------------------------------------

    /**
     * Requirement 8.2 — successful payment updates Payment and Rental status
     */
    #[Test]
    public function it_processes_payment_and_confirms_rental(): void
    {
        Queue::fake();

        $user   = $this->createVerifiedUser();
        $rental = $this->createRentalWithPayment($user, [
            'status' => RentalStatus::Pending,
        ], [
            'status' => PaymentStatus::Unpaid,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/payments/{$rental->id}/pay", [
            'payment_method' => 'transfer_bank',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $rental->refresh();
        $this->assertSame(RentalStatus::Confirmed, $rental->status);

        $payment = Payment::where('rental_id', $rental->id)->first();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('transfer_bank', $payment->payment_method);
    }

    /**
     * Requirement 8.4 — payment confirmation email job is dispatched to queue
     */
    #[Test]
    public function it_dispatches_payment_confirmation_email(): void
    {
        Queue::fake();

        $user   = $this->createVerifiedUser();
        $rental = $this->createRentalWithPayment($user, [], ['status' => PaymentStatus::Unpaid]);

        $this->actingAs($user)->postJson("/api/v1/payments/{$rental->id}/pay");

        Queue::assertPushed(\App\Jobs\SendPaymentConfirmationEmail::class);
    }

    /**
     * Requirement 8.3 — already paid rental returns HTTP 422
     */
    #[Test]
    public function it_returns_422_when_payment_already_paid(): void
    {
        $user   = $this->createVerifiedUser();
        $rental = $this->createRentalWithPayment($user, [], [
            'status'  => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/payments/{$rental->id}/pay");

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 8.2 — non-owner cannot pay (HTTP 403)
     */
    #[Test]
    public function it_returns_403_when_non_owner_tries_to_pay(): void
    {
        $owner     = $this->createVerifiedUser();
        $otherUser = $this->createVerifiedUser();
        $rental    = $this->createRentalWithPayment($owner);

        $response = $this->actingAs($otherUser)->postJson("/api/v1/payments/{$rental->id}/pay");

        $response->assertStatus(403)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 8.2 — non-existent rental returns HTTP 404
     */
    #[Test]
    public function it_returns_404_when_rental_not_found_for_payment(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->postJson('/api/v1/payments/9999/pay');

        $response->assertStatus(404)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 9.3 — unauthenticated request returns HTTP 401
     */
    #[Test]
    public function payment_pay_returns_401_when_not_authenticated(): void
    {
        $user   = $this->createVerifiedUser();
        $rental = $this->createRentalWithPayment($user);

        $response = $this->postJson("/api/v1/payments/{$rental->id}/pay");

        $response->assertStatus(401)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 8.2 — payment method is optional (can be null)
     */
    #[Test]
    public function it_processes_payment_without_payment_method(): void
    {
        Queue::fake();

        $user   = $this->createVerifiedUser();
        $rental = $this->createRentalWithPayment($user, [], ['status' => PaymentStatus::Unpaid]);

        $response = $this->actingAs($user)->postJson("/api/v1/payments/{$rental->id}/pay");

        $response->assertStatus(200);

        $payment = Payment::where('rental_id', $rental->id)->first();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertNull($payment->payment_method);
    }

    // -------------------------------------------------------------------------
    // Response envelope consistency
    // -------------------------------------------------------------------------

    /**
     * Property 21 — all payment endpoints follow JSON envelope format
     */
    #[Test]
    public function all_payment_endpoints_follow_json_envelope(): void
    {
        $user   = $this->createVerifiedUser();
        $rental = $this->createRentalWithPayment($user);

        $responses = [
            $this->actingAs($user)->getJson("/api/v1/payments/{$rental->id}"),
            $this->actingAs($user)->postJson("/api/v1/payments/{$rental->id}/pay"),
            $this->actingAs($user)->getJson('/api/v1/payments/9999'),
        ];

        foreach ($responses as $response) {
            $response->assertJsonStructure(['status', 'message', 'data']);
            $this->assertContains($response->json('status'), ['success', 'error']);
        }
    }
}
