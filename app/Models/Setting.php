<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Store info. The table holds a single row, edited in the admin ("Store info")
 * and read everywhere through {@see Setting::current()}.
 */
#[Fillable(['whatsapp', 'phone', 'street', 'city', 'state', 'zip', 'hours_mon_fri', 'hours_sat', 'hours_sun'])]
class Setting extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'settings';

    public const SCOPED_BINDING = 'store.settings';

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Values from the approved mockup, used by the seeder and as the fallback
     * when the table is empty.
     *
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'whatsapp' => '19784174036',
            'phone' => '(978) 417-4036',
            'street' => '1336 Merrimack Ave',
            'city' => 'Dracut',
            'state' => 'MA',
            'zip' => '01826',
            'hours_mon_fri' => '9:00 AM – 6:00 PM',
            'hours_sat' => '9:00 AM – 4:00 PM',
            'hours_sun' => 'Closed',
        ];
    }

    /**
     * The one store-info row, cached until it changes.
     */
    public static function current(): static
    {
        // Resolved once per request (scoped binding), so a page with a dozen
        // cards does not read the cache store a dozen times.
        return app(self::SCOPED_BINDING);
    }

    /**
     * Reads the row through the cache; used by the scoped binding.
     */
    public static function loadCached(): static
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->first() ?? static::query()->create(static::defaults()));
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::SCOPED_BINDING);
    }

    /**
     * `https://wa.me/<number>?text=<message>` for the store's WhatsApp number.
     */
    public function whatsappUrl(string $message): string
    {
        return 'https://wa.me/'.$this->whatsapp.'?text='.rawurlencode($message);
    }

    public function tradeInWhatsappUrl(): string
    {
        return $this->whatsappUrl("Hi Mario, I'd like an offer on my car.");
    }
}
