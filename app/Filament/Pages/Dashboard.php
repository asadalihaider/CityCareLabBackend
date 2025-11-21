<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading('Filters')
                    ->schema([
                        Select::make('range')
                            ->label('Predefined Range')
                            ->placeholder('Select Range')
                            ->default('this_month')
                            ->options([
                                'this_month' => 'This Month',
                                '3_months' => 'Last 3 Months',
                                '6_months' => 'Last 6 Months',
                                'custom' => 'Custom',
                            ])
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state == 'custom') {
                                    $set('fromDate', null);
                                    $set('toDate', null);
                                } else {
                                    $endDate = now();
                                    switch ($state) {
                                        case 'this_month':
                                            $startDate = now()->startOfMonth();
                                            break;
                                        case '3_months':
                                            $startDate = now()->subMonths(3);
                                            break;
                                        case '6_months':
                                            $startDate = now()->subMonths(6);
                                            break;
                                        default:
                                            $startDate = null;
                                    }
                                    $set('fromDate', $startDate);
                                    $set('toDate', $endDate);
                                }
                            }),
                        DatePicker::make('fromDate')
                            ->label('Start Date')
                            ->native(false)
                            ->visible(fn (callable $get) => $get('range') == 'custom'),
                        DatePicker::make('toDate')
                            ->label('End Date')
                            ->native(false)
                            ->minDate(now())
                            ->visible(fn (callable $get) => $get('range') == 'custom'),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            Widgets\StatsOverview::class,
            Widgets\Bookings::class,
        ];
    }
}
