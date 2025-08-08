<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabCenterResource\Pages;
use App\Models\LabCenter;
use App\Models\OperatingCity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LabCenterResource extends Resource
{
    protected static ?string $model = LabCenter::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Lab Centers';

    protected static ?string $pluralModelLabel = 'Lab Centers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('address')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('phone')
                    ->label('Primary Phone')
                    ->required()
                    ->maxLength(255),

                TextInput::make('secondary_phone')
                    ->label('Secondary Phone')
                    ->maxLength(255),

                TextInput::make('rating')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(5)
                    ->default(0.0),

                Select::make('operating_city_id')
                    ->label('Operating City')
                    ->options(OperatingCity::active()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),

                TextColumn::make('address')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('phone'),

                TextColumn::make('secondary_phone')
                    ->label('Secondary Phone')
                    ->placeholder('N/A'),

                TextColumn::make('rating')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),

                TextColumn::make('operatingCity.name')
                    ->label('City'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->searchPlaceholder('Search Address')
            ->filters([
                SelectFilter::make('operating_city_id')
                    ->label('City')
                    ->options(OperatingCity::active()->pluck('name', 'id'))
                    ->searchable(),

                Filter::make('is_active')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->defaultSort('rating', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabCenters::route('/'),
            'create' => Pages\CreateLabCenter::route('/create'),
            'edit' => Pages\EditLabCenter::route('/{record}/edit'),
        ];
    }
}
