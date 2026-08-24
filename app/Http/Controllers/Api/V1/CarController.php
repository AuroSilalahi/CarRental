<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService
    ) {}

    /**
     * Return a list of all cars with optional filters.
     *
     * GET /api/v1/cars
     *
     * Filters: type, brand, passenger_capacity, availability (boolean),
     * start_date, end_date (use AvailabilityService when date filters provided)
     *
     * Requirements: 6.2, 6.3, 6.5, 6.6
     */
    public function index(Request $request): JsonResponse
    {
        $query = Car::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by brand
        if ($request->filled('brand')) {
            $query->where('brand', 'like', '%' . $request->brand . '%');
        }

        // Filter by passenger_capacity
        if ($request->filled('passenger_capacity')) {
            $query->where('passenger_capacity', '>=', (int) $request->passenger_capacity);
        }

        // Filter by availability (boolean flag)
        if ($request->filled('availability')) {
            $isAvailable = filter_var($request->availability, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_available', $isAvailable);
        }

        $cars = $query->get();

        // If date range is provided, filter cars by rental availability
        if ($request->filled('start_date') && $request->filled('end_date')) {
            try {
                $start = Carbon::parse($request->start_date)->startOfDay();
                $end   = Carbon::parse($request->end_date)->startOfDay();

                $cars = $cars->filter(function (Car $car) use ($start, $end) {
                    return $this->availabilityService->isAvailable($car->id, $start, $end);
                });
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format tanggal tidak valid.',
                    'data'    => null,
                ], 422);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar kendaraan berhasil diambil.',
            'data'    => CarResource::collection($cars),
        ], 200);
    }

    /**
     * Return a single car detail.
     *
     * GET /api/v1/cars/{id}
     *
     * Requirements: 6.2, 6.3
     */
    public function show(int $id): JsonResponse
    {
        $car = Car::find($id);

        if (! $car) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kendaraan tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail kendaraan berhasil diambil.',
            'data'    => new CarResource($car),
        ], 200);
    }
}
