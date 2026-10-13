<?php

namespace Tests\Feature\Journeys;

use App\Filament\Pages\StoreSettings;
use App\Filament\Resources\Cars\Pages\CreateCar;
use App\Filament\Resources\Cars\Pages\ListCars;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Widgets\InventoryOverview;
use App\Filament\Widgets\SalesThisMonth;
use App\Livewire\CarDetail;
use App\Livewire\Inventory;
use App\Models\BodyType;
use App\Models\Car;
use App\Models\Category;
use App\Models\Drivetrain;
use App\Models\Transmission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\MakesImages;
use Tests\TestCase;

/**
 * The whole story end to end, the way Mario and a visitor go through it.
 */
class StoreJourneysTest extends TestCase
{
    use MakesImages;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Filament::setCurrentPanel('admin');
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->run(); // lookups + store info, like production
    }

    private function carData(array $overrides = []): array
    {
        return array_merge([
            'photos' => [UploadedFile::fake()->createWithContent('front.jpg', $this->jpegBytes(1600, 1200))],
            'category_id' => Category::where('name', 'Jeep')->value('id'),
            'title' => '2019 Jeep Compass',
            'trim' => 'Limited 4x4',
            'price' => 18900,
            'mileage' => 58200,
            'body_type_id' => BodyType::where('slug', 'suv')->value('id'),
            'transmission_id' => Transmission::where('slug', 'automatic')->value('id'),
            'drivetrain_id' => Drivetrain::where('slug', '4wd')->value('id'),
            'color' => 'Gray',
            'is_active' => true,
        ], $overrides);
    }

    public function test_first_day_the_store_creates_its_categories_then_a_car_and_it_shows_on_the_site(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/')->assertSee('0 cars')->assertSee('No cars match these filters yet.');

        // 1. Categories come first: none exist in production.
        $this->assertSame(0, Category::count());
        Livewire::test(ManageCategories::class)->callAction(CreateAction::class, ['name' => 'Jeep']);
        Livewire::test(ManageCategories::class)->callAction(CreateAction::class, ['name' => 'Fiat']);
        $this->assertSame(['Jeep', 'Fiat'], Category::ordered()->pluck('name')->all());

        // 2. A car with a photo.
        Livewire::test(CreateCar::class)->fillForm($this->carData())->call('create')->assertHasNoFormErrors();

        // 3. The visitor sees it.
        $car = Car::firstOrFail();
        $this->get('/')
            ->assertSee('2019 Jeep Compass')
            ->assertSee('$18,900')
            ->assertSee('Jeep')
            ->assertDontSee('Fiat'); // a make without cars has no button
        Livewire::test(Inventory::class)->assertViewHas('total', 1)->set('make', 'jeep')->assertViewHas('total', 1);
        Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)
            ->assertSee('MA used-car warranty:')
            ->assertSeeHtml(e($car->fresh()->whatsappUrl()));
        $this->get('/cars/'.$car->fresh()->slug)->assertOk();
        $this->get('/sitemap.xml')->assertSee($car->fresh()->slug);
    }

    public function test_car_lifecycle_from_creation_to_sale(): void
    {
        $this->actingAs(User::factory()->create());
        Category::create(['name' => 'Jeep']);
        Livewire::test(CreateCar::class)->fillForm($this->carData())->call('create')->assertHasNoFormErrors();
        $car = Car::firstOrFail();
        $slug = $car->fresh()->slug;

        // On the site and in the numbers of the lot.
        $this->get('/')->assertSee('2019 Jeep Compass')->assertSee('1 car');
        Livewire::test(InventoryOverview::class)->assertSee('$18,900');

        // WhatsApp link with the store number and the car message.
        $this->get("/cars/{$slug}")->assertSee('https://wa.me/19784174036?text='.rawurlencode('Hi Mario, is the 2019 Jeep Compass Limited 4x4 still available?'), false);

        // Mario sells it.
        Livewire::test(ListCars::class)
            ->callAction(TestAction::make('markAsSold')->table($car), ['sold_at' => now('America/New_York')->toDateString(), 'sold_price' => 18000]);

        // Gone from everything public ...
        $this->get('/')->assertDontSee('2019 Jeep Compass')->assertDontSee('Jeep')->assertSee('0 cars');
        $this->get("/cars/{$slug}")->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee($slug);

        // ... still in the admin, and counted on the dashboard.
        Livewire::test(ListCars::class)->assertCanSeeTableRecords([$car]);
        Livewire::test(SalesThisMonth::class)->assertSee('$18,000');
        Livewire::test(InventoryOverview::class)->assertDontSee('$18,900');

        // A mistake: it comes back hidden, then on the site again.
        Livewire::test(ListCars::class)->callAction(TestAction::make('markAsAvailable')->table($car));
        $this->get("/cars/{$slug}")->assertNotFound();
        Livewire::test(ListCars::class)->call('updateTableColumnState', 'is_active', (string) $car->getKey(), true);
        $this->get("/cars/{$slug}")->assertOk();
        Livewire::test(SalesThisMonth::class)->assertDontSee('$18,000');
    }

    public function test_store_info_and_category_changes_reach_the_site(): void
    {
        $this->actingAs(User::factory()->create());
        $jeep = Category::create(['name' => 'Jeep']);
        $fiat = Category::create(['name' => 'Fiat']);
        Car::factory()->for($jeep)->create();
        Car::factory()->for($fiat)->create();
        $this->get('/')->assertSeeInOrder(['Jeep', 'Fiat']);

        Livewire::test(ManageCategories::class)->callAction(TestAction::make('moveDown')->table($jeep));
        $this->get('/')->assertSeeInOrder(['Fiat', 'Jeep']);

        Livewire::test(StoreSettings::class)->fillForm([
            'whatsapp' => '15559876543', 'phone' => '(555) 987-6543', 'hours_sun' => '10:00 AM – 2:00 PM',
        ])->call('save')->assertHasNoFormErrors();

        $this->get('/')
            ->assertSee('(555) 987-6543')
            ->assertSee('Sun 10:00 AM – 2:00 PM')
            ->assertSee('https://wa.me/15559876543?text=', false)
            ->assertDontSee('(978) 417-4036');
    }

    public function test_the_visitor_flow_filters_opens_a_car_and_asks_on_whatsapp(): void
    {
        $jeep = Category::create(['name' => 'Jeep']);
        $fiat = Category::create(['name' => 'Fiat']);
        $compass = Car::factory()->for($jeep)->create(['title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4', 'price' => 14000]);
        Car::factory()->for($fiat)->create(['title' => '2016 Fiat Toro', 'price' => 21000]);

        Livewire::test(Inventory::class)
            ->call('selectMake', 'jeep')
            ->assertSee('2019 Jeep Compass')->assertDontSee('2016 Fiat Toro')
            ->set('max', '15000')->assertSee('2019 Jeep Compass')
            ->set('max', '10000')->assertSee('No cars match these filters yet.');

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $compass->id)
            ->assertSee('2019 Jeep Compass')
            ->assertSeeHtml('https://wa.me/19784174036?text=');
    }
}
