<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutboxLogResource\Pages;
use App\Filament\Resources\OutboxLogResource\Widgets\OutboxStatsWidget;
use App\Jobs\ProcessOutboxJob;
use App\Models\Enum\OutboxChannel;
use App\Models\Enum\OutboxStatus;
use App\Models\OutboxLog;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OutboxLogResource extends Resource
{
    protected static ?string $model = OutboxLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Delivery Logs';

    protected static ?string $navigationGroup = 'Outbox';

    protected static ?int $navigationSort = 11;

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

                TextColumn::make('scheduled_at')
                    ->label('Scheduled For')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Pending')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Filter::make('pending')
                    ->label('Pending / Scheduled')
                    ->query(fn (Builder $query) => $query->byStatus(OutboxStatus::PENDING)),

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
                Tables\Actions\ViewAction::make()
                    ->infolist([
                        Section::make('Details')->schema([
                            TextEntry::make('mobile')->label('Mobile'),
                            TextEntry::make('event')->label('Event')->badge(),
                            TextEntry::make('channel')
                                ->label('Channel')
                                ->formatStateUsing(fn (OutboxChannel|string|null $state) => $state instanceof OutboxChannel ? $state->label() : (string) $state),
                            TextEntry::make('status')
                                ->label('Status')
                                ->formatStateUsing(fn (OutboxStatus|string|null $state) => $state instanceof OutboxStatus ? $state->label() : (string) $state)
                                ->badge(),
                            TextEntry::make('response')
                                ->label('Provider Response / Failure Reason')
                                ->placeholder('—')
                                ->columnSpanFull(),
                            KeyValueEntry::make('payload')->label('Payload')->columnSpanFull(),
                            TextEntry::make('scheduled_at')->label('Scheduled For')->dateTime()->placeholder('—'),
                            TextEntry::make('processed_at')->label('Processed At')->dateTime()->placeholder('—'),
                            TextEntry::make('created_at')->label('Created At')->dateTime(),
                        ])->columns('3'),
                    ]),
                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (OutboxLog $record) => $record->status === OutboxStatus::FAILED)
                    ->action(function (OutboxLog $record): void {
                        static::retryRecord($record);

                        Notification::make()
                            ->title('Retry queued')
                            ->body('The failed notification has been queued for retry.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('retry_failed')
                    ->label('Retry Failed')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $failed = $records->filter(fn (OutboxLog $record) => $record->status === OutboxStatus::FAILED);

                        $failed->each(fn (OutboxLog $record) => static::retryRecord($record));

                        Notification::make()
                            ->title('Retries queued')
                            ->body($failed->count().' failed notification(s) queued for retry.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected static function retryRecord(OutboxLog $record): void
    {
        $payload = is_array($record->payload) ? $record->payload : [];

        if ($record->event === 'GENERAL') {
            $payload = array_merge([
                'title' => $record->title,
                'body' => $record->body,
            ], $payload);
        }

        $record->update([
            'status' => OutboxStatus::PENDING,
            'response' => null,
            'processed_at' => null,
            'scheduled_at' => now(),
        ]);

        ProcessOutboxJob::dispatch(
            mobile: $record->mobile,
            event: $record->event,
            data: $payload,
            channel: $record->channel,
            outboxLogId: $record->id,
        )->delay(now());
    }

    public static function getWidgets(): array
    {
        return [
            OutboxStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutboxLogs::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
        ];
    }
}
