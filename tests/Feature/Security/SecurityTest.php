<?php

namespace Tests\Feature\Security;

use App\Livewire\CarDetail;
use App\Livewire\Inventory;
use App\Models\Car;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private const XSS = '<script>alert("xss")</script>';

    // ---------------------------------------------------------- admin access

    public function test_every_admin_page_redirects_guests_to_the_login(): void
    {
        $checked = 0;

        collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => in_array('GET', $route->methods(), true) && str_starts_with($route->uri(), 'admin'))
            ->reject(fn (LaravelRoute $route) => in_array($route->uri(), ['admin/login'], true))
            ->each(function (LaravelRoute $route) use (&$checked): void {
                $uri = preg_replace('/\{[^}]+\}/', '1', '/'.$route->uri());

                $this->get($uri)->assertRedirect('/admin/login');
                $checked++;
            });

        $this->assertGreaterThanOrEqual(6, $checked, 'Expected the dashboard, cars (list/create/edit), categories and store info');
    }

    public function test_the_admin_panel_has_csrf_session_and_auth_protection(): void
    {
        $panel = Filament::getPanel('admin');
        $middleware = array_merge($panel->getMiddleware(), $panel->getAuthMiddleware());

        $this->assertContains(PreventRequestForgery::class, $middleware);
        $this->assertContains(Authenticate::class, $middleware);
        $this->assertContains(StartSession::class, $middleware);
    }

    public function test_the_public_site_never_exposes_admin_only_data(): void
    {
        Car::factory()->inactive()->create(['title' => '2010 Hidden Secret']);
        Car::factory()->sold()->create(['title' => '2011 Sold Secret']);
        Car::factory()->create(['title' => '2012 Public Car']);

        $home = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('2012 Public Car', $home);
        $this->assertStringNotContainsString('Hidden Secret', $home);
        $this->assertStringNotContainsString('Sold Secret', $home);
        $this->assertStringNotContainsString('sold_price', $home);
        $this->assertStringNotContainsString('/admin', $home);
    }

    // -------------------------------------------------------------- escaping

    public function test_category_names_are_escaped_on_the_site(): void
    {
        $category = Category::factory()->create(['name' => self::XSS]);
        Car::factory()->for($category)->create();

        $this->get('/')
            ->assertDontSee(self::XSS, false)
            ->assertSee(e(self::XSS), false);
    }

    public function test_store_info_is_escaped_on_every_page(): void
    {
        Setting::factory()->create(['phone' => self::XSS, 'street' => self::XSS, 'hours_sun' => self::XSS]);

        foreach (['/', '/privacy', '/accessibility', '/terms'] as $uri) {
            $this->get($uri)->assertDontSee(self::XSS, false);
        }
    }

    public function test_car_fields_are_escaped_in_the_card_modal_and_page(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->create(['title' => self::XSS, 'trim' => self::XSS, 'color' => self::XSS, 'engine' => self::XSS, 'description' => self::XSS]);

        $this->get('/')->assertDontSee(self::XSS, false);
        Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)->assertDontSeeHtml(self::XSS)->assertSeeHtml(e(self::XSS));
        $this->get('/cars/'.$car->fresh()->slug)->assertDontSee(self::XSS, false);
    }

    public function test_query_string_values_are_never_reflected_unescaped(): void
    {
        $this->get('/?make='.urlencode(self::XSS).'&body='.urlencode(self::XSS).'&max='.urlencode(self::XSS))
            ->assertOk()
            ->assertDontSee(self::XSS, false);
    }

    // ------------------------------------------------- tampering with Livewire

    public function test_a_tampered_car_id_cannot_open_a_hidden_or_sold_car(): void
    {
        Setting::factory()->create();
        $hidden = Car::factory()->inactive()->create(['title' => '2010 Hidden Secret']);
        $sold = Car::factory()->sold()->create(['title' => '2011 Sold Secret']);

        // `carId` is a public Livewire property: a client can set it to anything.
        Livewire::test(CarDetail::class)->set('carId', $hidden->id)->assertDontSee('Hidden Secret');
        Livewire::test(CarDetail::class)->set('carId', $sold->id)->assertDontSee('Sold Secret');
        Livewire::test(CarDetail::class)->set('carId', 999999)->assertOk();
    }

    public function test_tampered_filters_cannot_reveal_hidden_cars(): void
    {
        Car::factory()->inactive()->create(['title' => '2010 Hidden Secret']);
        Car::factory()->sold()->create(['title' => '2011 Sold Secret']);

        Livewire::test(Inventory::class)
            ->set('page', '9999')
            ->set('max', '99999999')
            ->assertDontSee('Hidden Secret')
            ->assertDontSee('Sold Secret');
    }

    public function test_an_enormous_page_number_is_capped(): void
    {
        Car::factory()->count(3)->create();

        Livewire::test(Inventory::class)->set('page', '99999999999999999999')->assertOk()->assertViewHas('shown', 3);
    }

    // ------------------------------------------------------------ mass assignment

    public function test_models_do_not_allow_mass_assigning_protected_columns(): void
    {
        $car = new Car;
        $car->fill(['id' => 99, 'slug' => 'x', 'sold_at' => now(), 'sold_price' => 1, 'created_at' => '2000-01-01']);
        $this->assertSame([], array_filter($car->getAttributes()));

        $user = new User;
        $user->fill(['name' => 'N', 'email' => 'e@e.test', 'password' => 'p', 'id' => 5, 'remember_token' => 'x']);
        $this->assertArrayNotHasKey('id', $user->getAttributes());
        $this->assertArrayNotHasKey('remember_token', $user->getAttributes());
    }
}
