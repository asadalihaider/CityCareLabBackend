<?php

namespace App\Filament\Resources\LabOfferResource\Pages;

use App\Filament\Resources\LabOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabOffers extends ListRecords
{
    protected static string $resource = LabOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
