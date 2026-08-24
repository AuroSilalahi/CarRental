<?php

namespace Tests\Feature\Api;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for GET /api/v1/profile and PUT /api/v1/profile
 *
 * Validates: Requirements 9.1, 9.2
 * Property 21: API Response Envelope Is Consistent for All Endpoints
 * Property 22: Unauthenticated Requests to Protected Endpoints Return HTTP 401
 * Property 23: Invalid Request Parameters Return HTTP 422 With Field-Level Errors
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name'           => 'Budi Santoso',
            'email'          => 'budi@example.com',
            'password'       => Hash::make('password123'),
            'phone'          => '081234567890',
            'address'        => 'Jl. Sudirman No. 1',
            'city'           => 'Medan',
            'province'       => 'Sumatera Utara',
            'account_status' => AccountStatus::Active,
        ], $overrides));
    }

    // =========================================================================
    // GET /api/v1/profile — Requirement 9.1, 9.2
    // =========================================================================

    /**
     * Requirement 9.1 — GET /api/v1/profile returns all expected profile fields
     */
    #[Test]
    public function get_profile_returns_all_expected_fields(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/v1/profile');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success'])
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'phone',
                         'address',
                         'city',
                         'province',
                         'account_status',
                         'email_verified_at',
                         'created_at',
                     ],
                 ]);

        $data = $response->json('data');
        $this->assertSame($user->id, $data['id']);
        $this->assertSame('Budi Santoso', $data['name']);
        $this->assertSame('budi@example.com', $data['email']);
        $this->assertSame('081234567890', $data['phone']);
        $this->assertSame('Jl. Sudirman No. 1', $data['address']);
        $this->assertSame('Medan', $data['city']);
        $this->assertSame('Sumatera Utara', $data['province']);
        $this->assertSame('active', $data['account_status']);
    }

    /**
     * Requirement 9.1 — GET /api/v1/profile returns data for the authenticated user (not another user)
     */
    #[Test]
    public function get_profile_returns_data_for_authenticated_user_only(): void
    {
        $userA = $this->createUser(['email' => 'usera@example.com', 'name' => 'User A']);
        $userB = $this->createUser(['email' => 'userb@example.com', 'name' => 'User B']);

        $response = $this->actingAs($userA, 'sanctum')
                         ->getJson('/api/v1/profile');

        $response->assertStatus(200);
        $this->assertSame($userA->id, $response->json('data.id'));
        $this->assertSame('User A', $response->json('data.name'));
        $this->assertNotSame($userB->id, $response->json('data.id'));
    }

    /**
     * Property 22 — GET /api/v1/profile unauthenticated returns HTTP 401
     */
    #[Test]
    public function get_profile_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'error',
                     'data'   => null,
                 ]);
    }

    /**
     * Property 21 — GET /api/v1/profile response follows JSON envelope format
     */
    #[Test]
    public function get_profile_response_follows_json_envelope_format(): void
    {
        $user = $this->createUser();

        $authenticated   = $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile');
        $unauthenticated = $this->getJson('/api/v1/profile');

        foreach ([$authenticated, $unauthenticated] as $response) {
            $response->assertJsonStructure(['status', 'message', 'data']);
            $this->assertContains($response->json('status'), ['success', 'error']);
            $this->assertIsString($response->json('message'));
        }
    }

    // =========================================================================
    // PUT /api/v1/profile — Requirement 9.1, 9.2
    // =========================================================================

    /**
     * Requirement 9.1 — PUT /api/v1/profile updates name, phone, address, city, province
     */
    #[Test]
    public function put_profile_updates_all_allowed_fields(): void
    {
        $user = $this->createUser();

        $payload = [
            'name'     => 'Andi Wijaya',
            'phone'    => '+6281234567890',
            'address'  => 'Jl. Diponegoro No. 10',
            'city'     => 'Pematangsiantar',
            'province' => 'Sumatera Utara',
        ];

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'data'   => [
                         'name'     => 'Andi Wijaya',
                         'phone'    => '+6281234567890',
                         'address'  => 'Jl. Diponegoro No. 10',
                         'city'     => 'Pematangsiantar',
                         'province' => 'Sumatera Utara',
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'name'     => 'Andi Wijaya',
            'phone'    => '+6281234567890',
            'address'  => 'Jl. Diponegoro No. 10',
            'city'     => 'Pematangsiantar',
            'province' => 'Sumatera Utara',
        ]);
    }

    /**
     * Requirement 9.1 — PUT /api/v1/profile supports partial update (only one field)
     */
    #[Test]
    public function put_profile_partial_update_only_name(): void
    {
        $user = $this->createUser();

        $originalPhone   = $user->phone;
        $originalAddress = $user->address;
        $originalCity    = $user->city;
        $originalProvince = $user->province;

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', ['name' => 'Siti Rahayu']);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'data'   => ['name' => 'Siti Rahayu'],
                 ]);

        // Other fields must remain unchanged
        $user->refresh();
        $this->assertSame('Siti Rahayu', $user->name);
        $this->assertSame($originalPhone, $user->phone);
        $this->assertSame($originalAddress, $user->address);
        $this->assertSame($originalCity, $user->city);
        $this->assertSame($originalProvince, $user->province);
    }

    /**
     * Requirement 9.1 — PUT /api/v1/profile partial update of city only
     */
    #[Test]
    public function put_profile_partial_update_only_city(): void
    {
        $user = $this->createUser();

        $originalName = $user->name;

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', ['city' => 'Binjai']);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertSame('Binjai', $user->city);
        $this->assertSame($originalName, $user->name);
    }

    /**
     * Requirement 9.2 — PUT /api/v1/profile with invalid phone returns HTTP 422 with field-level error on 'phone'
     */
    #[Test]
    public function put_profile_invalid_phone_returns_422_with_field_error(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', ['phone' => '12345']);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('phone', $response->json('data'));
    }

    /**
     * Requirement 9.1 — PUT /api/v1/profile does NOT allow updating email
     */
    #[Test]
    public function put_profile_does_not_allow_updating_email(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', [
                             'name'  => 'New Name',
                             'email' => 'hacker@example.com',
                         ]);

        $response->assertStatus(200);

        // Email must not be changed
        $user->refresh();
        $this->assertSame('budi@example.com', $user->email);
        $this->assertSame('New Name', $user->name);
    }

    /**
     * Requirement 9.1 — PUT /api/v1/profile does NOT allow updating password
     */
    #[Test]
    public function put_profile_does_not_allow_updating_password(): void
    {
        $user = $this->createUser();
        $originalPasswordHash = $user->password;

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', [
                             'name'     => 'New Name',
                             'password' => 'newpassword123',
                         ]);

        $response->assertStatus(200);

        // Password must not be changed
        $user->refresh();
        $this->assertSame($originalPasswordHash, $user->password);
    }

    /**
     * Property 22 — PUT /api/v1/profile unauthenticated returns HTTP 401
     */
    #[Test]
    public function put_profile_unauthenticated_returns_401(): void
    {
        $response = $this->putJson('/api/v1/profile', ['name' => 'Hacker']);

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'error',
                     'data'   => null,
                 ]);
    }

    /**
     * Property 21 — PUT /api/v1/profile all responses follow JSON envelope format
     */
    #[Test]
    public function put_profile_all_responses_follow_json_envelope_format(): void
    {
        $user = $this->createUser();

        $responses = [
            // Valid update
            $this->actingAs($user, 'sanctum')->putJson('/api/v1/profile', ['name' => 'Valid Name']),
            // Invalid phone
            $this->actingAs($user, 'sanctum')->putJson('/api/v1/profile', ['phone' => 'invalid']),
            // Unauthenticated
            $this->putJson('/api/v1/profile', ['name' => 'Someone']),
        ];

        foreach ($responses as $response) {
            $response->assertJsonStructure(['status', 'message', 'data']);
            $this->assertContains($response->json('status'), ['success', 'error']);
            $this->assertIsString($response->json('message'));
        }
    }

    /**
     * Property 23 — name exceeding max length returns HTTP 422 with field-level error on 'name'
     */
    #[Test]
    public function put_profile_name_too_long_returns_422(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', ['name' => str_repeat('a', 256)]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('name', $response->json('data'));
    }

    /**
     * Property 23 — city exceeding max length returns HTTP 422 with field-level error on 'city'
     */
    #[Test]
    public function put_profile_city_too_long_returns_422(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', ['city' => str_repeat('a', 101)]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('city', $response->json('data'));
    }

    /**
     * Property 23 — province exceeding max length returns HTTP 422 with field-level error on 'province'
     */
    #[Test]
    public function put_profile_province_too_long_returns_422(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', ['province' => str_repeat('a', 101)]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('province', $response->json('data'));
    }

    /**
     * Requirement 9.1 — PUT /api/v1/profile returns updated profile data in response
     */
    #[Test]
    public function put_profile_response_contains_updated_profile_data(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/v1/profile', [
                             'name' => 'Dewi Lestari',
                             'city' => 'Sibolga',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'phone',
                         'address',
                         'city',
                         'province',
                         'account_status',
                         'email_verified_at',
                         'created_at',
                     ],
                 ]);

        $this->assertSame('Dewi Lestari', $response->json('data.name'));
        $this->assertSame('Sibolga', $response->json('data.city'));
        $this->assertSame('budi@example.com', $response->json('data.email'));
    }
}
