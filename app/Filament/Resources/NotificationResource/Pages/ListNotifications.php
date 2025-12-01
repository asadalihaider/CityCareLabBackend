<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_notification')
                ->label('New Notification')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn () => NotificationResource::getUrl('send')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\NotificationResource\Widgets\NotificationStatsWidget::class,
        ];
    }
}