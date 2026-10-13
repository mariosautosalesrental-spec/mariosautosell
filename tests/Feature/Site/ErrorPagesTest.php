<?php

namespace Tests\Feature\Site;

use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_route_renders_branded_404(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('<header class="site">', false)
            ->assertSee("We can't find that page", false)
            ->assertSee('Back to the lot')
            ->assertSee('href="'.route('home').'"', false);
    }

    public function test_sold_car_page_renders_branded_404(): void
    {
        $car = Car::factory()->sold()->create();

        $this->get('/cars/'.$car->fresh()->slug)->assertNotFound()->assertSee("We can't find that page", false);
    }

    public function test_500_page_uses_the_site_layout_in_english(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringContainsString('<header class="site">', $html);
        $this->assertStringContainsString('Something went wrong on our side', $html);
        $this->assertStringContainsString('Back to the lot', $html);
    }

    public function test_500_page_still_renders_without_the_database(): void
    {
        \Illuminate\Support\Facades\Schema::drop('settings');
        \App\Models\Setting::flushCache();

        $html = view('errors.500')->render();

        $this->assertStringContainsString('Something went wrong on our side', $html);
        $this->assertStringContainsString('(978) 417-4036', $html);
    }
}
