<?php

namespace Tests\Feature\Api;

use App\Models\Car;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for Car API endpoints (Task 5.1)
 *
 * GET /api/v1/cars — list all cars with filters
 * GET /api/v1/cars/{id} — single car detail
 *
 * Validates: Requirements 6.2, 6.3, 6.5, 6.6
 */
class CarEndpointsTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // GET /api/v1/cars — List cars
    // -------------------------------------------------------------------------

    /**
     * Requirement 6.2 — car list returns all cars with proper camelCase format
     */
    #[Test]
    public function it_returns_all_cars_in_camel_case_format(): void
    {
        Car::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/cars');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success'])
                 ->assertJsonStructure([
                     'status', 'message',
                     'data' => [
                         '*' => [
                             'id', 'brand', 'model', 'type', 'licensePlate',
                             'passengerCapacity', 'colour', 'year', 'dailyRateIdr',
                             'isAvailable', 'isLuxuryBrand', 'luxuryMultiplier',
                         ],
                     ],
                 ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Requirement 6.3 — filter by type
     */
    #[Test]
    public function it_filters_cars_by_type(): void
    {
        Car::factory()->create(['type' => 'SUV']);
        Car::factory()->create(['type' => 'Sedan']);
        Car::factory()->create(['type' => 'SUV']);

        $response = $this->getJson('/api/v1/cars?type=SUV');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        foreach ($data as $car) {
            $this->assertSame('SUV', $car['type']);
        }
    }

    /**
     * Requirement 6.3 — filter by brand (partial match)
     */
    #[Test]
    public function it_filters_cars_by_brand(): void
    {
        Car::factory()->create(['brand' => 'Toyota']);
        Car::factory()->create(['brand' => 'Honda']);
        Car::factory()->create(['brand' => 'Toyota Alphard']);

        $response = $this->getJson('/api/v1/cars?brand=Toyota');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * Requirement 6.3 — filter by passenger_capacity (minimum)
     */
    #[Test]
    public function it_filters_cars_by_passenger_capacity(): void
    {
        Car::factory()->create(['passenger_capacity' => 4]);
        Car::factory()->create(['passenger_capacity' => 6]);
        Car::factory()->create(['passenger_capacity' => 8]);

        $response = $this->getJson('/api/v1/cars?passenger_capacity=6');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data); // 6 and 8
    }

    /**
     * Requirement 6.3 — filter by availability boolean
     */
    #[Test]
    public function it_filters_cars_by_availability_flag(): void
    {
        Car::factory()->create(['is_available' => true]);
        Car::factory()->create(['is_available' => false]);
        Car::factory()->create(['is_available' => true]);

        $response = $this->getJson('/api/v1/cars?availability=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        foreach ($data as $car) {
            $this->assertTrue($car['isAvailable']);
        }
    }

    /**
     * Requirement 6.6 — filter by date range using AvailabilityService
     */
    #[Test]
    public function it_filters_cars_by_date_range_availability(): void
    {
        $carAvailable = Car::factory()->create(['brand' => 'Available']);
        $carBooked    = Car::factory()->create(['brand' => 'Booked']);
        $customer     = User::factory()->create(['email_verified_at' => now()]);

        // Create a confirmed rental that overlaps the requested period
        Rental::factory()->create([
            'car_id'      => $carBooked->id,
            'customer_id' => $customer->id,
            'start_date'  => '2025-06-01',
            'end_date'    => '2025-06-05',
            'status'      => 'confirmed',
        ]);

        $response = $this->getJson('/api/v1/cars?start_date=2025-06-03&end_date=2025-06-07');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Only the car without conflict should be returned
        $this->assertCount(1, $data);
        $this->assertSame($carAvailable->id, $data[0]['id']);
    }

    /**
     * Requirement 6.6 — invalid date format returns HTTP 422
     */
    #[Test]
    public function it_returns_422_on_invalid_date_format(): void
    {
        Car::factory()->create();

        $response = $this->getJson('/api/v1/cars?start_date=invalid&end_date=2025-06-10');

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);
    }

    /**
     * Requirement 6.3 — multiple filters work together (AND logic)
     */
    #[Test]
    public function it_applies_multiple_filters_simultaneously(): void
    {
        Car::factory()->create(['type' => 'SUV', 'brand' => 'Toyota', 'is_available' => true]);
        Car::factory()->create(['type' => 'Sedan', 'brand' => 'Toyota', 'is_available' => true]);
        Car::factory()->create(['type' => 'SUV', 'brand' => 'Honda', 'is_available' => true]);
        Car::factory()->create(['type' => 'SUV', 'brand' => 'Toyota', 'is_available' => false]);

        $response = $this->getJson('/api/v1/cars?type=SUV&brand=Toyota&availability=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('SUV', $data[0]['type']);
        $this->assertStringContainsString('Toyota', $data[0]['brand']);
        $this->assertTrue($data[0]['isAvailable']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/cars/{id} — Car detail
    // -------------------------------------------------------------------------

    /**
     * Requirement 6.2 — car detail returns single car with camelCase format
     */
    #[Test]
    public function it_returns_single_car_detail(): void
    {
        $car = Car::factory()->create([
            'brand'              => 'Mercedes-Benz',
            'model'              => 'S-Class',
            'license_plate'      => 'BK1234AB',
            'is_luxury_brand'    => true,
            'luxury_multiplier'  => 2.0,
        ]);

        $response = $this->getJson("/api/v1/cars/{$car->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'data'    => [
                         'id'               => $car->id,
                         'brand'            => 'Mercedes-Benz',
                         'model'            => 'S-Class',
                         'licensePlate'     => 'BK1234AB',
                         'isLuxuryBrand'    => true,
                         'luxuryMultiplier' => '2.0',
                     ],
                 ]);
    }

    /**
     * Requirement 6.2 — non-existent car returns HTTP 404
     */
    #[Test]
    public function it_returns_404_when_car_not_found(): void
    {
        $response = $this->getJson('/api/v1/cars/9999');

        $response->assertStatus(404)
                 ->assertJson(['status' => 'error']);
    }

    // -------------------------------------------------------------------------
    // Response envelope consistency
    // -------------------------------------------------------------------------

    /**
     * Property 21 — all car endpoints follow JSON envelope format
     */
    #[Test]
    public function all_car_endpoints_follow_json_envelope(): void
    {
        $car = Car::factory()->create();

        $responses = [
            $this->getJson('/api/v1/cars'),
            $this->getJson("/api/v1/cars/{$car->id}"),
            $this->getJson('/api/v1/cars/9999'),
        ];

        foreach ($responses as $response) {
            $response->assertJsonStructure(['status', 'message', 'data']);
            $this->assertContains($response->json('status'), ['success', 'error']);
        }
    }
}
