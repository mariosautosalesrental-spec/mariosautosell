<?php

namespace Database\Factories;

use App\Models\BodyType;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\Category;
use App\Models\Drivetrain;
use App\Models\Transmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cars are created on the site (active, with a price and one photo) so the
 * data respects the publishing rules; use the states for other situations.
 *
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->numberBetween(2008, 2023);

        return [
            'category_id' => Category::factory(),
            'body_type_id' => BodyType::factory(),
            'transmission_id' => Transmission::factory(),
            'drivetrain_id' => Drivetrain::factory(),
            'title' => $year.' '.fake()->randomElement(['Jeep Compass', 'Chevrolet Onix', 'Fiat Toro', 'Volkswagen Fox', 'Jeep Renegade']),
            'trim' => fake()->optional(0.8)->randomElement(['Limited 4x4', 'LT', 'Sport', 'Freedom', 'Base']),
            'price' => fake()->numberBetween(4, 29) * 1000 + 995,
            'mileage' => fake()->numberBetween(12, 140) * 1000,
            'color' => fake()->randomElement(['White', 'Black', 'Gray', 'Silver', 'Red']),
            'engine' => null,
            'description' => fake()->sentence(12),
            'is_active' => true,
            'price_drop' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Car $car): void {
            if ($car->is_active && $car->photos()->doesntExist()) {
                CarPhoto::factory()->for($car)->create(['file_path' => "cars/{$car->id}/photo-1.jpg"]);
            }
        });
    }

    /** Hidden from the site by the store (a draft may have no price or photo). */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Sold: inactive with the sale date and price filled in. */
    public function sold(?\DateTimeInterface $at = null, ?int $price = null): static
    {
        return $this->state(function (array $attributes) use ($at, $price): array {
            return [
                'is_active' => false,
                'sold_at' => $at ?? now()->subDay(),
                'sold_price' => $price ?? $attributes['price'] ?? 10995,
            ];
        });
    }

    public function priceDrop(): static
    {
        return $this->state(['price_drop' => true]);
    }

    /** Make sure the car ends up with exactly this many photos. */
    public function withPhotos(int $count): static
    {
        return $this->afterCreating(function (Car $car) use ($count): void {
            $existing = $car->photos()->count();

            for ($i = $existing; $i < $count; $i++) {
                CarPhoto::factory()->for($car)->create([
                    'file_path' => "cars/{$car->id}/photo-".($i + 1).'.jpg',
                    'sort_order' => $i,
                ]);
            }

            $car->photos()->where('sort_order', '>=', $count)->delete();
            $car->unsetRelation('photos');
        });
    }

    /** No photos at all (only meaningful together with `inactive()`). */
    public function withoutPhotos(): static
    {
        return $this->afterCreating(function (Car $car): void {
            $car->photos()->delete();
            $car->unsetRelation('photos');
        });
    }

    public function withoutPrice(): static
    {
        return $this->state(['price' => null]);
    }
}
