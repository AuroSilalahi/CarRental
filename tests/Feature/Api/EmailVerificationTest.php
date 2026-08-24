<?php

namespace Tests\Feature\Api;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for:
 *   GET  /api/v1/email/verify/{id}/{hash}
 *   POST /api/v1/email/resend
 *
 * Validates: Requirements 5.3, 5.4
 * Also covers Property 9 (email verification round-trip)
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Build a valid (non-expired) signed verification URL for the given user. */
    private function verificationUrl(User $user, ?Carbon $expiresAt = null): string
    {
        return URL::temporarySignedRoute(
            'api.v1.email.verify',
            $expiresAt ?? Carbon::now()->addHours(24),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }

    /** Create an unverified user. */
    private function unverifiedUser(array $overrides = []): User
    {
        return User::factory()->unverified()->create($overrides);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/email/verify/{id}/{hash} — valid link
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.3 — clicking a valid link marks email as verified
     * Property 9 — clicking link before expiry sets email_verified_at non-null
     */
    #[Test]
    public function valid_link_marks_email_as_verified(): void
    {
        $user = $this->unverifiedUser();
        $url  = $this->verificationUrl($user);

        $response = $this->getJson($url);

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'data'    => null,
                 ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /**
     * Requirement 5.3 — already-verified users get a friendly success response
     */
    #[Test]
    public function already_verified_user_gets_success_response(): void
    {
        // Factory default has email_verified_at set
        $user = User::factory()->create();
        $url  = $this->verificationUrl($user);

        $response = $this->getJson($url);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/email/verify/{id}/{hash} — expired link
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.4 — expired link returns error message
     * Property 9 — clicking link after expiry leaves email_verified_at unchanged
     */
    #[Test]
    public function expired_link_returns_error_and_does_not_verify_email(): void
    {
        $user = $this->unverifiedUser();

        // Build a URL that expired 1 second ago
        $expiredUrl = $this->verificationUrl($user, Carbon::now()->subSecond());

        $response = $this->getJson($expiredUrl);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error'])
                 ->assertJsonStructure(['status', 'message', 'data']);

        // email_verified_at must remain null
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/email/verify/{id}/{hash} — tampered link
    // -------------------------------------------------------------------------

    /**
     * Property 21 — tampered/unsigned URL returns 422 envelope response
     */
    #[Test]
    public function tampered_signature_returns_error(): void
    {
        $user     = $this->unverifiedUser();
        $validUrl = $this->verificationUrl($user);

        // Corrupt the signature
        $tamperedUrl = $validUrl.'X';

        $response = $this->getJson($tamperedUrl);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /**
     * Invalid hash in URL returns error without verifying email.
     */
    #[Test]
    public function wrong_hash_returns_error(): void
    {
        $user = $this->unverifiedUser();

        // Build a signed URL but with the wrong hash
        $badUrl = URL::temporarySignedRoute(
            'api.v1.email.verify',
            Carbon::now()->addHours(24),
            [
                'id'   => $user->id,
                'hash' => sha1('wrong@email.com'),
            ]
        );

        $response = $this->getJson($badUrl);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/email/resend
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.4 — resend queues a new verification email
     */
    #[Test]
    public function resend_queues_verification_email_for_unverified_user(): void
    {
        Mail::fake();

        $user = $this->unverifiedUser(['email' => 'unverified@example.com']);

        $response = $this->postJson('/api/v1/email/resend', ['email' => $user->email]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        Mail::assertQueued(EmailVerificationMail::class, function (EmailVerificationMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /**
     * Requirement 5.4 — resend for non-existent email does not reveal it (anti-enumeration)
     */
    #[Test]
    public function resend_returns_success_for_unknown_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/email/resend', ['email' => 'nobody@example.com']);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        Mail::assertNothingQueued();
    }

    /**
     * Requirement 5.4 — resend for already-verified email returns success without re-sending
     */
    #[Test]
    public function resend_returns_success_without_email_when_already_verified(): void
    {
        Mail::fake();

        $user = User::factory()->create(); // verified by default

        $response = $this->postJson('/api/v1/email/resend', ['email' => $user->email]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        Mail::assertNothingQueued();
    }

    /**
     * Property 23 — resend without email field returns 422 with field-level errors
     */
    #[Test]
    public function resend_without_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/email/resend', []);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('email', $response->json('data'));
    }

    /**
     * Property 23 — resend with invalid email format returns 422
     */
    #[Test]
    public function resend_with_invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/v1/email/resend', ['email' => 'not-an-email']);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('email', $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // Response envelope — Property 21
    // -------------------------------------------------------------------------

    /**
     * All verification responses follow the JSON envelope format.
     */
    #[Test]
    public function all_responses_follow_json_envelope(): void
    {
        $user = $this->unverifiedUser();
        $url  = $this->verificationUrl($user);

        $response = $this->getJson($url);

        $response->assertJsonStructure(['status', 'message', 'data']);
        $this->assertContains($response->json('status'), ['success', 'error']);
        $this->assertIsString($response->json('message'));
    }
}
