<?php

namespace Tests\Feature\Api;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for POST /api/v1/login and POST /api/v1/logout
 *
 * Validates: Requirements 5.6, 5.7, 5.8
 * Property 10: Account Lockout After 5 Consecutive Failed Login Attempts
 * Property 21: API Response Envelope Is Consistent
 * Property 22: Unauthenticated Requests Return HTTP 401
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email'                  => 'customer@example.com',
            'password'               => Hash::make('password123'),
            'account_status'         => AccountStatus::Active,
            'failed_login_attempts'  => 0,
            'locked_until'           => null,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Happy path — Requirement 5.7
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.7 — valid credentials issue a Sanctum token
     */
    #[Test]
    public function it_returns_token_on_valid_credentials(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success'])
                 ->assertJsonStructure([
                     'status', 'message',
                     'data' => ['token', 'token_type', 'user' => ['id', 'name', 'email']],
                 ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame('Bearer', $response->json('data.token_type'));
    }

    /**
     * Requirement 5.7 — a personal access token is persisted in the database
     */
    #[Test]
    public function successful_login_persists_token_in_database(): void
    {
        $user = $this->createUser();

        $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * Requirement 5.8 — successful login resets failed_login_attempts and locked_until
     */
    #[Test]
    public function successful_login_resets_failed_attempts_counter(): void
    {
        $user = $this->createUser(['failed_login_attempts' => 3]);

        $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'password123',
        ]);

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    // -------------------------------------------------------------------------
    // Invalid credentials — Requirement 5.8
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.8 — invalid password returns generic error (HTTP 401)
     */
    #[Test]
    public function it_returns_401_with_generic_error_on_wrong_password(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'error',
                     'data'   => null,
                 ]);

        // Message must NOT mention email or password specifically
        $message = strtolower($response->json('message'));
        $this->assertStringNotContainsString('password', $message);
        $this->assertStringNotContainsString('email', $message);
    }

    /**
     * Requirement 5.8 — non-existent email returns generic error (HTTP 401) — no enumeration
     */
    #[Test]
    public function it_returns_401_with_generic_error_on_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'error',
                     'data'   => null,
                 ]);

        $message = strtolower($response->json('message'));
        $this->assertStringNotContainsString('password', $message);
        $this->assertStringNotContainsString('email', $message);
    }

    /**
     * Requirement 5.8 — each failed attempt increments the counter
     */
    #[Test]
    public function failed_login_increments_failed_attempts_counter(): void
    {
        $user = $this->createUser();

        $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'wrongpassword',
        ]);

        $user->refresh();
        $this->assertSame(1, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    // -------------------------------------------------------------------------
    // Account lockout — Requirement 5.8 / Property 10
    // -------------------------------------------------------------------------

    /**
     * Property 10 — after exactly 5 consecutive failed attempts, account is locked for 15 minutes
     */
    #[Test]
    public function account_is_locked_after_5_consecutive_failed_attempts(): void
    {
        $user = $this->createUser();
        $now  = Carbon::now();

        Carbon::setTestNow($now);

        // Make 5 failed attempts
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email'    => 'customer@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $user->refresh();
        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);

        // locked_until must be at least 15 minutes from now
        $this->assertGreaterThanOrEqual(
            $now->copy()->addMinutes(15)->timestamp,
            $user->locked_until->timestamp
        );

        Carbon::setTestNow();
    }

    /**
     * Property 10 — attempt on locked account returns 423 lockout message (not generic error)
     */
    #[Test]
    public function locked_account_returns_423_with_lockout_message(): void
    {
        $this->createUser([
            'failed_login_attempts' => 5,
            'locked_until'          => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'password123',   // correct password — should still be blocked
        ]);

        $response->assertStatus(423)
                 ->assertJson([
                     'status' => 'error',
                     'data'   => null,
                 ]);

        $this->assertNotEmpty($response->json('message'));
    }

    /**
     * Property 10 — lock expires after 15 minutes; login succeeds again
     */
    #[Test]
    public function locked_account_can_login_after_lockout_period_expires(): void
    {
        Carbon::setTestNow(Carbon::now());

        $this->createUser([
            'failed_login_attempts' => 5,
            'locked_until'          => Carbon::now()->subMinute(),  // already expired
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        Carbon::setTestNow();
    }

    /**
     * Requirement 5.8 — 5th failed attempt is blocked and the 6th attempt while locked returns 423
     */
    #[Test]
    public function sixth_attempt_on_locked_account_returns_423(): void
    {
        $user = $this->createUser(['failed_login_attempts' => 4]);

        // 5th attempt — triggers lock
        $fifth = $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'wrongpassword',
        ]);
        $fifth->assertStatus(401);

        $user->refresh();
        $this->assertNotNull($user->locked_until);

        // 6th attempt — should get lockout message
        $sixth = $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'wrongpassword',
        ]);
        $sixth->assertStatus(423);
    }

    // -------------------------------------------------------------------------
    // Deactivated account — Requirement 2.6
    // -------------------------------------------------------------------------

    /**
     * Deactivated account returns HTTP 403 on login
     */
    #[Test]
    public function deactivated_account_returns_403(): void
    {
        $this->createUser(['account_status' => AccountStatus::Deactivated]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'status' => 'error',
                     'data'   => null,
                 ]);
    }

    // -------------------------------------------------------------------------
    // Logout — Requirement 5.7
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.7 — logout revokes the current access token
     */
    #[Test]
    public function it_revokes_token_on_logout(): void
    {
        $user  = $this->createUser();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->withToken($token)
                         ->postJson('/api/v1/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'data'   => null,
                 ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Property 22 — unauthenticated logout request returns HTTP 401
     */
    #[Test]
    public function unauthenticated_logout_returns_401(): void
    {
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'error',
                     'data'   => null,
                 ]);
    }

    /**
     * After logout, the personal_access_token record is deleted from the database,
     * so the token can no longer authenticate future requests.
     */
    #[Test]
    public function after_logout_the_token_record_is_deleted_from_database(): void
    {
        $user  = $this->createUser();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/logout')->assertStatus(200);

        // Token row must be gone — a fresh request with this token has no DB record to match
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // -------------------------------------------------------------------------
    // Validation errors — Property 23
    // -------------------------------------------------------------------------

    /**
     * Property 23 — missing fields return HTTP 422 with field-level errors
     */
    #[Test]
    public function it_returns_422_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $data = $response->json('data');
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('password', $data);
    }

    /**
     * Property 23 — invalid email format returns HTTP 422
     */
    #[Test]
    public function it_returns_422_on_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email'    => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('email', $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // Response envelope — Property 21
    // -------------------------------------------------------------------------

    /**
     * Property 21 — all login responses conform to the JSON envelope
     */
    #[Test]
    public function all_responses_follow_json_envelope_format(): void
    {
        $this->createUser();

        $responses = [
            $this->postJson('/api/v1/login', ['email' => 'customer@example.com', 'password' => 'password123']),
            $this->postJson('/api/v1/login', ['email' => 'customer@example.com', 'password' => 'wrong']),
            $this->postJson('/api/v1/login', []),
        ];

        foreach ($responses as $response) {
            $response->assertJsonStructure(['status', 'message', 'data']);
            $this->assertContains($response->json('status'), ['success', 'error']);
            $this->assertIsString($response->json('message'));
        }
    }
}
