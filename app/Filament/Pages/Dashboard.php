<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\Enum\BookingStatus;
use App\Models\Enum\BookingType;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Booking::query()->latest())
            ->heading('Bookings')
            ->description('List of all recent bookings')
            ->poll('60s')
            ->paginated()
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
                            return 'https://maps.google.com/?q=' . $record->location['latitude'] . ',' . $record->location['longitude'];
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

                SelectFilter::make('date_range')
                    ->label('Date Range')
                    ->options([
                        'today' => 'Today',
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereDate('created_at', today()),
                            'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                            'this_month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                            default => $query,
                        };
                    })
                    ->default('today'),
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
