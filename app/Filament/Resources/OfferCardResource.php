<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfferCardResource\Pages;
use App\Models\OfferCard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OfferCardResource extends Resource
{
    protected static ?string $model = OfferCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Offer Cards';

    protected static ?string $modelLabel = 'Offer Card';

    protected static ?string $pluralModelLabel = 'Offer Cards';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required(),

                TextInput::make('link')
                    ->url()
                    ->required()
                    ->placeholder('https://example.com/discount-cards/card-name'),

                TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->default(500.00)
                    ->suffix('PKR')
                    ->minValue(0),

                Textarea::make('description')
                    ->required()
                    ->rows(3),

                FileUpload::make('image')
                    ->image()
                    ->required()
                    ->directory('offer-cards')
                    ->disk('s3')
                    ->visibility('publico')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
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
                    ->disk('s3')
                    ->square()
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->default('/placeholder.png'),

                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('price')
                    ->money('PKR')
                    ->sortable(),

                TextColumn::make('description')
                    ->limit(50),

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
            ->defaultSort('created_at', 'desc');
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
