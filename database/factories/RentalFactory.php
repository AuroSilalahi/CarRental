<?php

namespace Database\Factories;

use App\Enums\RentalStatus;
use App\Models\Car;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    protected $model = Rental::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');
        $end   = fake()->dateTimeBetween($start, '+2 months');

        return [
            'reference_number' => 'RNT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'customer_id'      => User::factory(),
            'car_id'           => Car::factory(),
            'start_date'       => $start,
            'end_date'         => $end,
            'pickup_location'  => fake()->address(),
            'return_location'  => fake()->address(),
            'total_cost_idr'   => fake()->numberBetween(500_000, 5_000_000),
            'status'           => RentalStatus::Pending,
        ];
    }

    /** Set the rental status to pending. */
    public function pending(): static
    {
        return $this->state(['status' => RentalStatus::Pending]);
    }

    /** Set the rental status to confirmed. */
    public function confirmed(): static
    {
        return $this->state(['status' => RentalStatus::Confirmed]);
    }

    /** Set the rental status to active. */
    public function active(): static
    {
        return $this->state(['status' => RentalStatus::Active]);
    }

    /** Set the rental status to completed. */
    public function completed(): static
    {
        return $this->state(['status' => RentalStatus::Completed]);
    }

    /** Set the rental status to cancelled. */
    public function cancelled(): static
    {
        return $this->state(['status' => RentalStatus::Cancelled]);
    }

    /** Set the rental status to expired. */
    public function expired(): static
    {
        return $this->state(['status' => RentalStatus::Expired]);
    }
}
