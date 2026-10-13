<?php

namespace App\Filament\Resources\Cars\Pages;

use App\Filament\Resources\Cars\CarResource;
use App\Services\CarPhotoSync;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCar extends EditRecord
{
    protected static string $resource = CarResource::class;

    /** @var list<string> */
    private array $photoPaths = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['photos'] = $this->record->photos()->pluck('file_path')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->photoPaths = array_values((array) ($data['photos'] ?? []));
        unset($data['photos']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(CarPhotoSync::class)->sync($this->record, $this->photoPaths);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Car saved';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
