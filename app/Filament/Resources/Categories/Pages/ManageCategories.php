<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected ?string $subheading = 'The order here is the order of the "Shop by make" buttons on the site.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add category')
                ->modalHeading('Add category')
                ->successNotificationTitle('Category added'),
        ];
    }
}
