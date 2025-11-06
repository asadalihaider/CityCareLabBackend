<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferCardResource\Pages;
use App\Models\OfferCard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OfferCardResource extends Resource
{
    protected static ?string $model = OfferCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required(),

                TextInput::make('serial_prefix')
                    ->label('Serial Prefix')
                    ->required()
                    ->default('CARD')
                    ->maxLength(10)
                    ->placeholder('BLUE, GOLD, PREMIUM')
                    ->helperText('Prefix used for generating card serial numbers'),

                TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->default(500.00)
                    ->suffix('PKR')
                    ->minValue(0),

                TextInput::make('link')
                    ->url()
                    ->required()
                    ->placeholder('https://example.com/discount-cards/card-name'),

                Textarea::make('description')
                    ->required()
                    ->rows(3),

                TagsInput::make('features')
                    ->label('Features')
                    ->placeholder('Add card features')
                    ->helperText('Press enter after each feature (e.g., "10% discount on lab tests", "Free home collection")')
                    ->reorderable(),

                FileUpload::make('image')
                    ->label(__('Image'))
                    ->image()
                    ->required()
                    ->directory('offer-cards')
                    ->disk('s3')
                    ->visibility('publico')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('s3')
                    ->square()
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->default('/placeholder.png'),

                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('price')
                    ->money('PKR'),

                TextColumn::make('description')
                    ->limit(50),

                TextColumn::make('physical_cards_count')
                    ->label('Physical Cards')
                    ->counts('physicalCards'),

                TextColumn::make('attached_cards_count')
                    ->label('Attached')
                    ->getStateUsing(fn ($record) => $record->physicalCards()
                        ->whereHas('customerCard')
                        ->count()),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($record) => $record->update(['is_active' => false]))
                        ->visible(fn ($record) => $record->is_active)
                        ->requiresConfirmation()
                        ->modalDescription('This will prevent new physical cards from being attached, but existing cards will remain active.'),
                    Tables\Actions\Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($record) => $record->update(['is_active' => true]))
                        ->visible(fn ($record) => ! $record->is_active)
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\OfferCardResource\RelationManagers\PhysicalCardsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfferCards::route('/'),
            'create' => Pages\CreateOfferCard::route('/create'),
            'edit' => Pages\EditOfferCard::route('/{record}/edit'),
        ];
    }
}
