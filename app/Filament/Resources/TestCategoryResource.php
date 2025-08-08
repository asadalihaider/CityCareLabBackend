<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestCategoryResource\Pages;
use App\Models\TestCategory;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestCategoryResource extends Resource
{
    protected static ?string $model = TestCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Test Categories';

    protected static ?string $pluralModelLabel = 'Test Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                TextInput::make('icon')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Material Design icon name (e.g., format-list-bulleted)'),

                TextInput::make('category')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Unique category identifier (e.g., full-body)'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('icon'),

                TextColumn::make('category')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestCategories::route('/'),
            'create' => Pages\CreateTestCategory::route('/create'),
            'edit' => Pages\EditTestCategory::route('/{record}/edit'),
        ];
    }
}
