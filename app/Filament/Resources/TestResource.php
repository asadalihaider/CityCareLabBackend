<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestResource\Pages;
use App\Models\Enum\TestType;
use App\Models\Test;
use App\Models\TestCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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

class TestResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Tests';

    protected static ?string $pluralModelLabel = 'Tests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->placeholder('Enter test name'),

                TextInput::make('short_title')
                    ->label(__('Short Title'))
                    ->placeholder('e.g., CBC, LFT'),

                Select::make('duration')
                    ->label(__('Duration'))
                    ->required()
                    ->options([
                        '9 hours',
                        '24 hours',
                        '2 days',
                        '3 days',
                        'one week',
                        'two weeks',
                        'three weeks',
                    ])
                    ->placeholder('Select duration')
                    ->native(false)
                    ->searchable(),

                TextInput::make('price')
                    ->label(__('Price'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefixIcon('heroicon-o-currency-rupee')
                    ->placeholder('Price in rupees e.g 1400'),

                Select::make('specimen')
                    ->label(__('Specimen'))
                    ->required()
                    ->options([
                        '24 hrs Urine Sample',
                        '3-5 cc Clotted Blood or Serum',
                        '5 ml Blood in CS Bottle',
                        'Biopsy Sample',
                        'Body Stone',
                        'Brest Milk',
                        'CLOT/plain 3 ml',
                        'CLOT/plain/EDTA/Fluride 3 ml',
                        'CP /EDTA 3 ml',
                        'CSF Sample',
                        'FFPs',
                        'Fluid',
                        'Manual Method',
                        'Nasal Swab',
                        'PAP Smear',
                        'PT/citrate 1.8 ml',
                        'PUS Swab',
                        'Semen Sample',
                        'Slides',
                        'Sputam Sample',
                        'Sputum Sample',
                        'Stool Sample',
                        'Urine Sample',
                    ])
                    ->searchable()
                    ->native(false),

                Select::make('type')
                    ->label(__('Test Type'))
                    ->required()
                    ->options(TestType::toOptions())
                    ->native(false),

                Select::make('categories')
                    ->label(__('Test Categories'))
                    ->multiple()
                    ->relationship('categories', 'title')
                    ->placeholder('Select categories')
                    ->helperText('You can select multiple categories'),

                TagsInput::make('includes')
                    ->label(__('Includes'))
                    ->placeholder('Add included tests/procedures')
                    ->helperText('Press enter after each item')
                    ->reorderable(),

                TagsInput::make('relevant_symptoms')
                    ->label(__('Relevant Symptoms'))
                    ->placeholder('Add relevant symptoms')
                    ->helperText('Press enter after each item')
                    ->reorderable(),

                TagsInput::make('relevant_diseases')
                    ->label(__('Relevant Diseases'))
                    ->placeholder('Add relevant diseases')
                    ->helperText('Press enter after each item')
                    ->reorderable(),

                Toggle::make('is_featured')
                    ->label(__('Featured'))
                    ->default(false)
                    ->live()
                    ->helperText('Featured tests will be highlighted on the website/mobile app'),

                FileUpload::make('image')
                    ->label(__('Image'))
                    ->image()
                    ->directory('test-images')
                    ->visibility('public')
                    ->visible(fn ($get) => $get('is_featured') === true)
                    ->required(fn ($get) => $get('is_featured') === true)
                    ->helperText('Required for featured tests')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_featured')
                    ->label(__('Featured'))
                    ->boolean(),

                TextColumn::make('title')
                    ->label(__('Test Name'))
                    ->searchable(),

                TextColumn::make('short_title')
                    ->label(__('Short Title'))
                    ->searchable(),

                TextColumn::make('type')
                    ->label(__('Test Type'))
                    ->badge()
                    ->color(fn ($record): string => $record->type->color()),

                TextColumn::make('categories')
                    ->label(__('Relevant Categories'))
                    ->getStateUsing(function ($record) {
                        return $record->categories->pluck('title')->join(', ');
                    })
                    ->limit(30),

                TextColumn::make('price')
                    ->label(__('Price'))
                    ->money('PKR'),

                TextColumn::make('duration')
                    ->label(__('Duration'))
                    ->limit(30),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('Test Type'))
                    ->options(TestType::toOptions()),

                SelectFilter::make('categories')
                    ->label(__('Test Category'))
                    ->options(TestCategory::active()->pluck('title', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['value'])) {
                            return $query->whereHas('categories', function ($q) use ($data) {
                                $q->where('test_category_id', $data['value']);
                            });
                        }

                        return $query;
                    }),

                Filter::make('is_active')
                    ->label(__('Active Only'))
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Filter::make('is_featured')
                    ->label(__('Featured Only'))
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
            ])
            ->defaultSort('is_featured', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTests::route('/'),
            'create' => Pages\CreateTest::route('/create'),
            'edit' => Pages\EditTest::route('/{record}/edit'),
        ];
    }
}
