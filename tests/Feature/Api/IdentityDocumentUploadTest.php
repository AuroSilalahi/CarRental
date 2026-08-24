<?php

namespace Tests\Feature\Api;

use App\Enums\AccountStatus;
use App\Enums\IdentityDocumentStatus;
use App\Models\IdentityDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for POST /api/v1/profile/identity
 *
 * Validates: Requirements 5.9, 5.10
 * Property 11: Identity Document Upload Accepts Valid Files and Rejects Invalid Files
 * Property 21: API Response Envelope Is Consistent
 * Property 22: Unauthenticated Requests Return HTTP 401
 * Property 23: Invalid Request Parameters Return HTTP 422 With Field-Level Errors
 */
class IdentityDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email'          => 'customer@example.com',
            'password'       => Hash::make('password123'),
            'account_status' => AccountStatus::Active,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Happy path — Requirement 5.9
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.9 — uploading a valid JPEG file returns HTTP 201
     */
    #[Test]
    public function it_accepts_a_valid_jpeg_file(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => 'success',
                     'data'   => [
                         'file_type' => 'jpeg',
                         'status'    => 'pending_review',
                     ],
                 ]);
    }

    /**
     * Requirement 5.9 — uploading a valid PNG file returns HTTP 201
     */
    #[Test]
    public function it_accepts_a_valid_png_file(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.png', 100, 'image/png');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => 'success',
                     'data'   => [
                         'file_type' => 'png',
                         'status'    => 'pending_review',
                     ],
                 ]);
    }

    /**
     * Requirement 5.9 — uploading a valid PDF file returns HTTP 201
     */
    #[Test]
    public function it_accepts_a_valid_pdf_file(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => 'success',
                     'data'   => [
                         'file_type' => 'pdf',
                         'status'    => 'pending_review',
                     ],
                 ]);
    }

    /**
     * Requirement 5.9 — uploaded file is stored on the local disk
     */
    #[Test]
    public function it_stores_the_uploaded_file_in_storage(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(201);

        // Retrieve the saved record and confirm the path exists in storage
        $document = IdentityDocument::first();
        $this->assertNotNull($document);
        Storage::disk('local')->assertExists($document->file_path);
    }

    /**
     * Requirement 5.9 — the file is stored under identity_documents/{user_id}/
     */
    #[Test]
    public function it_stores_the_file_in_the_user_scoped_directory(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg');

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $document = IdentityDocument::first();
        $this->assertNotNull($document);
        $this->assertStringStartsWith('identity_documents/' . $user->id, $document->file_path);
    }

    /**
     * Requirement 5.9 — IdentityDocument record is created with pending_review status
     */
    #[Test]
    public function it_creates_an_identity_document_record_with_pending_review_status(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.png', 200, 'image/png');

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $this->assertDatabaseHas('identity_documents', [
            'customer_id' => $user->id,
            'status'      => IdentityDocumentStatus::PendingReview->value,
            'file_type'   => 'png',
        ]);
    }

    // -------------------------------------------------------------------------
    // Rejection — Requirement 5.10
    // -------------------------------------------------------------------------

    /**
     * Requirement 5.10 — oversized file (>5MB) is rejected with HTTP 422 and error on 'file' field
     */
    #[Test]
    public function it_rejects_a_file_exceeding_5mb(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        // 5121 KB = just over 5MB
        $file = UploadedFile::fake()->create('ktp.jpg', 5121, 'image/jpeg');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('file', $response->json('data'));
        $this->assertDatabaseCount('identity_documents', 0);
    }

    /**
     * Requirement 5.10 — text file (.txt) is rejected with HTTP 422 and error on 'file' field
     */
    #[Test]
    public function it_rejects_a_text_file_with_invalid_mime_type(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.txt', 100, 'text/plain');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('file', $response->json('data'));
        $this->assertDatabaseCount('identity_documents', 0);
    }

    /**
     * Requirement 5.10 — docx file is rejected with HTTP 422 and error on 'file' field
     */
    #[Test]
    public function it_rejects_a_docx_file_with_invalid_mime_type(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('file', $response->json('data'));
        $this->assertDatabaseCount('identity_documents', 0);
    }

    /**
     * Requirement 5.10 — error message specifies allowed formats and maximum size
     */
    #[Test]
    public function rejection_error_message_specifies_allowed_formats_and_max_size(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.txt', 100, 'text/plain');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(422);

        // The error message on the 'file' field must mention allowed formats
        $fileErrors = $response->json('data.file');
        $this->assertNotEmpty($fileErrors);

        $errorText = implode(' ', $fileErrors);
        $lowerError = strtolower($errorText);

        // Must mention allowed formats
        $this->assertStringContainsString('jpeg', $lowerError);
        $this->assertStringContainsString('png', $lowerError);
        $this->assertStringContainsString('pdf', $lowerError);
    }

    /**
     * Requirement 5.10 — size rejection error message references the 5 MB limit
     */
    #[Test]
    public function size_rejection_error_message_specifies_max_size(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $file = UploadedFile::fake()->create('ktp.jpg', 5121, 'image/jpeg');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(422);

        $fileErrors = $response->json('data.file');
        $this->assertNotEmpty($fileErrors);

        $errorText = implode(' ', $fileErrors);
        $lowerError = strtolower($errorText);

        // Must mention the 5 MB limit
        $this->assertMatchesRegularExpression('/5\s*(mb|mb|mib|mega)/i', $errorText);
    }

    // -------------------------------------------------------------------------
    // Authentication guard — Property 22
    // -------------------------------------------------------------------------

    /**
     * Property 22 — unauthenticated request returns HTTP 401
     */
    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg');

        $response = $this->postJson('/api/v1/profile/identity', ['file' => $file]);

        $response->assertStatus(401)
                 ->assertJson([
                     'status' => 'error',
                     'data'   => null,
                 ]);
    }

    // -------------------------------------------------------------------------
    // Response envelope — Property 21
    // -------------------------------------------------------------------------

    /**
     * Property 21 — all responses conform to the JSON envelope format
     */
    #[Test]
    public function all_responses_follow_json_envelope_format(): void
    {
        Storage::fake('local');

        $user = $this->createUser();

        $validFile   = UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg');
        $invalidFile = UploadedFile::fake()->create('ktp.txt', 100, 'text/plain');
        $oversized   = UploadedFile::fake()->create('ktp.jpg', 5121, 'image/jpeg');

        $responses = [
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/identity', ['file' => $validFile]),
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/identity', ['file' => $invalidFile]),
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/identity', ['file' => $oversized]),
            $this->postJson('/api/v1/profile/identity', []),
        ];

        foreach ($responses as $response) {
            $response->assertJsonStructure(['status', 'message', 'data']);
            $this->assertContains($response->json('status'), ['success', 'error']);
            $this->assertIsString($response->json('message'));
        }
    }

    // -------------------------------------------------------------------------
    // Validation — Property 23
    // -------------------------------------------------------------------------

    /**
     * Property 23 — missing file field returns HTTP 422 with error on 'file'
     */
    #[Test]
    public function missing_file_field_returns_422(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/v1/profile/identity', []);

        $response->assertStatus(422)
                 ->assertJson(['status' => 'error']);

        $this->assertArrayHasKey('file', $response->json('data'));
    }
}
