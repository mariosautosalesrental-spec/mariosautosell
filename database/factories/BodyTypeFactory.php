<?php

namespace Database\Factories;

use App\Models\BodyType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BodyType>
 */
class BodyTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->bothify('BodyType ??##');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
