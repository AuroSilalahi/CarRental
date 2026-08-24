<?php

namespace Tests\Feature\Integration;

use App\Enums\AccountStatus;
use App\Enums\IdentityDocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Jobs\CompleteExpiredRentals;
use App\Jobs\ExpireUnpaidPayments;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndRentalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_customer_and_admin_rental_lifecycle(): void
    {
        Mail::fake();
        Queue::fake();
        Storage::fake('local');

        // 1. Customer Registration
        $registerResponse = $this->postJson('/api/v1/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'password' => 'Password123!',
            'address' => 'Jl. Merdeka No. 45',
            'city' => 'Medan',
            'province' => 'Sumatera Utara',
        ]);

        $registerResponse->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);

        $customer = User::where('email', 'budi@example.com')->first();
        $customer->markEmailAsVerified(); // Verify email for booking

        // 2. Customer Uploads KTP Identity Document (field: 'file')
        $ktp = UploadedFile::fake()->create('ktp_budi.jpg', 1024, 'image/jpeg');

        $uploadResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/profile/identity', [
                'file' => $ktp,
            ]);

        $uploadResponse->assertStatus(201);
        $this->assertDatabaseHas('identity_documents', [
            'customer_id' => $customer->id,
            'status' => IdentityDocumentStatus::PendingReview->value,
        ]);

        // 3. Create a Car Fleet Record
        $car = Car::create([
            'brand' => 'Toyota',
            'model' => 'Innova Zenix',
            'type' => 'MPV',
            'license_plate' => 'BK 1234 ABC',
            'passenger_capacity' => 7,
            'colour' => 'Hitam',
            'year' => 2024,
            'daily_rate_idr' => 750000,
            'is_available' => true,
            'is_luxury_brand' => false,
            'luxury_multiplier' => 1.0,
        ]);

        // 4. Booking Creation
        $bookingResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/rentals', [
                'car_id' => $car->id,
                'start_date' => Carbon::tomorrow()->toDateString(),
                'end_date' => Carbon::tomorrow()->addDays(3)->toDateString(),
                'pickup_location' => 'Bandara Kualanamu, Medan',
                'return_location' => 'Hotel Grand Aston, Medan',
            ]);

        $bookingResponse->assertStatus(201);
        $rentalId = $bookingResponse->json('data.id');

        $rental = Rental::find($rentalId);
        $this->assertNotNull($rental);
        $this->assertEquals(RentalStatus::Pending, $rental->status);

        // 5. Payment Processing
        $paymentResponse = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/payments/{$rental->id}/pay", [
                'payment_method' => 'manual',
            ]);

        $paymentResponse->assertStatus(200);

        $rental->refresh();
        $this->assertEquals(RentalStatus::Confirmed, $rental->status);
        $this->assertEquals(PaymentStatus::Paid, $rental->payment->status);
    }

    public function test_account_lockout_after_five_failed_login_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'lockout@example.com',
            'password' => bcrypt('correct-password'),
            'failed_login_attempts' => 0,
            'account_status' => AccountStatus::Active,
        ]);

        // 5 consecutive failed login attempts
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'lockout@example.com',
                'password' => 'wrong-password',
            ]);
            $response->assertStatus(401);
        }

        $user->refresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());

        // 6th attempt while locked returns HTTP 423
        $response6 = $this->postJson('/api/v1/login', [
            'email' => 'lockout@example.com',
            'password' => 'wrong-password',
        ]);
        $response6->assertStatus(423);
    }

    public function test_complete_expired_rentals_scheduled_job(): void
    {
        $customer = User::factory()->create();
        $car = Car::factory()->create(['is_available' => true]);

        $startDate = Carbon::yesterday()->subDays(5);
        $endDate = Carbon::yesterday();

        $pastRental = Rental::create([
            'reference_number' => 'RNT-20260101-99999',
            'customer_id' => $customer->id,
            'car_id' => $car->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'pickup_location' => 'Lokasi Penjemputan Test',
            'return_location' => 'Lokasi Pengembalian Test',
            'total_cost_idr' => 1000000,
            'status' => RentalStatus::Active,
        ]);

        $job = new CompleteExpiredRentals();
        $job->handle();

        $pastRental->refresh();
        $this->assertEquals(RentalStatus::Completed, $pastRental->status);

        /** @var AvailabilityService $availabilityService */
        $availabilityService = app(AvailabilityService::class);
        $this->assertTrue($availabilityService->isAvailable($car->id, $startDate, $endDate));
    }

    public function test_expire_unpaid_payments_scheduled_job(): void
    {
        $customer = User::factory()->create();
        $car = Car::factory()->create(['is_available' => true]);

        $startDate = Carbon::tomorrow();
        $endDate = Carbon::tomorrow()->addDays(2);

        $rental = Rental::create([
            'reference_number' => 'RNT-20260101-88888',
            'customer_id' => $customer->id,
            'car_id' => $car->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'pickup_location' => 'Lokasi Penjemputan Test',
            'return_location' => 'Lokasi Pengembalian Test',
            'total_cost_idr' => 500000,
            'status' => RentalStatus::Pending,
        ]);

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'amount_idr' => 500000,
            'status' => PaymentStatus::Unpaid,
            'expires_at' => Carbon::now()->subMinutes(10),
        ]);

        $job = new ExpireUnpaidPayments();
        $job->handle();

        $payment->refresh();
        $rental->refresh();

        $this->assertEquals(PaymentStatus::Expired, $payment->status);
        $this->assertEquals(RentalStatus::Expired, $rental->status);

        /** @var AvailabilityService $availabilityService */
        $availabilityService = app(AvailabilityService::class);
        $this->assertTrue($availabilityService->isAvailable($car->id, $startDate, $endDate));
    }
}
