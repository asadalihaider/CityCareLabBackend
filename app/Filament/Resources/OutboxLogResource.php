<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutboxLogResource\Pages;
use App\Models\Enum\OutboxChannel;
use App\Models\Enum\OutboxStatus;
use App\Models\OutboxLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OutboxLogResource extends Resource
{
    protected static ?string $model = OutboxLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Delivery Logs';

    protected static ?string $navigationGroup = 'Outbox';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mobile')
                    ->label('Mobile')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('event')
                    ->label('Event')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('channel')
                    ->label('Channel')
                    ->badge()
                    ->getStateUsing(fn (OutboxLog $record) => $record->channel?->label())
                    ->color(fn (OutboxLog $record) => $record->channel?->color()),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (OutboxLog $record) => $record->status?->label())
                    ->color(fn (OutboxLog $record) => $record->status?->color()),

                TextColumn::make('title')
                    ->label('Title')
                    ->limit(40)
                    ->tooltip(fn (OutboxLog $record) => $record->title),

                TextColumn::make('body')
                    ->label('Body')
                    ->limit(60)
                    ->tooltip(fn (OutboxLog $record) => $record->body)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('channel')
                    ->label('Channel')
                    ->options(OutboxChannel::toOptions()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(OutboxStatus::toOptions()),

                SelectFilter::make('event')
                    ->label('Event')
                    ->options(
                        fn () => OutboxLog::query()
                            ->distinct()
                            ->pluck('event', 'event')
                            ->toArray()
                    ),

                Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),

                Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query) => $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ])),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutboxLogs::route('/'),
        ];
    }
}
