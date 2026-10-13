<?php

namespace Database\Factories;

use App\Models\Transmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transmission>
 */
class TransmissionFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->bothify('Transmission ??##');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
