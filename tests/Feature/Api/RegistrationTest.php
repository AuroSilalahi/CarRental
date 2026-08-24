<?php

namespace Tests\Feature\Api;

use App\Enums\AccountStatus;
use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for POST /api/v1/register
 *
 * Validates: Requirements 5.1, 5.2
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** Valid payload used as the baseline for most tests */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'     => 'Budi Santoso',
            'email'    => 'budi@example.com',
            'phone'    => '081234567890',
            'password' => 'password123',
            'address'  => 'Jl. Merdeka No. 1',
            'city'     => 'Medan',
            'province' => 'Sumatera Utara',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.1, 5.2 — valid registration creates user and queues email
     */
    #[Test]
    public function it_registers_a_new_customer_successfully(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/register', $this->validPayload());

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'status', 'message',
                     'data' => ['id', 'name', 'email'],
                 ])
                 ->assertJson([
                     'status' => 'success',
                     'data'   => [
                         'name'  => 'Budi Santoso',
                         'email' => 'budi@example.com',
                     ],
                 ]);
    }

    /**
     * Requirement 5.2 — account is created with account_status = active and
     * email_verified_at = null (unverified)
     */
    #[Test]
    public function newly_registered_user_has_active_status_and_unverified_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/register', $this->validPayload());

        $user = User::where('email', 'budi@example.com')->firstOrFail();

        $this->assertSame(AccountStatus::Active, $user->account_status);
        $this->assertNull($user->email_verified_at);
    }

    /**
     * Requirement 5.2 — verification email is dispatched to the queue
     */
    #[Test]
    public function it_queues_a_verification_email_after_registration(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/register', $this->validPayload());

        Mail::assertQueued(EmailVerificationMail::class, function (EmailVerificationMail $mail) {
            return $mail->hasTo('budi@example.com');
        });
    }

    /**
     * Requirement 5.2 — verification URL is signed and expires after 24 hours
     */
    #[Test]
    public function verification_email_contains_a_signed_url_expiring_in_24h(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/register', $this->validPayload());

        Mail::assertQueued(EmailVerificationMail::class, function (EmailVerificationMail $mail) {
            $url = $mail->verificationUrl;

            // URL must contain the api.v1.email.verify route path
            $this->assertStringContainsString('/api/v1/email/verify/', $url);
            // URL must contain a signature parameter
            $this->assertStringContainsString('signature=', $url);
            // URL must contain an expiry parameter
            $this->assertStringContainsString('expires=', $url);

            // The "expires" timestamp should be approximately now + 24 hours
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $expires = (int) $query['expires'];
            $this->assertGreaterThan(now()->addHours(23)->timestamp, $expires);
            $this->assertLessThan(now()->addHours(25)->timestamp, $expires);

            return true;
        });
    }

    // -------------------------------------------------------------------------
    // Response envelope
    // -------------------------------------------------------------------------

    /**
     * Property 21 — response envelope must contain status/message/data
     */
    #[Test]
    public function response_follows_json_envelope_format(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/register', $this->validPayload());

        $response->assertJsonStructure(['status', 'message', 'data']);
        $this->assertContains($response->json('status'), ['success', 'error']);
        $this->assertIsString($response->json('message'));
    }

    // -------------------------------------------------------------------------
    // Validation errors — Requirement 5.1
    // -------------------------------------------------------------------------

    /**
     * Property 23 — invalid fields must return HTTP 422 with field-level errors
     */
    #[Test]
    public function it_returns_422_with_field_errors_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/register', []);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $data = $response->json('data');
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('phone', $data);
        $this->assertArrayHasKey('password', $data);
        $this->assertArrayHasKey('address', $data);
        $this->assertArrayHasKey('city', $data);
        $this->assertArrayHasKey('province', $data);
    }

    /**
     * Requirement 5.1 — duplicate email is rejected
     */
    #[Test]
    public function it_rejects_registration_with_duplicate_email(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/register', $this->validPayload([
            'email' => 'existing@example.com',
        ]));

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('email', $response->json('data'));
    }

    /**
     * Requirement 5.1 — password must be 8–64 characters
     */
    #[Test]
    public function it_rejects_password_shorter_than_8_characters(): void
    {
        $response = $this->postJson('/api/v1/register', $this->validPayload([
            'password' => 'short',
        ]));

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('password', $response->json('data'));
    }

    /**
     * Requirement 5.1 — password must not exceed 64 characters
     */
    #[Test]
    public function it_rejects_password_longer_than_64_characters(): void
    {
        $response = $this->postJson('/api/v1/register', $this->validPayload([
            'password' => str_repeat('a', 65),
        ]));

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('password', $response->json('data'));
    }

    /**
     * Requirement 5.1 — invalid email format is rejected
     */
    #[Test]
    public function it_rejects_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/register', $this->validPayload([
            'email' => 'not-an-email',
        ]));

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('email', $response->json('data'));
    }

    /**
     * Requirement 5.1 — Indonesian phone format validation
     *
     * @param  string  $phone
     */
    #[Test]
    #[DataProvider('invalidPhoneProvider')]
    public function it_rejects_invalid_phone_formats(string $phone): void
    {
        $response = $this->postJson('/api/v1/register', $this->validPayload(['phone' => $phone]));

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('phone', $response->json('data'));
    }

    /** @return array<string, array{0: string}> */
    public static function invalidPhoneProvider(): array
    {
        return [
            'too short'          => ['0812'],
            'wrong prefix'       => ['091234567890'],
            'letters'            => ['081abcdefgh'],
            'plus not 62'        => ['+63812345678'],
            'no digits after 62' => ['+62'],
        ];
    }

    /**
     * Requirement 5.1 — valid Indonesian phone formats are accepted
     *
     * @param  string  $phone
     */
    #[Test]
    #[DataProvider('validPhoneProvider')]
    public function it_accepts_valid_indonesian_phone_formats(string $phone): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/register', $this->validPayload([
            'email' => uniqid('user_', true).'@example.com',
            'phone' => $phone,
        ]));

        $response->assertStatus(201);
    }

    /** @return array<string, array{0: string}> */
    public static function validPhoneProvider(): array
    {
        return [
            '08 prefix 7 digits'  => ['081234567'],      // 08 + 7 = 9 chars total
            '08 prefix 12 digits' => ['081234567890'],   // 08 + 10 = 12 chars total (valid: 7–12 after 08 prefix)
            '+62 prefix short'    => ['+621234567'],     // +62 + 7
            '+62 prefix long'     => ['+6281234567890'], // +62 + 10
        ];
    }
}
