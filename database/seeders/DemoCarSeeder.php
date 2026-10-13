<?php

namespace Database\Seeders;

use App\Models\BodyType;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\Category;
use App\Models\Drivetrain;
use App\Models\Transmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Sample lot for local development only (never runs in production). Uses the
 * photos extracted from the mockup; the real photos are uploaded in the admin.
 */
class DemoCarSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $this->call([BodyTypeSeeder::class, TransmissionSeeder::class, DrivetrainSeeder::class]);

        foreach (['Chevrolet', 'Fiat', 'Jeep', 'Volkswagen'] as $i => $name) {
            Category::firstOrCreate(['name' => $name], ['sort_order' => $i + 1]);
        }

        foreach ($this->cars() as $data) {
            $this->seedCar($data);
        }
    }

    private function seedCar(array $data): void
    {
        $car = Car::query()->where('title', $data['title'])->where('trim', $data['trim'])->first();

        if ($car !== null) {
            return;
        }

        $car = Car::create([
            'category_id' => Category::where('name', $data['make'])->value('id'),
            'body_type_id' => BodyType::where('slug', $data['body'])->value('id'),
            'transmission_id' => Transmission::where('slug', $data['transmission'])->value('id'),
            'drivetrain_id' => Drivetrain::where('slug', $data['drivetrain'])->value('id'),
            'title' => $data['title'],
            'trim' => $data['trim'],
            'price' => $data['price'],
            'mileage' => $data['mileage'],
            'color' => $data['color'],
            'description' => $data['description'],
            'is_active' => $data['status'] === 'on_site',
            'price_drop' => $data['price_drop'] ?? false,
        ]);

        foreach ($data['photos'] as $order => $photo) {
            $path = "cars/{$car->id}/{$photo}.jpg";
            CarPhoto::disk()->put($path, File::get(database_path("seeders/demo-photos/{$photo}.jpg")));
            $car->photos()->create(['file_path' => $path, 'sort_order' => $order]);
        }

        if ($data['status'] === 'sold') {
            $car->markAsSold(now()->subDays(10), $data['price'] - 500);
        }
    }

    /** @return list<array<string, mixed>> */
    private function cars(): array
    {
        return [
            ['title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4', 'make' => 'Jeep', 'body' => 'suv', 'transmission' => 'automatic', 'drivetrain' => '4wd', 'price' => 17995, 'mileage' => 48200, 'color' => 'White', 'description' => 'One owner, clean history, leather seats and a new set of tires.', 'status' => 'on_site', 'photos' => ['compassW1', 'compassW2']],
            ['title' => '2018 Jeep Compass', 'trim' => 'Sport', 'make' => 'Jeep', 'body' => 'suv', 'transmission' => 'automatic', 'drivetrain' => 'fwd', 'price' => 14995, 'mileage' => 61500, 'color' => 'Gray', 'description' => 'Great on gas, backup camera and Bluetooth.', 'status' => 'on_site', 'photos' => ['compassGray']],
            ['title' => '2017 Jeep Renegade', 'trim' => 'Latitude', 'make' => 'Jeep', 'body' => 'suv', 'transmission' => 'automatic', 'drivetrain' => '4wd', 'price' => 12995, 'mileage' => 84300, 'color' => 'Orange', 'description' => 'Fun to drive and ready for the winter.', 'status' => 'on_site', 'price_drop' => true, 'photos' => ['renegade']],
            ['title' => '2020 Fiat Toro', 'trim' => 'Freedom', 'make' => 'Fiat', 'body' => 'truck', 'transmission' => 'automatic', 'drivetrain' => 'fwd', 'price' => 21995, 'mileage' => 32100, 'color' => 'Red', 'description' => 'Compact pickup with a roomy cabin.', 'status' => 'on_site', 'photos' => ['toro']],
            ['title' => '2016 Chevrolet Onix', 'trim' => 'LT', 'make' => 'Chevrolet', 'body' => 'car', 'transmission' => 'manual', 'drivetrain' => 'fwd', 'price' => 8995, 'mileage' => 96800, 'color' => 'White', 'description' => 'Economical commuter with a manual gearbox.', 'status' => 'on_site', 'photos' => ['onixW']],
            ['title' => '2015 Chevrolet Onix', 'trim' => 'LTZ', 'make' => 'Chevrolet', 'body' => 'car', 'transmission' => 'automatic', 'drivetrain' => 'fwd', 'price' => 7995, 'mileage' => 112400, 'color' => 'Silver', 'description' => 'Hidden from the site while we finish the inspection.', 'status' => 'hidden', 'photos' => ['onixS']],
            ['title' => '2014 Volkswagen Fox', 'trim' => 'Connect', 'make' => 'Volkswagen', 'body' => 'car', 'transmission' => 'manual', 'drivetrain' => 'fwd', 'price' => 6995, 'mileage' => 129900, 'color' => 'Black', 'description' => 'Sold example for the dashboard.', 'status' => 'sold', 'photos' => ['fox']],
        ];
    }
}
