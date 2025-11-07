<?php

namespace App\Filament\Resources\HealthCardResource\Pages;

use App\Filament\Resources\HealthCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHealthCards extends ListRecords
{
    protected static string $resource = HealthCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
