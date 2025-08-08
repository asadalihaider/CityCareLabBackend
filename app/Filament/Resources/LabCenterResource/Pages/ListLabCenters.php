<?php

namespace App\Filament\Resources\LabCenterResource\Pages;

use App\Filament\Resources\LabCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabCenters extends ListRecords
{
    protected static string $resource = LabCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
