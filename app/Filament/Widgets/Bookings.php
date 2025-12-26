<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Enum\BookingStatus;
use App\Models\Enum\BookingType;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Bookings extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof \App\Models\User && $user->can('widget_Bookings');
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['fromDate'] ?? null;
        $endDate = $this->filters['toDate'] ?? null;
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $table
            ->query(function () use ($startDate, $endDate, $user) {
                return Booking::query()
                    ->with(['customer.city'])
                    ->latest()
                    ->when($startDate && $endDate, fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->when(
                        $user && $user->city_id && ! $user->isSuperAdmin(),
                        fn (Builder $query) => $query->whereHas(
                            'customer',
                            fn (Builder $q) => $q->where('city_id', $user->city_id)
                        )
                    );
            })
            ->poll('60s')
            ->columns([
                TextColumn::make('patient_name')
                    ->label('Patient Name'),

                TextColumn::make('contact_number')
                    ->label('Contact'),

                SelectColumn::make('status')
                    ->label('Status')
                    ->options(fn ($record) => BookingStatus::getOptionsForBookingType($record->booking_type)),

                TextColumn::make('location')
                    ->label(__('Address'))
                    ->limit(40)
                    ->icon('heroicon-o-map-pin')
                    ->url(function (Booking $record) {
                        if ($record->location['latitude'] && $record->location['longitude']) {
                            return 'https://maps.google.com/?q='.$record->location['latitude'].','.$record->location['longitude'];
                        }

                        return null;
                    })
                    ->openUrlInNewTab()
                    ->getStateUsing(fn (Booking $record) => $record->location ?? 'N/A'),

                TextColumn::make('booking_date')
                    ->label('Booking Date')
                    ->dateTime('M j, Y g:i A'),

                TextColumn::make('booking_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->badge()
                    ->color(fn ($state) => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options(BookingStatus::toOptions()),

                SelectFilter::make('booking_type')
                    ->label('Type')
                    ->options(BookingType::toOptions()),
            ])
            ->actions([
                ViewAction::make('booking_details')
                    ->modalContent(fn (Booking $record): View => view(
                        'filament.pages.booking-details',
                        ['booking' => $record]
                    ))
                    ->modalHeading('Booking Details')
                    ->slideOver()
                    ->modalWidth('lg')
                    ->modalCancelAction(false),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
