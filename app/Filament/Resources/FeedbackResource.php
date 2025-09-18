<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedbackResource\Pages;
use App\Models\Enum\FeedbackCategory;
use App\Models\Enum\FeedbackStatus;
use App\Models\Feedback;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label(__('Customer')),
                TextColumn::make('subject')
                    ->label(__('Subject')),
                TextColumn::make('message')
                    ->label(__('Message'))
                    ->limit(50)
                    ->tooltip(fn (Feedback $record) => $record->message),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->getStateUsing(fn (Feedback $record) => $record->status?->label())
                    ->badge()
                    ->color(fn (Feedback $record) => $record->status?->color()),
                TextColumn::make('category')
                    ->label(__('Category'))
                    ->getStateUsing(fn (Feedback $record) => $record->category?->label()),
                TextColumn::make('rating')
                    ->label(__('Rating'))
                    ->icon('heroicon-o-star')
                    ->iconColor('warning')
                    ->sortable(),
                IconColumn::make('is_anonymous')
                    ->label(__('Anonymous'))
                    ->boolean(),
                TextColumn::make('contact_email')
                    ->label(__('Contact Email')),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label(__('Rating'))
                    ->options([
                        '1' => '1 Star',
                        '2' => '2 Stars',
                        '3' => '3 Stars',
                        '4' => '4 Stars',
                        '5' => '5 Stars',
                    ])
                    ->multiple(),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(FeedbackStatus::toOptions())
                    ->multiple(),
                SelectFilter::make('category')
                    ->label(__('Category'))
                    ->options(FeedbackCategory::toOptions())
                    ->multiple(),

                Filter::make('is_anonymous')
                    ->label('Anonymous Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_anonymous', true)),
            ])
            ->bulkActions([
                BulkAction::make('update_status')
                    ->label(__('Update Status'))
                    ->form([
                        Select::make('status')
                            ->label(__('Status'))
                            ->options(FeedbackStatus::toOptions())
                            ->required(),
                    ])
                    ->modalWidth('md')
                    ->action(function (array $data, $records) {
                        foreach ($records as $record) {
                            $record->update(['status' => $data['status']]);
                        }
                    })
                    ->icon('heroicon-o-pencil-square')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedback::route('/'),
        ];
    }
}
