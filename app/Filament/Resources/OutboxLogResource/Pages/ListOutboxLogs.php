<?php

namespace App\Filament\Resources\OutboxLogResource\Pages;

use App\Filament\Resources\OutboxLogResource;
use App\Filament\Resources\OutboxLogResource\Widgets\OutboxStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOutboxLogs extends ListRecords
{
    protected static string $resource = OutboxLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_notification')
                ->label('Create Notification')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn () => OutboxLogResource::getUrl('create')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OutboxStatsWidget::class,
        ];
    }
}
