<?php

namespace Tests\Feature\Vin;

use App\Models\BodyType;
use App\Models\Drivetrain;
use App\Models\Transmission;
use App\Services\VinDecoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VinDecoderTest extends TestCase
{
    use RefreshDatabase;

    private const VIN = 'SALYL2RX3JA777038';

    /** @return array<string, string> */
    private function velar(array $override = []): array
    {
        return array_merge([
            'Make' => 'LAND ROVER', 'Model' => 'Range Rover Velar', 'ModelYear' => '2018', 'Trim' => 'R-Dynamic SE',
            'BodyClass' => 'Sport Utility Vehicle [SUV]/Multipurpose Vehicle [MPV]', 'Doors' => '5', 'Seats' => '5',
            'EngineCylinders' => '4', 'DisplacementL' => '2', 'EngineHP' => '247', 'FuelTypePrimary' => 'Gasoline',
            'TransmissionStyle' => 'Automatic', 'DriveType' => 'AWD/All-Wheel Drive',
            'AirBagLocFront' => '1st Row (Driver and Passenger)', 'AirBagLocSide' => '1st and 2nd and 3rd Rows',
            'AirBagLocCurtain' => '', 'AirBagLocKnee' => '', 'TPMS' => 'Direct', 'ErrorCode' => '0',
            'BasePrice' => '58700',
        ], $override);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        BodyType::factory()->create(['name' => 'SUV', 'slug' => 'suv']);
        Transmission::factory()->create(['name' => 'Automatic', 'slug' => 'automatic']);
        Drivetrain::factory()->create(['name' => 'AWD', 'slug' => 'awd']);
    }

    public function test_it_maps_the_car_facts(): void
    {
        Http::fake(['vpic.nhtsa.dot.gov/*' => Http::response(['Results' => [$this->velar()]])]);

        $data = app(VinDecoder::class)->decode(strtolower(self::VIN));

        $this->assertSame('2018 Land Rover Range Rover Velar', $data['title']);
        $this->assertSame('R-Dynamic SE', $data['trim']);
        $this->assertSame(5, $data['doors']);
        $this->assertSame(4, $data['cylinders']);
        $this->assertSame(2.0, $data['displacement_l']);
        $this->assertSame(247, $data['horsepower']);
        $this->assertSame('Front, Side', $data['airbags']);
        $this->assertSame('Direct', $data['tpms']);
        $this->assertSame(BodyType::where('slug', 'suv')->value('id'), $data['body_type_id']);
        $this->assertSame(Transmission::where('slug', 'automatic')->value('id'), $data['transmission_id']);
        $this->assertSame(Drivetrain::where('slug', 'awd')->value('id'), $data['drivetrain_id']);
        $this->assertTrue($data['complete']);
    }

    public function test_it_rejects_a_malformed_vin_without_calling_the_service(): void
    {
        Http::fake();

        foreach (['', 'ABC', 'SALYL2RX3JA77703I', 'SALYL2RX3JA77703'] as $vin) {
            $this->assertNull(app(VinDecoder::class)->decode($vin));
        }

        Http::assertNothingSent();
    }

    public function test_a_vin_nobody_knows_returns_null(): void
    {
        Http::fake(['*' => Http::response(['Results' => [['Make' => '', 'Model' => '', 'ModelYear' => '', 'ErrorCode' => '7']]])]);

        $this->assertNull(app(VinDecoder::class)->decode(self::VIN));
    }

    public function test_a_service_failure_returns_null_and_is_not_cached(): void
    {
        Http::fake(['*' => Http::sequence()->push('down', 500)->push('down', 500)->push('down', 500)->push(['Results' => [$this->velar()]])]);

        $this->assertNull(app(VinDecoder::class)->decode(self::VIN));
        $this->assertNotNull(app(VinDecoder::class)->decode(self::VIN));
    }

    public function test_the_answer_is_cached_per_vin(): void
    {
        Http::fake(['*' => Http::response(['Results' => [$this->velar()]])]);

        app(VinDecoder::class)->decode(self::VIN);
        app(VinDecoder::class)->decode(self::VIN);

        Http::assertSentCount(1);
    }

    public function test_unknown_body_types_stay_empty(): void
    {
        Http::fake(['*' => Http::response(['Results' => [$this->velar(['BodyClass' => 'Bus', 'DriveType' => '', 'TransmissionStyle' => ''])]])]);

        $data = app(VinDecoder::class)->decode(self::VIN);

        $this->assertNull($data['body_type_id']);
        $this->assertNull($data['transmission_id']);
        $this->assertNull($data['drivetrain_id']);
    }

    public function test_model_wide_data_is_not_part_of_the_car_fields(): void
    {
        Http::fake(['*' => Http::response(['Results' => [$this->velar()]])]);

        $data = app(VinDecoder::class)->decode(self::VIN);

        $this->assertArrayNotHasKey('BasePrice', $data);
        $this->assertArrayNotHasKey('recalls', $data);
    }
}
