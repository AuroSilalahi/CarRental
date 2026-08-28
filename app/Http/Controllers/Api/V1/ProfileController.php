<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IdentityDocumentFileType;
use App\Enums\IdentityDocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\UploadIdentityDocumentRequest;
use App\Models\IdentityDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Return the authenticated customer's profile data.
     *
     * GET /api/v1/profile
     *
     * Requirements: 9.1, 9.2
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diambil.',
            'data'    => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'address'           => $user->address,
                'city'              => $user->city,
                'province'          => $user->province,
                'account_status'    => $user->account_status?->value,
                'email_verified_at' => $user->email_verified_at,
                'created_at'        => $user->created_at,
            ],
        ], 200);
    }

    /**
     * Update the authenticated customer's profile data (partial update).
     *
     * PUT /api/v1/profile
     *
     * Requirements: 9.1, 9.2
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Only update fields that were explicitly provided in the request
        $user->fill($request->only(['name', 'phone', 'address', 'city', 'province']));
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data'    => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'address'           => $user->address,
                'city'              => $user->city,
                'province'          => $user->province,
                'account_status'    => $user->account_status?->value,
                'email_verified_at' => $user->email_verified_at,
                'created_at'        => $user->created_at,
            ],
        ], 200);
    }

    /**
     * Upload an identity document (KTP) for the authenticated customer.
     *
     * POST /api/v1/profile/identity
     *
     * Requirements: 5.9, 5.10
     */
    public function uploadIdentity(UploadIdentityDocumentRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $file = $request->file('file');

        // Determine file type enum from actual MIME type
        $mime = $file->getMimeType();
        $fileType = match ($mime) {
            'image/jpeg' => IdentityDocumentFileType::Jpeg,
            'image/png'  => IdentityDocumentFileType::Png,
            'application/pdf' => IdentityDocumentFileType::Pdf,
            default => IdentityDocumentFileType::Jpeg,
        };

        // Store file securely under identity-documents/{user_id}/
        $disk = config('filesystems.default', 'local');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = "ktp-user-{$user->id}-" . time() . ".{$extension}";
        $storedPath = Storage::disk($disk)->putFileAs($directory, $file, $filename);

        // Create IdentityDocument record with pending_review status
        $identityDocument = IdentityDocument::create([
            'customer_id' => $user->id,
            'file_path'   => $storedPath,
            'file_type'   => $fileType,
            'status'      => IdentityDocumentStatus::PendingReview,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Dokumen identitas berhasil diunggah dan sedang menunggu peninjauan.',
            'data'    => [
                'id'         => $identityDocument->id,
                'file_type'  => $identityDocument->file_type->value,
                'status'     => $identityDocument->status->value,
                'created_at' => $identityDocument->created_at,
            ],
        ], 201);
    }
}
