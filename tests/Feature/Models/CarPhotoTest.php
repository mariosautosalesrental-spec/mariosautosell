<?php

namespace Tests\Feature\Models;

use App\Models\Car;
use App\Models\CarPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarPhotoTest extends TestCase
{
    use RefreshDatabase;

    private function photo(string $path = 'cars/7/front.jpg'): CarPhoto
    {
        return CarPhoto::factory()->for(Car::factory()->inactive()->withoutPhotos()->create())->create(['file_path' => $path]);
    }

    public function test_conversion_paths_sit_next_to_the_original(): void
    {
        $photo = $this->photo('cars/7/front.jpg');

        $this->assertSame('cars/7/thumb/front.webp', $photo->conversionPath('thumb'));
        $this->assertSame('cars/7/large/front.webp', $photo->conversionPath('large'));
    }

    public function test_photo_urls_fall_back_to_original_when_conversion_missing(): void
    {
        Storage::fake('public');
        $photo = $this->photo();

        $this->assertSame($photo->url(), $photo->thumbUrl());
        $this->assertSame($photo->url(), $photo->largeUrl());
        $this->assertStringEndsWith('cars/7/front.jpg', $photo->url());
    }

    public function test_urls_use_conversions_when_they_exist(): void
    {
        Storage::fake('public');
        $photo = $this->photo();
        Storage::disk('public')->put('cars/7/thumb/front.webp', 'x');
        Storage::disk('public')->put('cars/7/large/front.webp', 'x');

        $this->assertStringEndsWith('cars/7/thumb/front.webp', $photo->thumbUrl());
        $this->assertStringEndsWith('cars/7/large/front.webp', $photo->largeUrl());
    }

    public function test_positive_conversion_lookup_is_cached(): void
    {
        Storage::fake('public');
        $photo = $this->photo();
        Storage::disk('public')->put('cars/7/thumb/front.webp', 'x');
        $photo->thumbUrl();

        Storage::disk('public')->delete('cars/7/thumb/front.webp');

        $this->assertStringEndsWith('cars/7/thumb/front.webp', $photo->thumbUrl());
    }

    public function test_photo_belongs_to_car(): void
    {
        $photo = $this->photo();

        $this->assertInstanceOf(Car::class, $photo->car);
    }
}
