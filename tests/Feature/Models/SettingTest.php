<?php

namespace Tests\Feature\Models;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_returns_the_single_row(): void
    {
        Setting::factory()->create(['phone' => '(978) 111-2222']);

        $this->assertSame('(978) 111-2222', Setting::current()->phone);
    }

    public function test_current_creates_defaults_when_table_is_empty(): void
    {
        $this->assertSame(0, Setting::count());

        $setting = Setting::current();

        $this->assertSame('19784174036', $setting->whatsapp);
        $this->assertSame(1, Setting::count());
    }

    public function test_current_is_cached(): void
    {
        Setting::factory()->create();
        Setting::current();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        Setting::current();
        Setting::current();

        $this->assertSame(0, $queries);
        $this->assertTrue(Cache::has(Setting::CACHE_KEY));
    }

    public function test_saving_setting_clears_cache(): void
    {
        $setting = Setting::factory()->create(['phone' => '(978) 111-2222']);
        $this->assertSame('(978) 111-2222', Setting::current()->phone);

        $setting->update(['phone' => '(978) 333-4444']);

        $this->assertFalse(Cache::has(Setting::CACHE_KEY));
        $this->assertSame('(978) 333-4444', Setting::current()->phone);
    }

    public function test_deleting_setting_clears_cache(): void
    {
        $setting = Setting::factory()->create();
        Setting::current();

        $setting->delete();

        $this->assertFalse(Cache::has(Setting::CACHE_KEY));
    }

    public function test_whatsapp_url_encodes_the_message(): void
    {
        $setting = Setting::factory()->make(['whatsapp' => '19784174036']);

        $this->assertSame(
            'https://wa.me/19784174036?text=Hi%20Mario%2C%20is%20it%20here%3F',
            $setting->whatsappUrl('Hi Mario, is it here?'),
        );
    }

    public function test_trade_in_whatsapp_url_has_expected_message(): void
    {
        $setting = Setting::factory()->make(['whatsapp' => '19784174036']);

        $this->assertSame(
            'https://wa.me/19784174036?text=Hi%20Mario%2C%20I%27d%20like%20an%20offer%20on%20my%20car.',
            $setting->tradeInWhatsappUrl(),
        );
    }

    public function test_current_works_with_a_cache_store_that_serializes(): void
    {
        config(['cache.default' => 'file', 'cache.stores.file.path' => storage_path('framework/testing-cache-'.uniqid())]);
        Cache::purge('file');
        Setting::factory()->create(['phone' => '(978) 123-4567']);

        $first = Setting::current();
        app()->forgetInstance(Setting::SCOPED_BINDING); // new request, value now comes from the file cache
        $second = Setting::current();

        $this->assertInstanceOf(Setting::class, $second);
        $this->assertSame('(978) 123-4567', $second->phone);
        $this->assertTrue($second->exists);
        $this->assertSame($first->id, $second->id);

        Cache::flush();
    }
}
