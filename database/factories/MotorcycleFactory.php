<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Motorcycle>
 */
class MotorcycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand' => $this->faker->randomElement(['Yamaha', 'Honda', 'Ducati', 'Kawasaki', 'BMW', 'Suzuki']),
            'model' => $this->faker->word() . ' ' . $this->faker->numberBetween(300, 1000) . 'R',
            'type' => $this->faker->randomElement(['Sport', 'Cruiser', 'Touring', 'Naked', 'Off-road']),
            'year' => $this->faker->numberBetween(2018, 2024),
            'engine_capacity' => $this->faker->numberBetween(300, 1200),
            'power' => $this->faker->numberBetween(30, 200),
            'price' => $this->faker->numberBetween(5000, 25000),
            'description' => $this->faker->realText(200), // Generates real Russian text
            'image_url' => 'https://placehold.co/600x400/333/orange?text=' . urlencode('Мотоцикл'), // Russian placeholder
            'is_available' => $this->faker->boolean(90),
        ];
    }
}
