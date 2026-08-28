<?php

namespace App\Services;

use App\Enums\RentalStatus;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityService
{
    /**
     * The rental statuses that count as active conflicts.
     */
    private const CONFLICT_STATUSES = [
        RentalStatus::Pending,
        RentalStatus::Confirmed,
        RentalStatus::Active,
    ];

    /**
     * Determine whether a car is available for the given date range.
     *
     * Returns true if there are no conflicting rentals for the car in
     * the period [$start, $end). An existing rental conflicts when:
     *   existing.start_date < $end  AND  existing.end_date > $start
     *
     * Only rentals with status pending, confirmed, or active count as
     * conflicts. Pass $excludeRentalId to ignore a specific rental
     * (useful when editing an existing booking).
     *
     * @param  int          $carId
     * @param  Carbon       $start
     * @param  Carbon       $end
     * @param  int|null     $excludeRentalId
     * @return bool
     */
    public function isAvailable(
        int $carId,
        Carbon $start,
        Carbon $end,
        ?int $excludeRentalId = null
    ): bool {
        $car = \App\Models\Car::find($carId);
        if ($car && ! $car->is_available) {
            return false;
        }

        return $this->buildConflictQuery($carId, $start, $end, $excludeRentalId)
            ->doesntExist();
    }

    /**
     * Return all rentals that conflict with the given car and date range.
     *
     * Uses the same overlap condition and status filter as isAvailable().
     *
     * @param  int        $carId
     * @param  Carbon     $start
     * @param  Carbon     $end
     * @return Collection<int, Rental>
     */
    public function getConflictingRentals(
        int $carId,
        Carbon $start,
        Carbon $end
    ): Collection {
        return $this->buildConflictQuery($carId, $start, $end)
            ->get();
    }

    /**
     * Build the base query for conflicting rentals.
     *
     * Overlap condition (standard interval intersection):
     *   existing.start_date < $end  AND  existing.end_date > $start
     *
     * @param  int      $carId
     * @param  Carbon   $start
     * @param  Carbon   $end
     * @param  int|null $excludeRentalId
     * @return \Illuminate\Database\Eloquent\Builder<Rental>
     */
    private function buildConflictQuery(
        int $carId,
        Carbon $start,
        Carbon $end,
        ?int $excludeRentalId = null
    ) {
        $query = Rental::where('car_id', $carId)
            ->whereIn('status', array_map(
                fn (RentalStatus $s) => $s->value,
                self::CONFLICT_STATUSES
            ))
            ->whereDate('start_date', '<', $end->toDateString())
            ->whereDate('end_date', '>', $start->toDateString());

        if ($excludeRentalId !== null) {
            $query->where('id', '!=', $excludeRentalId);
        }

        return $query;
    }
}
