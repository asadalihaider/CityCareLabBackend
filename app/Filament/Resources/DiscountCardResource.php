<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountCardResource\Pages;
use App\Models\DiscountCard;
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

class DiscountCardResource extends Resource
{
    protected static ?string $model = DiscountCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Discount Cards';

    protected static ?string $modelLabel = 'Discount Card';

    protected static ?string $pluralModelLabel = 'Discount Cards';

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

                Textarea::make('description')
                    ->required()
                    ->rows(3),

                FileUpload::make('image')
                    ->image()
                    ->required()
                    ->directory('discount-cards')
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
            'index' => Pages\ListDiscountCards::route('/'),
            'create' => Pages\CreateDiscountCard::route('/create'),
            'edit' => Pages\EditDiscountCard::route('/{record}/edit'),
        ];
    }
}
