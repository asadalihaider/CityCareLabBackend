<?php

namespace App\Filament\Resources\NotificationResource\Widgets;

use App\Models\ExpoNotification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NotificationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $queuedCount = ExpoNotification::count();
        
        $totalRecipients = ExpoNotification::get()->sum(function ($notification) {
            $data = json_decode($notification->data, true);
            return count($data['to'] ?? []);
        });

        $oldestNotification = ExpoNotification::oldest()->first();
        $oldestDate = $oldestNotification ? $oldestNotification->created_at->diffForHumans() : 'No notifications';

        return [
            Stat::make('Queued Notifications', $queuedCount)
                ->description('Notifications waiting to be sent')
                ->descriptionIcon('heroicon-m-clock')
                ->color($queuedCount > 0 ? 'warning' : 'success'),

            Stat::make('Total Recipients', $totalRecipients)
                ->description('Total devices that will receive notifications')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('primary'),

            Stat::make('Oldest Queued', $oldestDate)
                ->description('Time since oldest notification was queued')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($queuedCount > 0 ? 'danger' : 'gray'),
        ];
    }
}