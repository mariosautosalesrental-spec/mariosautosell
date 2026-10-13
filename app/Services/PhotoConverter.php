<?php

namespace App\Services;

use App\Models\CarPhoto;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Throwable;

/**
 * Makes the WebP `thumb` (600px) and `large` (1600px) versions of a car photo.
 *
 * Runs right when the photo is uploaded, with no queue. A photo that cannot be
 * converted is logged and keeps working: the site falls back to the original.
 */
class PhotoConverter
{
    private readonly ImageManagerInterface $manager;

    public function __construct(?ImageManagerInterface $manager = null)
    {
        $this->manager = $manager ?? ImageManager::usingDriver(Driver::class);
    }

    /**
     * @return bool true when every conversion was written
     */
    public function convert(CarPhoto $photo): bool
    {
        $disk = CarPhoto::disk();

        try {
            $original = $disk->get($photo->file_path);

            if ($original === null) {
                throw new \RuntimeException("Original photo not found: {$photo->file_path}");
            }

            foreach (config('marios.photo_sizes') as $size => $width) {
                // Decoded again for every size: scaling changes the image in place.
                $webp = $this->manager->decode($original)
                    ->orient()                      // phone photos carry their rotation in EXIF
                    ->scaleDown(width: $width)      // never upscales a small photo
                    ->encodeUsingFormat(Format::WEBP, quality: 82);

                $disk->put($photo->conversionPath($size), (string) $webp, ['visibility' => 'public']);
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Car photo conversion failed', [
                'photo_id' => $photo->id,
                'path' => $photo->file_path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
