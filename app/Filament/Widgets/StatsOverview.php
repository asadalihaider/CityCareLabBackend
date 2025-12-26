<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Customer;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof \App\Models\User && $user->can('widget_StatsOverview');
    }

    protected function getStats(): array
    {
        $startDate = $this->filters['fromDate'] ?? null;
        $endDate = $this->filters['toDate'] ?? null;
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $bookings = Booking::query()
            ->when($startDate && $endDate, fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->when(
                $user && $user->city_id && ! $user->isSuperAdmin(),
                fn (Builder $query) => $query->whereHas(
                    'customer',
                    fn (Builder $q) => $q->where('city_id', $user->city_id)
                )
            )
            ->get();

        $totalCustomers = Customer::query()
            ->when($startDate && $endDate, fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->when(
                $user && $user->city_id && ! $user->isSuperAdmin(),
                fn (Builder $query) => $query->where('city_id', $user->city_id)
            )
            ->count();

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
