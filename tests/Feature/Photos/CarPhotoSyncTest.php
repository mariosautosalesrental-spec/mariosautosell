<?php

namespace Tests\Feature\Photos;

use App\Models\Car;
use App\Models\CarPhoto;
use App\Services\CarPhotoSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesImages;
use Tests\TestCase;

class CarPhotoSyncTest extends TestCase
{
    use MakesImages;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function upload(string $name, int $width = 1200, int $height = 900): string
    {
        $path = CarPhotoSync::UPLOAD_DIRECTORY."/{$name}";
        Storage::disk('public')->put($path, $this->jpegBytes($width, $height));

        return $path;
    }

    private function bareCar(): Car
    {
        return Car::factory()->inactive()->withoutPhotos()->create();
    }

    private function sync(Car $car, array $paths): void
    {
        app(CarPhotoSync::class)->sync($car, $paths);
    }

    public function test_new_uploads_are_moved_into_the_car_directory_and_saved_in_order(): void
    {
        $car = $this->bareCar();

        $this->sync($car, [$this->upload('b.jpg'), $this->upload('a.jpg')]);

        $photos = $car->photos()->get();
        $this->assertSame(["cars/{$car->id}/b.jpg", "cars/{$car->id}/a.jpg"], $photos->pluck('file_path')->all());
        $this->assertSame([0, 1], $photos->pluck('sort_order')->all());
        Storage::disk('public')->assertExists("cars/{$car->id}/b.jpg");
        Storage::disk('public')->assertMissing('cars/uploads/b.jpg');
    }

    public function test_new_uploads_are_converted(): void
    {
        $car = $this->bareCar();

        $this->sync($car, [$this->upload('front.jpg', 2400, 1800)]);

        Storage::disk('public')->assertExists("cars/{$car->id}/thumb/front.webp");
        Storage::disk('public')->assertExists("cars/{$car->id}/large/front.webp");
    }

    public function test_reordering_photos_persists_sort_order_and_the_first_is_the_cover(): void
    {
        $car = $this->bareCar();
        $this->sync($car, [$this->upload('a.jpg'), $this->upload('b.jpg'), $this->upload('c.jpg')]);
        $paths = $car->photos()->pluck('file_path')->all();

        $this->sync($car->fresh(), [$paths[2], $paths[0], $paths[1]]);

        $ordered = $car->fresh()->photos;
        $this->assertSame([$paths[2], $paths[0], $paths[1]], $ordered->pluck('file_path')->all());
        $this->assertSame([0, 1, 2], $ordered->pluck('sort_order')->all());
        $this->assertSame($paths[2], $car->fresh()->photos->first()->file_path);
    }

    public function test_existing_photos_are_not_duplicated_or_reconverted(): void
    {
        $car = $this->bareCar();
        $this->sync($car, [$this->upload('a.jpg')]);
        $path = $car->photos()->value('file_path');
        $id = $car->photos()->value('id');

        $this->sync($car->fresh(), [$path, $this->upload('b.jpg')]);

        $this->assertSame(2, $car->photos()->count());
        $this->assertSame($id, $car->photos()->first()->id);
    }

    public function test_removing_a_photo_deletes_the_row_and_all_files(): void
    {
        $car = $this->bareCar();
        $this->sync($car, [$this->upload('a.jpg', 2000, 1500), $this->upload('b.jpg', 2000, 1500)]);
        $paths = $car->photos()->pluck('file_path')->all();

        $this->sync($car->fresh(), [$paths[1]]);

        $this->assertSame(1, $car->photos()->count());
        $this->assertDatabaseMissing('car_photos', ['file_path' => $paths[0]]);
        Storage::disk('public')->assertMissing($paths[0]);
        Storage::disk('public')->assertMissing("cars/{$car->id}/thumb/a.webp");
        Storage::disk('public')->assertMissing("cars/{$car->id}/large/a.webp");
        Storage::disk('public')->assertExists($paths[1]);
        Storage::disk('public')->assertExists("cars/{$car->id}/thumb/b.webp");
    }

    public function test_an_empty_list_removes_every_photo(): void
    {
        $car = $this->bareCar();
        $this->sync($car, [$this->upload('a.jpg')]);

        $this->sync($car->fresh(), []);

        $this->assertSame(0, $car->photos()->count());
        $this->assertSame([], Storage::disk('public')->allFiles("cars/{$car->id}"));
    }

    public function test_paths_of_other_cars_cannot_be_claimed(): void
    {
        $owner = $this->bareCar();
        $this->sync($owner, [$this->upload('private.jpg')]);
        $ownerPath = $owner->photos()->value('file_path');
        $thief = $this->bareCar();

        $this->sync($thief, [$ownerPath]);

        $this->assertSame(0, $thief->photos()->count());
        $this->assertSame(1, $owner->photos()->count());
        Storage::disk('public')->assertExists($ownerPath);
    }

    public function test_paths_outside_the_upload_area_are_ignored(): void
    {
        Storage::disk('public')->put('secrets/key.txt', 'secret');
        Storage::disk('public')->put('cars/uploads/nested/deep.jpg', 'x');
        $car = $this->bareCar();

        $this->sync($car, ['secrets/key.txt', '../.env', 'cars/../secrets/key.txt', 'cars/uploads/nested/deep.jpg', '/etc/passwd', '', null, 123, ['x']]);

        $this->assertSame(0, $car->photos()->count());
        Storage::disk('public')->assertExists('secrets/key.txt');
    }

    public function test_missing_upload_file_is_skipped(): void
    {
        $car = $this->bareCar();

        $this->sync($car, ['cars/uploads/never-uploaded.jpg']);

        $this->assertSame(0, $car->photos()->count());
    }

    public function test_duplicate_paths_create_a_single_photo(): void
    {
        $car = $this->bareCar();
        $path = $this->upload('a.jpg');

        $this->sync($car, [$path, $path]);

        $this->assertSame(1, $car->photos()->count());
    }

    public function test_a_name_collision_does_not_overwrite_an_existing_file(): void
    {
        $car = $this->bareCar();
        Storage::disk('public')->put("cars/{$car->id}/same.jpg", 'old content');

        $this->sync($car, [$this->upload('same.jpg')]);

        $this->assertSame('old content', Storage::disk('public')->get("cars/{$car->id}/same.jpg"));
        $this->assertSame(1, $car->photos()->count());
        $this->assertNotSame("cars/{$car->id}/same.jpg", $car->photos()->value('file_path'));
    }

    public function test_deleting_a_car_removes_its_photo_files(): void
    {
        $car = $this->bareCar();
        $this->sync($car, [$this->upload('a.jpg', 2000, 1500)]);
        $this->assertNotEmpty(Storage::disk('public')->allFiles("cars/{$car->id}"));

        $car->delete();

        $this->assertSame([], Storage::disk('public')->allFiles("cars/{$car->id}"));
        $this->assertSame(0, CarPhoto::count());
    }

    public function test_deleting_one_photo_model_removes_its_files(): void
    {
        $car = $this->bareCar();
        $this->sync($car, [$this->upload('a.jpg', 2000, 1500)]);
        $photo = $car->photos()->first();

        $photo->delete();

        Storage::disk('public')->assertMissing($photo->file_path);
        Storage::disk('public')->assertMissing($photo->conversionPath('thumb'));
    }
}
