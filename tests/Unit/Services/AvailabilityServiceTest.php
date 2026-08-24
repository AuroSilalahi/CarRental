<?php

namespace Tests\Unit\Services;

use App\Enums\RentalStatus;
use App\Models\Car;
use App\Models\Rental;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AvailabilityService.
 *
 * Validates: Requirements 3.3, 6.6, 7.4
 */
class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;
    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AvailabilityService();
        $this->car     = Car::factory()->create();
    }

    // -----------------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------------

    /**
     * Create a rental for the given car (defaults to $this->car) with the
     * given status and dates.
     */
    private function createRental(
        string $startDate,
        string $endDate,
        RentalStatus $status = RentalStatus::Pending,
        ?Car $car = null
    ): Rental {
        $customer = User::factory()->create();

        return Rental::create([
            'reference_number' => 'RNT-TEST-' . uniqid(),
            'customer_id'      => $customer->id,
            'car_id'           => ($car ?? $this->car)->id,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'pickup_location'  => 'Medan',
            'return_location'  => 'Medan',
            'total_cost_idr'   => 500_000,
            'status'           => $status,
        ]);
    }

    // -----------------------------------------------------------------------
    // isAvailable — no existing rentals
    // -----------------------------------------------------------------------

    public function test_is_available_returns_true_when_car_has_no_rentals(): void
    {
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------------
    // isAvailable — direct / full overlap
    // -----------------------------------------------------------------------

    public function test_is_available_returns_false_for_exact_same_range_with_pending_status(): void
    {
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Pending);

        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertFalse($result);
    }

    public function test_is_available_returns_false_for_exact_same_range_with_confirmed_status(): void
    {
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Confirmed);

        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertFalse($result);
    }

    public function test_is_available_returns_false_for_exact_same_range_with_active_status(): void
    {
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Active);

        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertFalse($result);
    }

    // -----------------------------------------------------------------------
    // isAvailable — partial overlaps
    // -----------------------------------------------------------------------

    public function test_is_available_returns_false_when_requested_range_starts_inside_existing(): void
    {
        // Existing: Aug 1 – Aug 10
        $this->createRental('2025-08-01', '2025-08-10', RentalStatus::Confirmed);

        // Requested: Aug 07 – Aug 15 (overlaps tail of existing)
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-07'),
            Carbon::parse('2025-08-15')
        );

        $this->assertFalse($result);
    }

    public function test_is_available_returns_false_when_requested_range_ends_inside_existing(): void
    {
        // Existing: Aug 05 – Aug 15
        $this->createRental('2025-08-05', '2025-08-15', RentalStatus::Confirmed);

        // Requested: Aug 01 – Aug 08 (overlaps head of existing)
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-08')
        );

        $this->assertFalse($result);
    }

    public function test_is_available_returns_false_when_requested_range_fully_contains_existing(): void
    {
        // Existing: Aug 05 – Aug 10
        $this->createRental('2025-08-05', '2025-08-10', RentalStatus::Confirmed);

        // Requested: Aug 01 – Aug 15 (fully wraps existing)
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-15')
        );

        $this->assertFalse($result);
    }

    public function test_is_available_returns_false_when_existing_fully_contains_requested_range(): void
    {
        // Existing: Aug 01 – Aug 15
        $this->createRental('2025-08-01', '2025-08-15', RentalStatus::Confirmed);

        // Requested: Aug 05 – Aug 10 (entirely inside existing)
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-05'),
            Carbon::parse('2025-08-10')
        );

        $this->assertFalse($result);
    }

    // -----------------------------------------------------------------------
    // isAvailable — boundary / adjacent dates (should NOT overlap)
    // -----------------------------------------------------------------------

    public function test_is_available_returns_true_when_existing_rental_ends_on_requested_start_date(): void
    {
        // Existing: Aug 01 – Aug 05 (end_date == requested start_date, no overlap)
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Confirmed);

        // Requested: Aug 05 – Aug 10
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-05'),
            Carbon::parse('2025-08-10')
        );

        $this->assertTrue($result);
    }

    public function test_is_available_returns_true_when_requested_end_date_equals_existing_start_date(): void
    {
        // Existing: Aug 10 – Aug 15 (start_date == requested end_date, no overlap)
        $this->createRental('2025-08-10', '2025-08-15', RentalStatus::Confirmed);

        // Requested: Aug 05 – Aug 10
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-05'),
            Carbon::parse('2025-08-10')
        );

        $this->assertTrue($result);
    }

    public function test_is_available_returns_true_for_completely_non_overlapping_range_before(): void
    {
        // Existing: Aug 10 – Aug 15
        $this->createRental('2025-08-10', '2025-08-15', RentalStatus::Confirmed);

        // Requested: Aug 01 – Aug 05 (entirely before)
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertTrue($result);
    }

    public function test_is_available_returns_true_for_completely_non_overlapping_range_after(): void
    {
        // Existing: Aug 01 – Aug 05
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Confirmed);

        // Requested: Aug 20 – Aug 25 (entirely after)
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-20'),
            Carbon::parse('2025-08-25')
        );

        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------------
    // isAvailable — status filter (completed, cancelled, expired should not block)
    // -----------------------------------------------------------------------

    public function test_is_available_returns_true_when_only_completed_rental_overlaps(): void
    {
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Completed);

        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertTrue($result);
    }

    public function test_is_available_returns_true_when_only_cancelled_rental_overlaps(): void
    {
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Cancelled);

        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertTrue($result);
    }

    public function test_is_available_returns_true_when_only_expired_rental_overlaps(): void
    {
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Expired);

        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------------
    // isAvailable — excludeRentalId parameter
    // -----------------------------------------------------------------------

    public function test_is_available_returns_true_when_sole_conflict_is_excluded_by_id(): void
    {
        $existing = $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Confirmed);

        // Without exclusion — conflict exists
        $this->assertFalse(
            $this->service->isAvailable(
                $this->car->id,
                Carbon::parse('2025-08-01'),
                Carbon::parse('2025-08-05')
            )
        );

        // With exclusion — becomes available
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05'),
            $existing->id
        );

        $this->assertTrue($result);
    }

    public function test_is_available_returns_false_when_excluded_id_does_not_remove_all_conflicts(): void
    {
        $first  = $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Confirmed);
        /* $second = */ $this->createRental('2025-08-03', '2025-08-08', RentalStatus::Pending);

        // Excluding the first still leaves the second as a conflict
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-08'),
            $first->id
        );

        $this->assertFalse($result);
    }

    // -----------------------------------------------------------------------
    // isAvailable — different car isolation
    // -----------------------------------------------------------------------

    public function test_is_available_returns_true_when_conflict_belongs_to_a_different_car(): void
    {
        $otherCar = Car::factory()->create();
        $this->createRental('2025-08-01', '2025-08-05', RentalStatus::Confirmed, $otherCar);

        // $this->car has no rentals at all
        $result = $this->service->isAvailable(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------------
    // getConflictingRentals — basic cases
    // -----------------------------------------------------------------------

    public function test_get_conflicting_rentals_returns_empty_collection_when_no_conflicts(): void
    {
        $conflicts = $this->service->getConflictingRentals(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertCount(0, $conflicts);
    }

    public function test_get_conflicting_rentals_returns_all_overlapping_active_conflict_rentals(): void
    {
        $r1 = $this->createRental('2025-08-01', '2025-08-10', RentalStatus::Pending);
        $r2 = $this->createRental('2025-08-05', '2025-08-15', RentalStatus::Confirmed);
        // Non-overlapping — should not appear
        $this->createRental('2025-08-20', '2025-08-25', RentalStatus::Active);

        $conflicts = $this->service->getConflictingRentals(
            $this->car->id,
            Carbon::parse('2025-08-03'),
            Carbon::parse('2025-08-12')
        );

        $this->assertCount(2, $conflicts);
        $this->assertTrue($conflicts->contains('id', $r1->id));
        $this->assertTrue($conflicts->contains('id', $r2->id));
    }

    public function test_get_conflicting_rentals_excludes_completed_cancelled_and_expired(): void
    {
        $this->createRental('2025-08-01', '2025-08-10', RentalStatus::Completed);
        $this->createRental('2025-08-01', '2025-08-10', RentalStatus::Cancelled);
        $this->createRental('2025-08-01', '2025-08-10', RentalStatus::Expired);

        $conflicts = $this->service->getConflictingRentals(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-10')
        );

        $this->assertCount(0, $conflicts);
    }

    public function test_get_conflicting_rentals_includes_all_three_conflict_statuses(): void
    {
        $pending   = $this->createRental('2025-08-01', '2025-08-10', RentalStatus::Pending);
        $confirmed = $this->createRental('2025-08-01', '2025-08-10', RentalStatus::Confirmed);
        $active    = $this->createRental('2025-08-01', '2025-08-10', RentalStatus::Active);

        $conflicts = $this->service->getConflictingRentals(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-10')
        );

        $this->assertCount(3, $conflicts);
        $this->assertTrue($conflicts->contains('id', $pending->id));
        $this->assertTrue($conflicts->contains('id', $confirmed->id));
        $this->assertTrue($conflicts->contains('id', $active->id));
    }

    // -----------------------------------------------------------------------
    // getConflictingRentals — boundary dates (adjacent should not appear)
    // -----------------------------------------------------------------------

    public function test_get_conflicting_rentals_does_not_include_adjacent_rentals_at_boundaries(): void
    {
        // Ends exactly when requested period starts — no overlap
        $this->createRental('2025-07-25', '2025-08-01', RentalStatus::Confirmed);
        // Starts exactly when requested period ends — no overlap
        $this->createRental('2025-08-05', '2025-08-10', RentalStatus::Confirmed);

        $conflicts = $this->service->getConflictingRentals(
            $this->car->id,
            Carbon::parse('2025-08-01'),
            Carbon::parse('2025-08-05')
        );

        $this->assertCount(0, $conflicts);
    }
}
