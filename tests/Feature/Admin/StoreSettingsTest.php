<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\StoreSettings;
use App\Models\Car;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    private array $valid = [
        'whatsapp' => '15551234567',
        'phone' => '(555) 123-4567',
        'hours_mon_fri' => '8:00 AM – 5:00 PM',
        'hours_sat' => '10:00 AM – 2:00 PM',
        'hours_sun' => 'Closed',
        'street' => '1 Main St',
        'city' => 'Lowell',
        'state' => 'MA',
        'zip' => '01850',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create());
    }

    public function test_form_loads_the_current_values(): void
    {
        Setting::factory()->create(['phone' => '(978) 111-2222', 'whatsapp' => '19781112222']);

        Livewire::test(StoreSettings::class)
            ->assertFormSet(['phone' => '(978) 111-2222', 'whatsapp' => '19781112222', 'hours_sun' => 'Closed']);
    }

    public function test_page_shows_the_expected_labels(): void
    {
        Setting::factory()->create();

        $this->get('/admin/store-info')
            ->assertOk()
            ->assertSee('WhatsApp number')
            ->assertSee('Mon–Fri')
            ->assertSee('Saturday')
            ->assertSee('Sunday')
            ->assertSee('ZIP')
            ->assertSee('Save changes');
    }

    public function test_store_info_saves_to_single_row(): void
    {
        $original = Setting::factory()->create();

        Livewire::test(StoreSettings::class)
            ->fillForm($this->valid)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Setting::count());
        $this->assertSame($original->id, Setting::first()->id);
        $this->assertDatabaseHas('settings', $this->valid);
    }

    public function test_saving_creates_the_row_when_it_does_not_exist_yet(): void
    {
        Livewire::test(StoreSettings::class)->fillForm($this->valid)->call('save')->assertHasNoFormErrors();

        $this->assertSame(1, Setting::count());
        $this->assertSame('1 Main St', Setting::first()->street);
    }

    public function test_store_info_requires_all_fields(): void
    {
        Setting::factory()->create();

        $empty = array_fill_keys(array_keys($this->valid), '');

        Livewire::test(StoreSettings::class)
            ->fillForm($empty)
            ->call('save')
            ->assertHasFormErrors(array_fill_keys(array_keys($this->valid), 'required'));
    }

    public function test_whatsapp_rejects_non_digits(): void
    {
        Setting::factory()->create();

        foreach (['+1 (978) 417-4036', '1978-417-4036', '1 978 417 4036', 'abc1234567', '+19784174036'] as $bad) {
            Livewire::test(StoreSettings::class)
                ->fillForm([...$this->valid, 'whatsapp' => $bad])
                ->call('save')
                ->assertHasFormErrors(['whatsapp']);
        }
    }

    public function test_whatsapp_rejects_numbers_that_are_too_short_or_too_long(): void
    {
        Setting::factory()->create();

        foreach (['1234567', '1234567890123456', '0123456789'] as $bad) {
            Livewire::test(StoreSettings::class)
                ->fillForm([...$this->valid, 'whatsapp' => $bad])
                ->call('save')
                ->assertHasFormErrors(['whatsapp']);
        }
    }

    public function test_whatsapp_accepts_country_code_digits(): void
    {
        Setting::factory()->create();

        foreach (['19784174036', '5511999998888', '12345678'] as $good) {
            Livewire::test(StoreSettings::class)
                ->fillForm([...$this->valid, 'whatsapp' => $good])
                ->call('save')
                ->assertHasNoFormErrors();
        }
    }

    public function test_saving_store_info_clears_cache(): void
    {
        Setting::factory()->create(['phone' => '(978) 000-0000']);
        $this->assertSame('(978) 000-0000', Setting::current()->phone);

        Livewire::test(StoreSettings::class)->fillForm($this->valid)->call('save');

        $this->assertFalse(Cache::has(Setting::CACHE_KEY));
        $this->assertSame('(555) 123-4567', Setting::current()->phone);
    }

    public function test_store_info_change_in_admin_reflects_on_public_site(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->create();

        $this->get('/')->assertSee('(978) 417-4036');

        Livewire::test(StoreSettings::class)->fillForm($this->valid)->call('save');

        $this->get('/')
            ->assertSee('1 Main St')
            ->assertSee('Lowell, MA 01850')
            ->assertSee('(555) 123-4567')
            ->assertSee('Mon–Fri 8:00 AM – 5:00 PM')
            ->assertSee('https://wa.me/15551234567?text=', false)
            ->assertDontSee('(978) 417-4036')
            ->assertDontSee('1336 Merrimack');
        $this->get('/cars/'.$car->fresh()->slug)->assertSee('https://wa.me/15551234567?text=', false);
        $this->get('/privacy')->assertSee('(555) 123-4567');
    }
}
