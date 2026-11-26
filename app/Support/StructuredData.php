<?php

namespace App\Support;

use App\Models\Car;
use App\Models\Setting;

/**
 * schema.org JSON-LD for the dealer (home) and for each car page.
 */
class StructuredData
{
    private const DAYS = [
        'hours_mon_fri' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        'hours_sat' => ['Saturday'],
        'hours_sun' => ['Sunday'],
    ];

    /** @return array<string, mixed> */
    public static function autoDealer(Setting $store): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'AutoDealer',
            'name' => "Mario's Auto Sales & Rentals",
            'url' => rtrim(config('app.url'), '/').'/',
            'image' => asset('images/logo.png'),
            'telephone' => $store->phone,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $store->street,
                'addressLocality' => $store->city,
                'addressRegion' => $store->state,
                'postalCode' => $store->zip,
                'addressCountry' => 'US',
            ],
        ];

        $hours = self::openingHours($store);

        if ($hours !== []) {
            $data['openingHoursSpecification'] = $hours;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public static function car(Car $car): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Car',
            'name' => $car->fullName(),
            'image' => $car->photos->map(fn ($photo) => $photo->largeUrl())->values()->all(),
            'color' => $car->color,
            'vehicleModelDate' => $car->year,
            'numberOfDoors' => $car->doors,
            'fuelType' => $car->fuel_type,
            'vehicleIdentificationNumber' => $car->vin,
            'bodyType' => $car->bodyType?->name,
            'vehicleTransmission' => $car->transmission?->name,
            'driveWheelConfiguration' => $car->drivetrain?->name,
            'brand' => $car->category ? ['@type' => 'Brand', 'name' => $car->category->name] : null,
            'mileageFromOdometer' => $car->mileage === null ? null : [
                '@type' => 'QuantitativeValue',
                'value' => $car->mileage,
                'unitCode' => 'SMI',
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $car->price,
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
                'url' => route('cars.show', $car->slug),
            ],
        ];

        return array_filter($data, fn ($value) => $value !== null && $value !== []);
    }

    /**
     * JSON safe to print inside a <script> tag (no way to close the tag early).
     */
    public static function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
    }

    /**
     * The store hours are free text ("9:00 AM – 6:00 PM", "Closed"); only
     * entries that read as a time range become opening-hours specifications.
     *
     * @return list<array<string, mixed>>
     */
    private static function openingHours(Setting $store): array
    {
        $specs = [];

        foreach (self::DAYS as $column => $days) {
            $range = self::parseRange((string) $store->{$column});

            if ($range !== null) {
                $specs[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => $range[0], 'closes' => $range[1]];
            }
        }

        return $specs;
    }

    /** @return array{0: string, 1: string}|null */
    private static function parseRange(string $text): ?array
    {
        if (! preg_match('/(\d{1,2})(?::(\d{2}))?\s*(AM|PM)\s*[–—-]\s*(\d{1,2})(?::(\d{2}))?\s*(AM|PM)/iu', $text, $m)) {
            return null;
        }

        return [self::to24h((int) $m[1], (int) ($m[2] ?: 0), $m[3]), self::to24h((int) $m[4], (int) ($m[5] ?: 0), $m[6])];
    }

    private static function to24h(int $hour, int $minute, string $meridiem): string
    {
        $hour = $hour % 12 + (strtoupper($meridiem) === 'PM' ? 12 : 0);

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
