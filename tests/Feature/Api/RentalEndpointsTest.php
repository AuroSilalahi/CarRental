<?php

namespace Tests\Feature\Api;

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
 * Feature tests for Rental API endpoints (Task 5.3, 5.6)
 *
 * POST /api/v1/rentals — create booking
 * GET /api/v1/rentals — list customer's rentals
 * GET /api/v1/rentals/{id} — rental detail (owner only)
 *
 * Validates: Requirements 7.1, 7.2, 7.4, 7.5, 7.6, 7.7, 9.1, 9.2, 9.4
 */
class RentalEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    private function createUnverifiedUser(): User
    {
        return User::factory()->unverified()->create();
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/rentals — Create booking
    // -------------------------------------------------------------------------

    /**
     * Requirement 7.2 — successful booking creates Rental and Payment records
     */
    #[Test]
    public function it_creates_rental_and_payment_on_valid_request(): void
    {
        Queue::fake();

        $user = $this->createVerifiedUser();
        $car  = Car::factory()->create(['daily_rate_idr' => 500000]);

        $startDate = now()->addDays(5)->toDateString();
        $endDate   = now()->addDays(10)->toDateString();

        $response = $this->actingAs($user)->postJson('/api/v1/rentals', [
            'car_id'           => $car->id,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'pickup_location'  => 'Medan, Sumatera Utara',
            'return_location'  => 'Binjai, Sumatera Utara',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['status' => 'success'])
                 ->assertJsonStructure([
                     'data' => [
                         'id', 'referenceNumber', 'carId', 'customerId',
                         'startDate', 'endDate', 'totalCostIdr', 'status',
                         'car' => ['id', 'brand', 'model'],
                     ],
                 ]);

        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('payments', 1);

        $rental = Rental::first();
        $this->assertSame($user->id, $rental->customer_id);
        $this->assertSame($car->id, $rental->car_id);
        $this->assertSame(RentalStatus::Pending, $rental->status);
        $this->assertNotNull($rental->reference_number);

        $payment = Payment::first();
        $this->assertSame($rental->id, $payment->rental_id);
        $this->assertSame($rental->total_cost_idr, $payment->amount_idr);
    }

    /**
     * Requirement 7.7 — booking confirmation email job is dispatched to queue
     */
    #[Test]
    public function it_dispatches_booking_confirmation_email(): void
    {
        Queue::fake();

        $user = $this->createVerifiedUser();
        $car  = Car::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/rentals', [
            'car_id'           => $car->id,
            'start_date'       => now()->addDays(5)->toDateString(),
            'end_date'         => now()->addDays(7)->toDateString(),
            'pickup_location'  => 'Location A',
            'return_location'  => 'Location B',
        ]);

        Queue::assertPushed(\App\Jobs\SendBookingConfirmationEmail::class);
    }

    /**
     * Requirement 7.6 — unverified email blocks booking with HTTP 403
     */
    #[Test]
    public function it_returns_403_when_email_not_verified(): void
    {
        $user = $this->createUnverifiedUser();
        $car  = Car::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/rentals', [
            'car_id'           => $car->id,
            'start_date'       => now()->addDays(5)->toDateString(),
            'end_date'         => now()->addDays(7)->toDateString(),
            'pickup_location'  => 'Location A',
            'return_location'  => 'Location B',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['status' => 'error']);

        $this->assertDatabaseCount('rentals', 0);
    }

    /**
     * Requirement 7.4 — overlapping rental date returns HTTP 422
     */
    #[Test]
    public function it_returns_422_when_car_not_available(): void
    {
        $user1 = $this->createVerifiedUser();
        $user2 = $this->createVerifiedUser();
        $car   = Car::factory()->create();

        // User1 books the car
        Rental::factory()->create([
            'car_id'      => $car->id,
            'customer_id' => $user1->id,
            'start_date'  => now()->addDays(5)->toDateString(),
            'end_date'    => now()->addDays(10)->toDateString(),
            'status'      => RentalStatus::Confirmed,
        ]);

        // User2 tries to book overlapping dates
        $response = $this->actingAs($user2)->postJson('/api/v1/rentals', [
            'car_id'           => $car->id,
            'start_date'       => now()->addDays(7)->toDateString(),
            'end_date'         => now()->addDays(12)->toDateString(),
            'pickup_location'  => 'Location A',
            'return_location'  => 'Location B',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 7.5 — end_date must be after start_date (validation)
     */
    #[Test]
    public function it_returns_422_when_end_date_is_not_after_start_date(): void
    {
        $user = $this->createVerifiedUser();
        $car  = Car::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/rentals', [
            'car_id'           => $car->id,
            'start_date'       => now()->addDays(5)->toDateString(),
            'end_date'         => now()->addDays(5)->toDateString(), // same day
            'pickup_location'  => 'Location A',
            'return_location'  => 'Location B',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error'])
                 ->assertJsonPath('data.end_date', fn ($v) => ! empty($v));
    }

    /**
     * Requirement 7.1 — missing required fields return HTTP 422
     */
    #[Test]
    public function it_returns_422_when_required_fields_missing(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->postJson('/api/v1/rentals', []);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $data = $response->json('data');
        $this->assertArrayHasKey('car_id', $data);
        $this->assertArrayHasKey('start_date', $data);
        $this->assertArrayHasKey('end_date', $data);
        $this->assertArrayHasKey('pickup_location', $data);
        $this->assertArrayHasKey('return_location', $data);
    }

    /**
     * Requirement 9.3 — unauthenticated request returns HTTP 401
     */
    #[Test]
    public function it_returns_401_when_not_authenticated(): void
    {
        $car = Car::factory()->create();

        $response = $this->postJson('/api/v1/rentals', [
            'car_id'           => $car->id,
            'start_date'       => '2025-06-10',
            'end_date'         => '2025-06-12',
            'pickup_location'  => 'Location A',
            'return_location'  => 'Location B',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['status' => 'error']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/rentals — List customer's rentals
    // -------------------------------------------------------------------------

    /**
     * Requirement 9.1, 9.2 — list returns only authenticated customer's rentals
     */
    #[Test]
    public function it_returns_only_authenticated_customers_rentals(): void
    {
        $user1 = $this->createVerifiedUser();
        $user2 = $this->createVerifiedUser();
        $car   = Car::factory()->create();

        Rental::factory()->count(2)->create(['customer_id' => $user1->id, 'car_id' => $car->id]);
        Rental::factory()->create(['customer_id' => $user2->id, 'car_id' => $car->id]);

        $response = $this->actingAs($user1)->getJson('/api/v1/rentals');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        foreach ($data as $rental) {
            $this->assertSame($user1->id, $rental['customerId']);
        }
    }

    /**
     * Requirement 9.4 — rentals are returned with nested car data
     */
    #[Test]
    public function it_returns_rentals_with_nested_car_resource(): void
    {
        $user = $this->createVerifiedUser();
        $car  = Car::factory()->create(['brand' => 'Toyota', 'model' => 'Avanza']);

        Rental::factory()->create(['customer_id' => $user->id, 'car_id' => $car->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/rentals');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id', 'referenceNumber', 'startDate', 'endDate',
                             'car' => ['id', 'brand', 'model', 'licensePlate'],
                         ],
                     ],
                 ]);

        $this->assertSame('Toyota', $response->json('data.0.car.brand'));
    }

    /**
     * Requirement 9.3 — unauthenticated request returns HTTP 401
     */
    #[Test]
    public function rentals_list_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/v1/rentals');

        $response->assertStatus(401)
                 ->assertJson(['status' => 'error']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/rentals/{id} — Rental detail (owner only)
    // -------------------------------------------------------------------------

    /**
     * Requirement 9.1, 9.2, 9.4 — rental detail returns single rental with car
     */
    #[Test]
    public function it_returns_rental_detail_for_owner(): void
    {
        $user   = $this->createVerifiedUser();
        $car    = Car::factory()->create();
        $rental = Rental::factory()->create(['customer_id' => $user->id, 'car_id' => $car->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/rentals/{$rental->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'data'   => [
                         'id'              => $rental->id,
                         'referenceNumber' => $rental->reference_number,
                         'customerId'      => $user->id,
                         'carId'           => $car->id,
                     ],
                 ])
                 ->assertJsonStructure([
                     'data' => ['car' => ['id', 'brand', 'model']],
                 ]);
    }

    /**
     * Requirement 9.4 — non-owner cannot access rental (HTTP 403)
     */
    #[Test]
    public function it_returns_403_when_non_owner_tries_to_access_rental(): void
    {
        $owner      = $this->createVerifiedUser();
        $otherUser  = $this->createVerifiedUser();
        $car        = Car::factory()->create();
        $rental     = Rental::factory()->create(['customer_id' => $owner->id, 'car_id' => $car->id]);

        $response = $this->actingAs($otherUser)->getJson("/api/v1/rentals/{$rental->id}");

        $response->assertStatus(403)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 9.2 — non-existent rental returns HTTP 404
     */
    #[Test]
    public function it_returns_404_when_rental_not_found(): void
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->getJson('/api/v1/rentals/9999');

        $response->assertStatus(404)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 9.3 — unauthenticated request returns HTTP 401
     */
    #[Test]
    public function rental_detail_returns_401_when_not_authenticated(): void
    {
        $rental = Rental::factory()->create();

        $response = $this->getJson("/api/v1/rentals/{$rental->id}");

        $response->assertStatus(401)
                 ->assertJson(['status' => 'error']);
    }
}
