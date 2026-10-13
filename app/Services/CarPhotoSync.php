<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarPhoto;
use Illuminate\Support\Facades\DB;

/**
 * Makes a car's photos match the list of paths submitted by the admin form.
 *
 * The form uploads new files into `cars/uploads/`. Saving the car moves them
 * to `cars/{car id}/`, creates the `car_photos` rows in the submitted order
 * (the first one is the cover) and converts them. Photos missing from the list
 * are deleted along with their files.
 *
 * The paths come from the browser, so nothing is trusted: a path is only used
 * when it is already a photo of this car or a fresh upload in `cars/uploads/`.
 */
class CarPhotoSync
{
    public const UPLOAD_DIRECTORY = 'cars/uploads';

    public function __construct(private readonly PhotoConverter $converter) {}

    /**
     * @param  array<int|string, mixed>  $paths  photo paths, in display order
     */
    public function sync(Car $car, array $paths): void
    {
        $disk = CarPhoto::disk();
        $desired = $this->cleanPaths($paths);
        $existing = $car->photos()->get()->keyBy('file_path');

        DB::transaction(function () use ($desired, $existing): void {
            // Photos taken out of the form.
            $existing->reject(fn (CarPhoto $photo) => in_array($photo->file_path, $desired, true))
                ->each(fn (CarPhoto $photo) => $photo->delete());
        });

        $new = [];

        foreach ($desired as $position => $path) {
            if ($photo = $existing->get($path)) {
                if ($photo->sort_order !== $position) {
                    $photo->forceFill(['sort_order' => $position])->save();
                }

                continue;
            }

            if (! $this->isFreshUpload($path) || ! $disk->exists($path)) {
                continue; // not ours, or the file is gone
            }

            $final = $this->moveIntoCarDirectory($car, $path);

            $photo = $car->photos()->create(['file_path' => $final, 'sort_order' => $position]);
            $new[] = $photo;
        }

        foreach ($new as $photo) {
            $this->converter->convert($photo);
        }

        $car->unsetRelation('photos');
    }

    /**
     * @param  array<int|string, mixed>  $paths
     * @return list<string>
     */
    private function cleanPaths(array $paths): array
    {
        return collect($paths)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn (string $path) => ltrim($path, '/'))
            ->filter(fn (string $path) => str_starts_with($path, 'cars/') && ! str_contains($path, '..'))
            ->unique()
            ->values()
            ->all();
    }

    private function isFreshUpload(string $path): bool
    {
        return str_starts_with($path, self::UPLOAD_DIRECTORY.'/') && substr_count($path, '/') === 2;
    }

    private function moveIntoCarDirectory(Car $car, string $path): string
    {
        $disk = CarPhoto::disk();
        $name = basename($path);
        $target = "cars/{$car->id}/{$name}";

        if ($disk->exists($target)) {
            $target = "cars/{$car->id}/".uniqid().'-'.$name;
        }

        $disk->move($path, $target);

        return $target;
    }
}
