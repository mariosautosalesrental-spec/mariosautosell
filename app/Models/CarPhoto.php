<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['file_path', 'sort_order'])]
class CarPhoto extends Model
{
    use HasFactory;

    /** @return BelongsTo<Car, $this> */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public static function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('marios.photos_disk'));
    }

    /**
     * Where a conversion of this photo lives: `<dir>/<size>/<name>.webp`
     * next to the original (e.g. `cars/7/thumb/front.webp`).
     */
    public function conversionPath(string $size): string
    {
        $info = pathinfo($this->file_path);
        $dir = $info['dirname'] === '.' ? '' : $info['dirname'].'/';

        return $dir.$size.'/'.$info['filename'].'.webp';
    }

    public function url(): string
    {
        return static::disk()->url($this->file_path);
    }

    public function thumbUrl(): string
    {
        return $this->conversionUrl('thumb');
    }

    public function largeUrl(): string
    {
        return $this->conversionUrl('large');
    }

    /**
     * The conversion when it exists, the original otherwise. Positive answers
     * are cached so listing pages do not hit the disk once per photo.
     */
    private function conversionUrl(string $size): string
    {
        $path = $this->conversionPath($size);
        $key = 'photo-conversion:'.md5($path);

        if (Cache::get($key) === true) {
            return static::disk()->url($path);
        }

        if (static::disk()->exists($path)) {
            Cache::forever($key, true);

            return static::disk()->url($path);
        }

        return $this->url();
    }
}
