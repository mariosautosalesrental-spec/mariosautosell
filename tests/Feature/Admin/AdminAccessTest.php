<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_guest_is_redirected_from_admin_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/categories')->assertRedirect('/admin/login');
        $this->get('/admin/store-info')->assertRedirect('/admin/login');
    }

    public function test_login_page_has_branding_and_english_copy(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin')
            ->assertSee('Sign in to manage your cars and store info.')
            ->assertSee('images/logo.png', false)
            ->assertSee('Sign in');
    }

    public function test_invalid_credentials_do_not_log_in(): void
    {
        User::factory()->create(['email' => 'mario@example.com', 'password' => 'right-password']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'mario@example.com', 'password' => 'wrong-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_valid_user_logs_in_and_reaches_admin(): void
    {
        User::factory()->create(['email' => 'mario@example.com', 'password' => 'right-password']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'mario@example.com', 'password' => 'right-password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertAuthenticated();
        $this->get('/admin')->assertOk();
    }

    public function test_every_user_has_the_same_access(): void
    {
        $mario = User::factory()->create();
        $joao = User::factory()->create();

        foreach ([$mario, $joao] as $user) {
            $this->actingAs($user)->get('/admin')->assertOk();
            $this->actingAs($user)->get('/admin/categories')->assertOk();
            $this->actingAs($user)->get('/admin/store-info')->assertOk();
        }
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'mario@example.com', 'password' => 'right-password']);

        $component = Livewire::test(Login::class);

        foreach (range(1, 6) as $attempt) {
            $component->fillForm(['email' => 'mario@example.com', 'password' => 'wrong'])->call('authenticate');
        }

        $component->fillForm(['email' => 'mario@example.com', 'password' => 'right-password'])->call('authenticate');

        $this->assertGuest();
    }

    public function test_admin_seeder_creates_a_user_only_locally(): void
    {
        $this->app['env'] = 'production';
        $this->app->make(AdminUserSeeder::class)->setContainer($this->app)->run();
        $this->assertSame(0, User::count());

        $this->app['env'] = 'local';
        $this->app->make(AdminUserSeeder::class)->setContainer($this->app)->run();
        $this->app->make(AdminUserSeeder::class)->setContainer($this->app)->run();

        $this->assertSame(1, User::count());
        $this->assertSame('admin@marios.test', User::first()->email);
    }

    public function test_navigation_lists_the_main_sections_and_a_link_to_the_site(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertSee('Categories')
            ->assertSee('Store info')
            ->assertSee('View website');
    }

    public function test_tab_bar_is_rendered_for_phones_and_leaves_out_external_links(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/admin/categories')->getContent();

        $this->assertStringContainsString('class="mario-tabbar"', $html);
        $tabs = substr($html, strpos($html, 'class="mario-tabbar"'));
        $tabs = substr($tabs, 0, strpos($tabs, '</nav>'));
        $this->assertStringContainsString('Categories', $tabs);
        $this->assertStringContainsString('Store info', $tabs);
        $this->assertStringNotContainsString('View website', $tabs);
    }
}
