<?php

namespace App\Filament\Resources\HealthCardResource\RelationManagers;

use App\Models\Enum\PhysicalCardStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhysicalCardsRelationManager extends RelationManager
{
    protected static string $relationship = 'physicalCards';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('serial_number')
                    ->label('Serial Number')
                    ->required()
                    ->unique('physical_cards', 'serial_number', ignoreRecord: true)
                    ->maxLength(50)
                    ->placeholder('BLUE2411051001')
                    ->helperText('Unique identifier printed on physical card'),

                Forms\Components\DatePicker::make('expiry_date')
                    ->label('Expiry Date')
                    ->required()
                    ->default(now()->addYears(2))
                    ->helperText('Set expiry date for API security validation'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive cards cannot be activated for customers'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->color(fn ($record) => $record->expiry_date <= now() ? 'danger' : 'primary'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->status->label())
                    ->color(fn ($record) => $record->status?->color()),

                Tables\Columns\TextColumn::make('member_count')
                    ->label('Members')
                    ->getStateUsing(fn ($record) => $record->getMemberCount())
                    ->badge()
                    ->color(fn ($record) => $record->getMemberCount() > 0 ? 'success' : 'gray')
                    ->visible(fn () => $this->ownerRecord->isFamilyCard()),

                Tables\Columns\TextColumn::make('customerCard.customer.name')
                    ->label('Activated By')
                    ->placeholder('Not activated')
                    ->getStateUsing(function ($record) {
                        if (! $record->isFamilyCard()) {
                            return $record->customerCard?->customer->name;
                        }

                        $primaryHolder = $record->getPrimaryCardholder();

                        return $primaryHolder ? $primaryHolder->customer->name.' (Primary)' : null;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(PhysicalCardStatus::toOptions()),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_cards')
                    ->label('Generate Cards')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Number of Cards')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(10),
                        Forms\Components\TextInput::make('tenure_years')
                            ->label('Card Validity (Years)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(2)
                            ->helperText('Years from today for card expiry'),
                    ])
                    ->action(function (array $data) {
                        $cards = $this->ownerRecord->generatePhysicalCards($data['quantity'], $data['tenure_years']);

                        Notification::make()
                            ->title('Cards Generated Successfully!')
                            ->body('Generated '.count($cards).' physical cards.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => $this->ownerRecord->is_active),
            ])
            ->actions([
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record) {
                        $record->update(['is_active' => true]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => ! $record->is_active),
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->update(['is_active' => false]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->is_active),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Cards')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Cards')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            // Only delete cards that are not activated to customers
                            $eligibleRecords = $records->filter(function ($record) {
                                return ! $record->customerCard;
                            });

                            if ($eligibleRecords->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Cannot delete cards')
                                    ->body('Selected cards are activated to customers and cannot be deleted.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $eligibleRecords->each->delete();

                            \Filament\Notifications\Notification::make()
                                ->title('Cards deleted successfully')
                                ->body($eligibleRecords->count().' cards were deleted.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('Only cards that are not activated to customers will be deleted.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
