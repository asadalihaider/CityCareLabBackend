<?php

namespace App\Filament\Resources\OperatingCityResource\Pages;

use App\Filament\Resources\OperatingCityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOperatingCities extends ListRecords
{
    protected static string $resource = OperatingCityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
