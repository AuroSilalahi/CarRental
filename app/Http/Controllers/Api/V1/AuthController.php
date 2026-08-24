<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    /**
     * Register a new customer account.
     *
     * POST /api/v1/register
     *
     * Requirements: 5.1, 5.2
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'password'       => Hash::make($request->password),
            'address'        => $request->address,
            'city'           => $request->city,
            'province'       => $request->province,
            'account_status' => AccountStatus::Active,
        ]);

        // Dispatch verification email via queue
        $verificationUrl = $this->buildVerificationUrl($user);
        Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationUrl));

        return response()->json([
            'status'  => 'success',
            'message' => 'Registrasi berhasil. Silakan cek email Anda untuk verifikasi.',
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Verify the user's email address via signed URL.
     *
     * GET /api/v1/email/verify/{id}/{hash}
     *
     * Requirements: 5.3, 5.4
     */
    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        // Validate URL signature
        if (! $request->hasValidSignature()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Link verifikasi telah kedaluwarsa atau tidak valid. Silakan minta link baru.',
                'data'    => null,
            ], 422);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pengguna tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        // Verify hash matches the user's email
        if (! hash_equals(sha1($user->email), $hash)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Link verifikasi tidak valid.',
                'data'    => null,
            ], 422);
        }

        // Already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Email Anda sudah terverifikasi sebelumnya.',
                'data'    => null,
            ], 200);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'status'  => 'success',
            'message' => 'Email berhasil diverifikasi. Anda sekarang dapat melakukan pemesanan.',
            'data'    => null,
        ], 200);
    }

    /**
     * Resend the verification email.
     *
     * POST /api/v1/email/resend
     *
     * Requirements: 5.4
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Return success regardless of whether the email exists (security: prevent enumeration)
        if (! $user) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Jika email terdaftar, link verifikasi akan segera dikirim.',
                'data'    => null,
            ], 200);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Email Anda sudah terverifikasi.',
                'data'    => null,
            ], 200);
        }

        $verificationUrl = $this->buildVerificationUrl($user);
        Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationUrl));

        return response()->json([
            'status'  => 'success',
            'message' => 'Link verifikasi baru telah dikirim ke email Anda.',
            'data'    => null,
        ], 200);
    }

    /**
     * Authenticate a customer and issue a Sanctum token.
     *
     * POST /api/v1/login
     *
     * Requirements: 5.6, 5.7, 5.8
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // If account is currently locked, reject immediately with 423
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $minutesLeft = (int) ceil(Carbon::now()->diffInSeconds($user->locked_until) / 60);

            return response()->json([
                'status'  => 'error',
                'message' => "Akun Anda terkunci sementara karena terlalu banyak percobaan login yang gagal. "
                    . "Silakan coba lagi dalam {$minutesLeft} menit.",
                'data'    => null,
            ], 423);
        }

        // Check credentials — generic error (do NOT reveal whether email or password is wrong)
        if (! $user || ! Hash::check($request->password, $user->password)) {
            if ($user) {
                $attempts = $user->failed_login_attempts + 1;
                $updateData = ['failed_login_attempts' => $attempts];

                // Lock the account after reaching 5 consecutive failures
                if ($attempts >= 5) {
                    $updateData['locked_until'] = Carbon::now()->addMinutes(15);
                }

                $user->update($updateData);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Kredensial yang Anda masukkan tidak valid.',
                'data'    => null,
            ], 401);
        }

        // Check if account is deactivated
        if ($user->account_status === AccountStatus::Deactivated) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
                'data'    => null,
            ], 403);
        }

        // Credentials are valid — reset lockout counters and issue token
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil.',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'user'       => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ],
        ], 200);
    }

    /**
     * Revoke the authenticated user's current access token.
     *
     * POST /api/v1/logout
     *
     * Requirements: 5.7
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil.',
            'data'    => null,
        ], 200);
    }

    /**
     * Build a signed temporary URL for email verification, valid for 24 hours.
     */
    private function buildVerificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'api.v1.email.verify',
            Carbon::now()->addHours(24),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }
}
