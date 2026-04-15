<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutboxLogResource\Pages;
use App\Filament\Resources\OutboxLogResource\Widgets\OutboxStatsWidget;
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
                    ->colors([
                        'gray' => 'IN_APP',
                        'danger' => 'SYSTEM',
                        'primary' => 'API_CLIENT',
                    ]),

                TextColumn::make('attempts')
                    ->label('Attempts')
                    ->getStateUsing(fn (OutboxLog $record) => count($record->attempts ?? []))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status->color()),

                TextColumn::make('title')
                    ->label('Title')
                    ->limit(40)
                    ->tooltip(fn (OutboxLog $record) => $record->title),

                TextColumn::make('scheduled_at')
                    ->label('Scheduled For')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'SYSTEM' => 'System',
                        'IN_APP' => 'In App',
                        'API_CLIENT' => 'API Client',
                    ]),

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
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->infolist([
                        Section::make('Details')->schema([
                            TextEntry::make('mobile')->label('Mobile'),
                            TextEntry::make('event')->label('Event')->badge(),
                            TextEntry::make('status')
                                ->label('Status')
                                ->formatStateUsing(fn (OutboxStatus|string|null $state) => $state instanceof OutboxStatus ? $state->label() : (string) $state)
                                ->badge(),
                            TextEntry::make('response')
                                ->label('Summary')
                                ->placeholder('—')
                                ->columnSpanFull(),
                            TextEntry::make('attempts')
                                ->label('Delivery Attempts')
                                ->getStateUsing(function (OutboxLog $record) {
                                    $attempts = $record->attempts ?? [];

                                    if (! is_array($attempts) || empty($attempts)) {
                                        return $record->processed_at ? 'No attempts recorded.' : '—';
                                    }

                                    return collect($attempts)
                                        ->map(function ($attempt, $index) {
                                            $ch = ucfirst($attempt['channel'] ?? 'system');
                                            $st = ucfirst($attempt['status'] ?? 'unknown');
                                            $reason = $attempt['reason'] ?? '';

                                            $timestamp = $attempt['timestamp'] ?? null;
                                            $timeStr = '';
                                            if ($timestamp) {
                                                $timeStr = ' ('.(\Illuminate\Support\Carbon::parse($timestamp)->format('M d, Y h:i A')).')';
                                            }

                                            return '<strong>'.($index + 1).'. '.$ch.': '.$st.'</strong>'.($reason ? " - $reason" : '').$timeStr;
                                        })
                                        ->implode('<br />');
                                })
                                ->html()
                                ->columnSpanFull(),
                            TextEntry::make('title')
                                ->label('Title')
                                ->placeholder('—'),
                            TextEntry::make('body')
                                ->label('Message Body')
                                ->limit(100)
                                ->tooltip(fn (OutboxLog $record) => $record->body)
                                ->placeholder('—'),
                            KeyValueEntry::make('payload')
                                ->label('Payload')
                                ->columnSpanFull(),
                            TextEntry::make('scheduled_at')
                                ->label('Scheduled For')
                                ->dateTime()
                                ->placeholder('—'),
                            TextEntry::make('processed_at')
                                ->label('Processed At')
                                ->dateTime()
                                ->placeholder('—'),
                            TextEntry::make('created_at')
                                ->label('Created At')
                                ->dateTime(),
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
        $record->update([
            'response' => null,
            'attempts' => null,
            'processed_at' => null,
        ]);
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
