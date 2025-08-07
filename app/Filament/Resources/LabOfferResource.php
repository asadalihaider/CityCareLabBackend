<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabOfferResource\Pages;
use App\Models\LabOffer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LabOfferResource extends Resource
{
    protected static ?string $model = LabOffer::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Lab Offers';

    protected static ?string $modelLabel = 'Lab Offer';

    protected static ?string $pluralModelLabel = 'Lab Offers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('link')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('https://example.com/offers/special-offer'),

                FileUpload::make('image')
                    ->image()
                    ->directory('lab-offers')
                    ->disk('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->required()
                    ->previewable(true),

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
                    ->disk('public')
                    ->square()
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->default('/placeholder.png'),

                TextColumn::make('link')
                    ->limit(50),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active offers only')
                    ->falseLabel('Inactive offers only')
                    ->native(false),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabOffers::route('/'),
            'create' => Pages\CreateLabOffer::route('/create'),
            'edit' => Pages\EditLabOffer::route('/{record}/edit'),
        ];
    }
}
