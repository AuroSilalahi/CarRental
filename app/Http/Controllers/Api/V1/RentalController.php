<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CarNotAvailableException;
use App\Exceptions\EmailNotVerifiedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateRentalRequest;
use App\Http\Resources\RentalResource;
use App\Jobs\SendBookingConfirmationEmail;
use App\Models\Rental;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentalService
    ) {}

    /**
     * Create a new rental booking for the authenticated customer.
     *
     * POST /api/v1/rentals
     *
     * Requirements: 7.1, 7.2, 7.4, 7.5, 7.6, 7.7
     */
    public function store(CreateRentalRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $rental = $this->rentalService->createBooking($user, $request->validated());
        } catch (EmailNotVerifiedException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'data'    => null,
            ], 403);
        } catch (CarNotAvailableException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'data'    => null,
            ], 422);
        }

        // Dispatch booking confirmation email to queue
        SendBookingConfirmationEmail::dispatch($rental->load('car', 'customer'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Pemesanan berhasil dibuat. Silakan selesaikan pembayaran dalam 24 jam.',
            'data'    => new RentalResource($rental->load('car')),
        ], 201);
    }

    /**
     * List all rentals belonging to the authenticated customer.
     *
     * GET /api/v1/rentals
     *
     * Requirements: 9.1, 9.2, 9.4
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $rentals = Rental::where('customer_id', $user->id)
            ->with('car')
            ->latest()
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar rental berhasil diambil.',
            'data'    => RentalResource::collection($rentals),
        ], 200);
    }

    /**
     * Return detail of a single rental.
     *
     * GET /api/v1/rentals/{id}
     *
     * Only the rental owner may access this.
     *
     * Requirements: 9.1, 9.2, 9.4
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $rental = Rental::with('car')->find($id);

        if (! $rental) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Rental tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        // Authorization: only the owner may view their rental
        if ($rental->customer_id !== $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki akses ke rental ini.',
                'data'    => null,
            ], 403);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail rental berhasil diambil.',
            'data'    => new RentalResource($rental),
        ], 200);
    }
}
