<?php

namespace App\Filament\Resources\OutboxLogResource\Pages;

use App\Filament\Resources\OutboxLogResource;
use Filament\Resources\Pages\ListRecords;

class ListOutboxLogs extends ListRecords
{
    protected static string $resource = OutboxLogResource::class;
}
