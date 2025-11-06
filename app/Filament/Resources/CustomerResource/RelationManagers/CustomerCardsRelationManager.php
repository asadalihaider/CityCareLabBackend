<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\CustomerCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerCardsRelationManager extends RelationManager
{
    protected static string $relationship = 'customerCards';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('discount_card_id')
                    ->label('Discount Card')
                    ->relationship('discountCard', 'serial_number',
                        fn (Builder $query) => $query->available()
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $expiry = $record->expiry_date->format('Y-m-d');

                        return $record->serial_number.' (Expires: '.$expiry.')';
                    })
                    ->helperText('Only available and active cards are shown'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('discountCard.serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('discountCard.serial_number')
                    ->label('Card Serial')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('discountCard.expiry_date')
                    ->label('Card Expiry')
                    ->date()
                    ->color(fn ($record) => $record->discountCard->expiry_date <= now() ? 'danger' : 'primary'),

                Tables\Columns\IconColumn::make('discountCard.is_active')
                    ->boolean()
                    ->label('Card Active'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (CustomerCard $record) {
                        if (! $record->discountCard->is_active) {
                            return 'Card Deactivated';
                        }
                        if ($record->discountCard->isExpired()) {
                            return 'Expired';
                        }
                        if ($record->discountCard->status === \App\Models\Enum\DiscountCardStatus::ATTACHED) {
                            return 'Active';
                        }

                        return 'Unknown';
                    })
                    ->colors([
                        'success' => 'Active',
                        'warning' => 'Card Deactivated',
                        'danger' => ['Expired', 'Unknown'],
                    ])
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Attached At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active_cards')
                    ->label('Active Cards Only')
                    ->query(fn (Builder $query): Builder => $query->active())
                    ->toggle(),

                Tables\Filters\Filter::make('expired_cards')
                    ->label('Expired Cards')
                    ->query(fn (Builder $query): Builder => $query->whereHas('discountCard', fn ($q) => $q->where('expiry_date', '<=', now())))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // Auto-set customer_id
                        $data['customer_id'] = $this->ownerRecord->id;

                        return $data;
                    })
                    ->using(function (array $data) {
                        // Use the attachCard method
                        return CustomerCard::attachCard($data['customer_id'], $data['discount_card_id']);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->label('Remove Card')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (CustomerCard $record) {
                        $record->removeCard();
                    })
                    ->requiresConfirmation()
                    ->modalDescription('This will remove the card from the customer and make it available for others.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('remove_cards')
                        ->label('Remove Selected Cards')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->removeCard();
                            });
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will remove the selected cards from customers and make them available for others.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
