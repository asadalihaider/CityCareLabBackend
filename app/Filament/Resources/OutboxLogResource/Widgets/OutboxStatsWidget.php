<?php

namespace App\Filament\Resources\OutboxLogResource\Widgets;

use App\Models\Enum\OutboxStatus;
use App\Models\OutboxLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OutboxStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $sentToday = OutboxLog::byStatus(OutboxStatus::SENT)->whereDate('processed_at', today())->count();
        $failedToday = OutboxLog::byStatus(OutboxStatus::FAILED)->whereDate('processed_at', today())->count();
        $pending = OutboxLog::byStatus(OutboxStatus::PENDING)->count();
        $sentThisMonth = OutboxLog::byStatus(OutboxStatus::SENT)
            ->whereYear('processed_at', now()->year)
            ->whereMonth('processed_at', now()->month)
            ->count();

        return [
            Stat::make('Sent Today', $sentToday)
                ->description('Successful deliveries today')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Failed Today', $failedToday)
                ->description('Failed delivery attempts today')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedToday > 0 ? 'danger' : 'gray'),

            Stat::make('Pending', $pending)
                ->description('Scheduled or awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'gray'),

            Stat::make('Sent This Month', $sentThisMonth)
                ->description('Total successful deliveries this month')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('primary'),
        ];
    }
}
