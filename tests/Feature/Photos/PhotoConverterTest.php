<?php

namespace Tests\Feature\Photos;

use App\Models\Car;
use App\Models\CarPhoto;
use App\Services\PhotoConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesImages;
use Tests\TestCase;

class PhotoConverterTest extends TestCase
{
    use MakesImages;
    use RefreshDatabase;

    private function photo(int $width, int $height, string $name = 'front.jpg'): CarPhoto
    {
        Storage::fake('public');
        $car = Car::factory()->inactive()->withoutPhotos()->create();
        $path = "cars/{$car->id}/{$name}";
        Storage::disk('public')->put($path, $this->jpegBytes($width, $height));

        return CarPhoto::factory()->for($car)->create(['file_path' => $path]);
    }

    private function dimensions(string $path): array
    {
        $info = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$info[0], $info[1], $info['mime']];
    }

    public function test_converter_creates_thumb_and_large_webp_on_upload(): void
    {
        $photo = $this->photo(2400, 1800);

        $this->assertTrue(app(PhotoConverter::class)->convert($photo));

        [$thumbWidth, $thumbHeight, $thumbMime] = $this->dimensions($photo->conversionPath('thumb'));
        [$largeWidth, $largeHeight, $largeMime] = $this->dimensions($photo->conversionPath('large'));

        $this->assertSame(600, $thumbWidth);
        $this->assertSame(450, $thumbHeight);
        $this->assertSame('image/webp', $thumbMime);
        $this->assertSame(1600, $largeWidth);
        $this->assertSame(1200, $largeHeight);
        $this->assertSame('image/webp', $largeMime);
    }

    public function test_the_original_is_kept(): void
    {
        $photo = $this->photo(2400, 1800);
        $before = Storage::disk('public')->get($photo->file_path);

        app(PhotoConverter::class)->convert($photo);

        $this->assertSame($before, Storage::disk('public')->get($photo->file_path));
    }

    public function test_small_photos_are_not_upscaled(): void
    {
        $photo = $this->photo(400, 300);

        app(PhotoConverter::class)->convert($photo);

        $this->assertSame(400, $this->dimensions($photo->conversionPath('thumb'))[0]);
        $this->assertSame(400, $this->dimensions($photo->conversionPath('large'))[0]);
    }

    public function test_portrait_photos_keep_their_proportions(): void
    {
        $photo = $this->photo(1000, 2000);

        app(PhotoConverter::class)->convert($photo);

        [$width, $height] = $this->dimensions($photo->conversionPath('thumb'));
        $this->assertSame(600, $width);
        $this->assertSame(1200, $height);
    }

    public function test_urls_use_conversions_after_upload(): void
    {
        $photo = $this->photo(2400, 1800, 'side.jpg');
        $this->assertSame($photo->url(), $photo->thumbUrl());

        app(PhotoConverter::class)->convert($photo);

        $this->assertStringEndsWith('/thumb/side.webp', $photo->thumbUrl());
        $this->assertStringEndsWith('/large/side.webp', $photo->largeUrl());
    }

    public function test_conversion_failure_is_logged_and_not_thrown(): void
    {
        Storage::fake('public');
        $car = Car::factory()->inactive()->withoutPhotos()->create();
        $path = "cars/{$car->id}/broken.jpg";
        Storage::disk('public')->put($path, 'this is not an image');
        $photo = CarPhoto::factory()->for($car)->create(['file_path' => $path]);

        Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => $message === 'Car photo conversion failed' && $context['path'] === $path);

        $this->assertFalse(app(PhotoConverter::class)->convert($photo));
        Storage::disk('public')->assertMissing($photo->conversionPath('thumb'));
        $this->assertSame($photo->url(), $photo->thumbUrl()); // still shows the original
    }

    public function test_missing_original_is_logged_and_not_thrown(): void
    {
        Storage::fake('public');
        $car = Car::factory()->inactive()->withoutPhotos()->create();
        $photo = CarPhoto::factory()->for($car)->create(['file_path' => "cars/{$car->id}/gone.jpg"]);

        Log::shouldReceive('warning')->once();

        $this->assertFalse(app(PhotoConverter::class)->convert($photo));
    }
}
