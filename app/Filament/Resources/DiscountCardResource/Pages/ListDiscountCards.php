<?php

namespace App\Filament\Resources\DiscountCardResource\Pages;

use App\Filament\Resources\DiscountCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDiscountCards extends ListRecords
{
    protected static string $resource = DiscountCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
