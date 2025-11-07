<?php

namespace App\Filament\Resources\HealthCardResource\Pages;

use App\Filament\Resources\HealthCardResource;
use Filament\Resources\Pages\EditRecord;

class EditHealthCard extends EditRecord
{
    protected static string $resource = HealthCardResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
