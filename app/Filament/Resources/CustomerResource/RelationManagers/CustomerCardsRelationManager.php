<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\CustomerCard;
use App\Models\Enum\CustomerRelationship;
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
                Forms\Components\Select::make('physical_card_id')
                    ->label('Physical Card')
                    ->options(function () {
                        return \App\Models\PhysicalCard::assignable($this->ownerRecord->id)
                            ->get()
                            ->filter(fn ($card) => $card->isAssignable())
                            ->mapWithKeys(fn ($card) => [
                                $card->id => sprintf(
                                    '%s (Expires: %s)%s%s',
                                    $card->serial_number,
                                    $card->expiry_date->format('Y-m-d'),
                                    $card->healthCard->max_members > 1 ? ' [Family Card]' : '',
                                    $card->isFamilyCard() ? sprintf(' - %d/%d members', $card->getMemberCount(), $card->healthCard->max_members) : ''
                                )
                            ]);
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->helperText('Available cards and family cards with open slots are shown'),

                Forms\Components\Select::make('relationship_type')
                    ->label('Relationship to Primary Holder')
                    ->options(fn () => collect(CustomerRelationship::cases())
                        ->filter(fn ($case) => $case !== CustomerRelationship::SELF)
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                    )
                    ->required()
                    ->default(CustomerRelationship::CHILD->value)
                    ->visible(fn (Forms\Get $get) => $get('physical_card_id') && CustomerCard::where('physical_card_id', $get('physical_card_id'))->primary()->exists())
                    ->helperText('You are being added as a family member'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('physicalCard.serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('physicalCard.serial_number')
                    ->label('Card Serial')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('card_type')
                    ->label('Card Type')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->isIndividualCard() ? 'Individual' : 'Family')
                    ->color(fn ($record) => $record->isIndividualCard() ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('relationship_type')
                    ->label('Role')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->relationship_type->label())
                    ->color(fn ($record) => $record->is_primary ? 'info' : 'warning'),

                Tables\Columns\TextColumn::make('family_members_count')
                    ->label('Family Members')
                    ->getStateUsing(fn ($record) => $record?->getFamilyMembers()->count() ?? 0)
                    ->badge()
                    ->visible(fn ($record) => $record?->isFamilyCard() ?? false)
                    ->color('success'),

                Tables\Columns\TextColumn::make('physicalCard.expiry_date')
                    ->label('Card Expiry')
                    ->date()
                    ->color(fn ($record) => $record->physicalCard->expiry_date <= now() ? 'danger' : 'primary'),

                Tables\Columns\IconColumn::make('physicalCard.is_active')
                    ->boolean()
                    ->label('Card Active'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (CustomerCard $record) => match (true) {
                        $record->physicalCard->isExpired() => 'Expired',
                        $record->physicalCard->is_active => 'Active',
                        default => 'Deactivated',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Active' => 'success',
                        'Deactivated' => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Activated At')
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
                    ->query(fn (Builder $query): Builder => $query->whereHas('physicalCard', fn ($q) => $q->where('expiry_date', '<=', now())))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['customer_id'] = $this->ownerRecord->id;
                        $hasPrimaryHolder = CustomerCard::where('physical_card_id', $data['physical_card_id'])->primary()->exists();

                        $data['is_primary'] = ! $hasPrimaryHolder;
                        $data['relationship_type'] = $hasPrimaryHolder
                            ? CustomerRelationship::from($data['relationship_type'] ?? CustomerRelationship::CHILD->value)
                            : CustomerRelationship::SELF;

                        return $data;
                    })
                    ->using(fn (array $data) => CustomerCard::activateCard(
                        customerId: $data['customer_id'],
                        physicalCardId: $data['physical_card_id'],
                        isPrimary: $data['is_primary'],
                        relationshipType: $data['relationship_type']
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('view_family')
                    ->label('View Members')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->visible(fn ($record) => $record?->isFamilyCard() ?? false)
                    ->modalHeading('Family Members')
                    ->modalContent(function ($record) {
                        return view('filament.components.family-members-list', [
                            'members' => $record->getFamilyMembers(),
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalWidth('md'),

                Tables\Actions\Action::make('remove')
                    ->label('Remove Card')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn (CustomerCard $record) => $record->removeCard())
                    ->requiresConfirmation()
                    ->modalDescription(fn ($record) => $record->isPrimary() && $record->getFamilyMembers()->count() > 1
                        ? 'Warning: This is a primary cardholder with family members. Remove all family members first.'
                        : 'This will remove the card from the customer and make it available for others.'),
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
