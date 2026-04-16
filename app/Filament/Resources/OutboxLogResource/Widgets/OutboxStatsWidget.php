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
        $allLogs = OutboxLog::all();

        $sentToday = $allLogs
            ->filter(fn (OutboxLog $log) => $log->status === OutboxStatus::SENT && $log->processed_at?->isToday())
            ->count();

        $failedToday = $allLogs
            ->filter(fn (OutboxLog $log) => $log->status === OutboxStatus::FAILED && $log->processed_at?->isToday())
            ->count();

        $pending = $allLogs
            ->filter(fn (OutboxLog $log) => $log->processed_at === null || ($log->scheduled_at && $log->scheduled_at->isFuture()))
            ->count();

        $sentThisMonth = $allLogs
            ->filter(fn (OutboxLog $log) => $log->status === OutboxStatus::SENT &&
                $log->processed_at &&
                $log->processed_at->isCurrentMonth()
            )
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
