<?php

namespace App\Services;

use App\Models\BodyType;
use App\Models\Drivetrain;
use App\Models\Transmission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads a VIN from the free NHTSA vPIC service and returns only the facts that
 * belong to that car (not model-wide data such as recalls or list prices).
 */
class VinDecoder
{
    private const ENDPOINT = 'https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValues/';

    /** A VIN has 17 characters and never uses I, O or Q. */
    public static function isValid(string $vin): bool
    {
        return (bool) preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', self::normalize($vin));
    }

    public static function normalize(string $vin): string
    {
        return strtoupper(preg_replace('/\s+/', '', $vin));
    }

    /**
     * @return array<string, mixed>|null the car fields, or null when the VIN
     *                                   is invalid, unknown or the service is down
     */
    public function decode(string $vin): ?array
    {
        $vin = self::normalize($vin);

        if (! self::isValid($vin)) {
            return null;
        }

        $raw = Cache::remember("vin:{$vin}", now()->addDays(30), fn () => $this->fetch($vin));

        // A failed call is not worth remembering for a month.
        if ($raw === null) {
            Cache::forget("vin:{$vin}");

            return null;
        }

        return $this->map($vin, $raw);
    }

    /** @return array<string, mixed>|null */
    private function fetch(string $vin): ?array
    {
        try {
            $response = Http::timeout(8)->retry(2, 200, throw: false)
                ->get(self::ENDPOINT.$vin, ['format' => 'json']);
        } catch (\Throwable) {
            return null;
        }

        $row = $response->successful() ? ($response->json('Results.0') ?? null) : null;

        return is_array($row) ? $row : null;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    public function map(string $vin, array $raw): ?array
    {
        $make = $this->clean($raw['Make'] ?? null);
        $model = $this->clean($raw['Model'] ?? null);
        $year = (int) ($raw['ModelYear'] ?? 0);

        // Nothing useful was decoded: let the form stay manual.
        if ($make === null || $model === null || $year < 1950) {
            return null;
        }

        return [
            'vin' => $vin,
            'year' => $year,
            'make' => Str::title($make),
            'model' => $model,
            'title' => trim("{$year} ".Str::title($make)." {$model}"),
            'trim' => $this->clean($raw['Trim'] ?? null),
            'doors' => $this->int($raw['Doors'] ?? null),
            'seats' => $this->int($raw['Seats'] ?? null),
            'cylinders' => $this->int($raw['EngineCylinders'] ?? null),
            'displacement_l' => $this->float($raw['DisplacementL'] ?? null),
            'horsepower' => $this->int($raw['EngineHP'] ?? null),
            'fuel_type' => $this->clean($raw['FuelTypePrimary'] ?? null),
            'airbags' => $this->airbags($raw),
            'tpms' => $this->tpms($raw['TPMS'] ?? null),
            'body_type_id' => $this->bodyTypeId($raw['BodyClass'] ?? null),
            'transmission_id' => $this->transmissionId($raw['TransmissionStyle'] ?? null),
            'drivetrain_id' => $this->drivetrainId($raw['DriveType'] ?? null),
            'complete' => (string) ($raw['ErrorCode'] ?? '') === '0',
            'vin_data' => $raw,
        ];
    }

    /** @param array<string, mixed> $raw */
    private function airbags(array $raw): ?string
    {
        $found = collect([
            'Front' => $raw['AirBagLocFront'] ?? null,
            'Side' => $raw['AirBagLocSide'] ?? null,
            'Curtain' => $raw['AirBagLocCurtain'] ?? null,
            'Knee' => $raw['AirBagLocKnee'] ?? null,
        ])->filter(fn ($value) => $this->clean($value) !== null)->keys();

        return $found->isEmpty() ? null : $found->implode(', ');
    }

    private function tpms(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value === null ? null : (str_contains(strtolower($value), 'direct') ? 'Direct' : (str_contains(strtolower($value), 'indirect') ? 'Indirect' : $value));
    }

    private function bodyTypeId(mixed $value): ?int
    {
        $v = strtolower((string) $value);

        $slug = match (true) {
            $v === '' => null,
            str_contains($v, 'sport utility'), str_contains($v, 'multipurpose'), str_contains($v, 'crossover') => 'suv',
            str_contains($v, 'pickup'), str_contains($v, 'truck') => 'truck',
            str_contains($v, 'sedan'), str_contains($v, 'coupe'), str_contains($v, 'hatchback'),
            str_contains($v, 'wagon'), str_contains($v, 'convertible'), str_contains($v, 'roadster') => 'car',
            default => null,
        };

        return $slug ? BodyType::query()->where('slug', $slug)->value('id') : null;
    }

    private function transmissionId(mixed $value): ?int
    {
        $v = strtolower((string) $value);

        $slug = match (true) {
            $v === '' => null,
            str_contains($v, 'manual') => 'manual',
            str_contains($v, 'automatic'), str_contains($v, 'cvt'), str_contains($v, 'dual') => 'automatic',
            default => null,
        };

        return $slug ? Transmission::query()->where('slug', $slug)->value('id') : null;
    }

    private function drivetrainId(mixed $value): ?int
    {
        $v = strtolower((string) $value);

        $slug = match (true) {
            $v === '' => null,
            str_contains($v, 'awd'), str_contains($v, 'all-wheel') => 'awd',
            str_contains($v, '4wd'), str_contains($v, '4-wheel') => '4wd',
            str_contains($v, 'fwd'), str_contains($v, 'front-wheel') => 'fwd',
            str_contains($v, 'rwd'), str_contains($v, 'rear-wheel') => 'rwd',
            default => null,
        };

        return $slug ? Drivetrain::query()->where('slug', $slug)->value('id') : null;
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || strtolower($value) === 'not applicable' ? null : $value;
    }

    private function int(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function float(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value > 0 ? round((float) $value, 1) : null;
    }
}
