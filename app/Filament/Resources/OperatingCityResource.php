<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperatingCityResource\Pages;
use App\Models\OperatingCity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OperatingCityResource extends Resource
{
    protected static ?string $model = OperatingCity::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Operating Cities';

    protected static ?string $modelLabel = 'Operating City';

    protected static ?string $pluralModelLabel = 'Operating Cities';

    public static function form(Form $form): Form
    {
        $pakistaniProvinces = [
            'Punjab' => 'Punjab',
            'Sindh' => 'Sindh',
            'Khyber Pakhtunkhwa' => 'Khyber Pakhtunkhwa',
            'Balochistan' => 'Balochistan',
            'Gilgit-Baltistan' => 'Gilgit-Baltistan',
            'Azad Kashmir' => 'Azad Kashmir',
            'Islamabad Capital Territory' => 'Islamabad Capital Territory',
        ];

        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter city name'),

                Select::make('province')
                    ->required()
                    ->options($pakistaniProvinces)
                    ->searchable()
                    ->placeholder('Select province'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('province')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('province')
                    ->options([
                        'Punjab' => 'Punjab',
                        'Sindh' => 'Sindh',
                        'Khyber Pakhtunkhwa' => 'Khyber Pakhtunkhwa',
                        'Balochistan' => 'Balochistan',
                        'Gilgit-Baltistan' => 'Gilgit-Baltistan',
                        'Azad Kashmir' => 'Azad Kashmir',
                        'Islamabad Capital Territory' => 'Islamabad Capital Territory',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active cities only')
                    ->falseLabel('Inactive cities only')
                    ->native(false),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperatingCities::route('/'),
            'create' => Pages\CreateOperatingCity::route('/create'),
            'edit' => Pages\EditOperatingCity::route('/{record}/edit'),
        ];
    }
}
