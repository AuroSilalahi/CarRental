<?php

namespace Tests\Feature\Integration;

use App\Enums\AccountStatus;
use App\Enums\IdentityDocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Mail\BookingConfirmationMail;
use App\Mail\EmailVerificationMail;
use App\Mail\IdentityApprovedMail;
use App\Mail\PaymentConfirmationMail;
use App\Models\Car;
use App\Models\IdentityDocument;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FullWorkflowEmailAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_admin_and_customer_workflow_with_notifications(): void
    {
        Mail::fake();
        Storage::fake('s3');
        Storage::fake('public');

        // 1. Create Admin Account
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@carrental.com',
            'password' => Hash::make('password123'),
            'phone' => '081234567890',
            'account_status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ]);
        $this->assertDatabaseHas('users', ['email' => 'admin@carrental.com']);

        // 2. Customer Registration via API / Web
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Auro Test Customer',
            'email' => 'pasaribu766hi@gmail.com',
            'password' => 'Password123!',
            'phone' => '081299998888',
            'address' => 'Jl. Sudirman No. 12',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
        ]);

        $response->assertStatus(201);
        $customer = User::where('email', 'pasaribu766hi@gmail.com')->first();
        $this->assertNotNull($customer);

        // Verify Email Verification Mail was queued
        Mail::assertQueued(EmailVerificationMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });

        // Simulate user verifying their email
        $customer->markEmailAsVerified();

        // 3. Customer Uploads KYC Identity Document (KTP)
        $ktpPhoto = UploadedFile::fake()->create('ktp_auro.jpg', 500, 'image/jpeg');
        $uploadResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/profile/identity', [
                'file' => $ktpPhoto,
            ]);

        $uploadResponse->assertStatus(201);
        $document = IdentityDocument::where('customer_id', $customer->id)->first();
        $this->assertNotNull($document);
        $this->assertEquals(IdentityDocumentStatus::PendingReview, $document->status);
        $this->assertNotNull($document->file_url); // S3 URL helper

        // Simulate Admin Approving Identity Document
        $document->update([
            'status' => IdentityDocumentStatus::Verified,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);
        Mail::to($customer->email)->queue(new IdentityApprovedMail($customer));

        Mail::assertQueued(IdentityApprovedMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });

        // 4. Admin Adds a New Car to the Fleet
        $car = Car::create([
            'brand' => 'Toyota',
            'model' => 'Alphard Hybrid',
            'type' => 'MPV',
            'license_plate' => 'B 7777 VVIP',
            'passenger_capacity' => 7,
            'colour' => 'Hitam Mutiara',
            'year' => 2024,
            'daily_rate_idr' => 2000000,
            'is_available' => true,
            'is_luxury_brand' => true,
            'luxury_multiplier' => 1.5,
            'image_path' => 'cars/alphard.jpg',
        ]);
        $this->assertDatabaseHas('cars', ['license_plate' => 'B 7777 VVIP']);
        $this->assertNotNull($car->image_url); // S3 Car Image helper

        // 5. Customer Books the Car Rental
        $startDate = Carbon::tomorrow()->toDateString();
        $endDate = Carbon::tomorrow()->addDays(2)->toDateString();

        $bookingResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/rentals', [
                'car_id' => $car->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'pickup_location' => 'Bandara Soekarno-Hatta Terminal 3',
                'return_location' => 'Bandara Soekarno-Hatta Terminal 3',
            ]);

        $bookingResponse->assertStatus(201);
        $rentalId = $bookingResponse->json('data.id');

        $rental = Rental::find($rentalId);
        $this->assertNotNull($rental);
        $this->assertEquals(RentalStatus::Pending, $rental->status);

        // Verify Booking Confirmation Email was queued/sent
        Mail::assertQueued(BookingConfirmationMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });

        // 6. Customer Uploads Payment Proof
        $payment = Payment::where('rental_id', $rental->id)->first();
        $this->assertNotNull($payment);

        $payment->update([
            'payment_method' => 'Bank Transfer BCA',
            'proof_path' => 'payments/proof_123.jpg',
            'transaction_reference' => 'TRX-99887766',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
        $this->assertNotNull($payment->proof_url); // Presigned URL helper

        // Admin approves payment and confirms rental
        $rental->update(['status' => RentalStatus::Confirmed]);
        Mail::to($customer->email)->queue(new PaymentConfirmationMail($rental));

        Mail::assertQueued(PaymentConfirmationMail::class, function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });
    }
}
