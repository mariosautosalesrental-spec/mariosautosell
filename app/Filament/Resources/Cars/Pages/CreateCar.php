<?php

namespace App\Filament\Resources\Cars\Pages;

use App\Filament\Resources\Cars\CarResource;
use App\Services\CarPhotoSync;
use Filament\Resources\Pages\CreateRecord;

class CreateCar extends CreateRecord
{
    protected static string $resource = CarResource::class;

    protected static bool $canCreateAnother = false;

    /** @var list<string> */
    private array $photoPaths = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The photos are not a column: they become car_photos rows once the car exists.
        $this->photoPaths = array_values((array) ($data['photos'] ?? []));
        unset($data['photos']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(CarPhotoSync::class)->sync($this->record, $this->photoPaths);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Car added';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
