<?php

namespace Database\Factories;

use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand'              => fake()->randomElement(['Toyota', 'Honda', 'Mitsubishi', 'Suzuki', 'Daihatsu']),
            'model'              => fake()->bothify('Model-##??'),
            'type'               => fake()->randomElement(['SUV', 'MPV', 'Sedan', 'Hatchback', 'Pickup']),
            'license_plate'      => strtoupper(fake()->unique()->bothify('BK###??')),
            'passenger_capacity' => fake()->numberBetween(2, 8),
            'colour'             => fake()->safeColorName(),
            'year'               => fake()->numberBetween(1990, (int) date('Y')),
            'daily_rate_idr'     => fake()->numberBetween(200_000, 1_000_000),
            'is_available'       => true,
            'is_luxury_brand'    => false,
            'luxury_multiplier'  => 1.0,
            'image_path'         => null,
        ];
    }

    /**
     * Mark the car as a luxury brand with the given multiplier.
     */
    public function luxury(float $multiplier = 1.5): static
    {
        return $this->state([
            'is_luxury_brand'   => true,
            'luxury_multiplier' => $multiplier,
        ]);
    }
}
