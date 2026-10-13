<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarPhoto>
 */
class CarPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'file_path' => 'cars/'.fake()->numberBetween(1, 999).'/'.fake()->unique()->lexify('photo-????????').'.jpg',
            'sort_order' => 0,
        ];
    }
}
