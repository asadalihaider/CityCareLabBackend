<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Customer;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = $this->filters['fromDate'] ?? null;
        $endDate = $this->filters['toDate'] ?? null;

        $bookings = Booking::when($startDate && $endDate, fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->get();
        $totalCustomers = Customer::when($startDate && $endDate, fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->count();

        $totalBookings = $bookings->count();
        $totalRevenue = $bookings->sum(function ($booking) {
            return collect($booking->booking_items)->sum(function ($item) {
                $price = $item['price'] ?? 0;
                $discount = $item['discount'] ?? 0;

                return max($price - $discount, 0);
            });
        });

        return [
            Stat::make('Customers', number_format($totalCustomers)),
            Stat::make('Bookings', number_format($totalBookings)),
            Stat::make('Revenue', 'Rs '.number_format($totalRevenue).'/-'),
        ];
    }
}
