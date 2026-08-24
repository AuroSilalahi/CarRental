<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Exceptions\CarNotAvailableException;
use App\Exceptions\EmailNotVerifiedException;
use App\Exceptions\RentalStatusConflictException;
use App\Models\Car;
use App\Models\Rental;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RentalServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RentalService(
            new AvailabilityService(),
            new PricingService(),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function verifiedCustomer(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function unverifiedCustomer(): User
    {
        return User::factory()->unverified()->create();
    }

    private function car(array $overrides = []): Car
    {
        return Car::factory()->create(array_merge([
            'daily_rate_idr'    => 300_000,
            'is_luxury_brand'   => false,
            'luxury_multiplier' => 1.0,
        ], $overrides));
    }

    private function bookingData(Car $car, string $start = '+1 day', string $end = '+3 days'): array
    {
        return [
            'car_id'          => $car,
            'start_date'      => Carbon::parse($start)->startOfDay(),
            'end_date'        => Carbon::parse($end)->startOfDay(),
            'pickup_location' => 'Jl. Sudirman No. 1, Medan',
            'return_location' => 'Jl. Gatot Subroto No. 5, Medan',
        ];
    }

    // -------------------------------------------------------------------------
    // createBooking — success
    // -------------------------------------------------------------------------

    #[Test]
    public function create_booking_creates_rental_payment_and_log_atomically(): void
    {
        $customer = $this->verifiedCustomer();
        $car      = $this->car();
        $data     = $this->bookingData($car);

        $rental = $this->service->createBooking($customer, $data);

        // Rental persisted with correct attributes
        $this->assertInstanceOf(Rental::class, $rental);
        $this->assertDatabaseHas('rentals', [
            'id'          => $rental->id,
            'customer_id' => $customer->id,
            'car_id'      => $car->id,
            'status'      => RentalStatus::Pending->value,
        ]);

        // reference_number matches expected format RNT-YYYYMMDD-XXXXX
        $this->assertMatchesRegularExpression(
            '/^RNT-\d{8}-\d{5}$/',
            $rental->reference_number
        );

        // Payment created as Unpaid with 24-hour expiry
        $this->assertDatabaseHas('payments', [
            'rental_id' => $rental->id,
            'status'    => PaymentStatus::Unpaid->value,
        ]);
        $payment = $rental->payment()->first();
        $this->assertNotNull($payment);
        $this->assertTrue($payment->expires_at->greaterThan(now()->addHours(23)));

        // Status log appended
        $this->assertDatabaseHas('rental_status_logs', [
            'rental_id'  => $rental->id,
            'status'     => RentalStatus::Pending->value,
            'changed_by' => null,
        ]);
        $this->assertCount(1, $rental->statusLogs()->get());
    }

    // -------------------------------------------------------------------------
    // createBooking — email not verified
    // -------------------------------------------------------------------------

    #[Test]
    public function create_booking_throws_if_email_not_verified(): void
    {
        $customer = $this->unverifiedCustomer();
        $car      = $this->car();
        $data     = $this->bookingData($car);

        $this->expectException(EmailNotVerifiedException::class);

        $this->service->createBooking($customer, $data);

        // No rental should be created
        $this->assertDatabaseCount('rentals', 0);
    }

    // -------------------------------------------------------------------------
    // createBooking — car not available
    // -------------------------------------------------------------------------

    #[Test]
    public function create_booking_throws_if_car_not_available(): void
    {
        $customer = $this->verifiedCustomer();
        $car      = $this->car();

        // Create a conflicting rental
        Rental::factory()->create([
            'car_id'     => $car->id,
            'start_date' => Carbon::parse('+1 day')->startOfDay(),
            'end_date'   => Carbon::parse('+5 days')->startOfDay(),
            'status'     => RentalStatus::Confirmed,
        ]);

        $data = $this->bookingData($car, '+2 days', '+4 days');

        $this->expectException(CarNotAvailableException::class);

        $this->service->createBooking($customer, $data);
    }

    // -------------------------------------------------------------------------
    // confirmRental — success
    // -------------------------------------------------------------------------

    #[Test]
    public function confirm_rental_updates_status_and_appends_log(): void
    {
        $customer = $this->verifiedCustomer();
        $car      = $this->car();
        $data     = $this->bookingData($car);

        $rental = $this->service->createBooking($customer, $data);

        $this->service->confirmRental($rental);

        $rental->refresh();

        $this->assertEquals(RentalStatus::Confirmed, $rental->status);

        $this->assertDatabaseHas('rental_status_logs', [
            'rental_id' => $rental->id,
            'status'    => RentalStatus::Confirmed->value,
        ]);

        // Two log entries: Pending + Confirmed
        $this->assertCount(2, $rental->statusLogs()->get());
    }

    // -------------------------------------------------------------------------
    // confirmRental — date conflict
    // -------------------------------------------------------------------------

    #[Test]
    public function confirm_rental_throws_if_conflicting_rental_exists(): void
    {
        $customer = $this->verifiedCustomer();
        $car      = $this->car();

        // First booking (pending — counts as conflict for availability check)
        $rental = $this->service->createBooking($customer, $this->bookingData($car));

        // Second rental on the same car/dates, already confirmed
        Rental::factory()->create([
            'car_id'     => $car->id,
            'start_date' => Carbon::parse('+1 day')->startOfDay(),
            'end_date'   => Carbon::parse('+3 days')->startOfDay(),
            'status'     => RentalStatus::Confirmed,
        ]);

        $this->expectException(CarNotAvailableException::class);

        $this->service->confirmRental($rental);
    }

    // -------------------------------------------------------------------------
    // cancelRental — success
    // -------------------------------------------------------------------------

    #[Test]
    public function cancel_rental_updates_status_and_appends_log(): void
    {
        $customer = $this->verifiedCustomer();
        $car      = $this->car();
        $rental   = $this->service->createBooking($customer, $this->bookingData($car));

        // Manually set to Confirmed so it's cancellable
        $rental->update(['status' => RentalStatus::Confirmed]);

        $this->service->cancelRental($rental->fresh());

        $rental->refresh();

        $this->assertEquals(RentalStatus::Cancelled, $rental->status);

        $this->assertDatabaseHas('rental_status_logs', [
            'rental_id' => $rental->id,
            'status'    => RentalStatus::Cancelled->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // cancelRental — wrong status (Pending)
    // -------------------------------------------------------------------------

    #[Test]
    public function cancel_rental_throws_if_status_is_pending(): void
    {
        $customer = $this->verifiedCustomer();
        $car      = $this->car();
        $rental   = $this->service->createBooking($customer, $this->bookingData($car));

        // Status is Pending — not cancellable via cancelRental()
        $this->assertEquals(RentalStatus::Pending, $rental->status);

        $this->expectException(RentalStatusConflictException::class);

        $this->service->cancelRental($rental);
    }

    // -------------------------------------------------------------------------
    // completeRental — success
    // -------------------------------------------------------------------------

    #[Test]
    public function complete_rental_updates_status_and_appends_log(): void
    {
        $customer = $this->verifiedCustomer();
        $car      = $this->car();
        $rental   = $this->service->createBooking($customer, $this->bookingData($car));

        $rental->update(['status' => RentalStatus::Confirmed]);

        $this->service->completeRental($rental->fresh());

        $rental->refresh();

        $this->assertEquals(RentalStatus::Completed, $rental->status);

        $this->assertDatabaseHas('rental_status_logs', [
            'rental_id' => $rental->id,
            'status'    => RentalStatus::Completed->value,
        ]);
    }
}
