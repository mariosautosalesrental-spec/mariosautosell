<?php

namespace Database\Factories;

use App\Models\Drivetrain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Drivetrain>
 */
class DrivetrainFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->bothify('Drivetrain ??##');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
