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
                    ->required()
                    ->placeholder('Enter test name'),

                TextInput::make('short_title')
                    ->label('Short Title')
                    ->placeholder('e.g., CBC, LFT'),

                Select::make('categories')
                    ->label('Test Categories')
                    ->multiple()
                    ->relationship('categories', 'title')
                    ->placeholder('Select categories')
                    ->helperText('You can select multiple categories'),

                Select::make('specimen')
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

                TextInput::make('duration')
                    ->required()
                    ->placeholder('e.g., Reports within 9 hours'),

                Select::make('type')
                    ->required()
                    ->options(TestType::toOptions())
                    ->live()
                    ->native(false),

                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefixIcon('heroicon-o-currency-rupee')
                    ->placeholder('Price in rupees e.g 1400'),

                TextInput::make('sale_price')
                    ->label('Sale Price')
                    ->numeric()
                    ->minValue(0)
                    ->prefixIcon('heroicon-o-currency-rupee')
                    ->placeholder('Sale price in rupees e.g 1400'),

                TagsInput::make('includes')
                    ->placeholder('Add included tests/procedures')
                    ->helperText('Press enter after each item')
                    ->reorderable(),

                FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->directory('test-images')
                    ->visibility('public')
                    ->required(fn ($get) => $get('type') === TestType::PACKAGE->value)
                    ->helperText('Required for package type tests')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                Toggle::make('is_featured')
                    ->label('Featured')
                    ->default(false)
                    ->helperText('Mark this test as featured'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),

                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('short_title')
                    ->label('Short Title')
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn ($record): string => $record->type->color()),

                TextColumn::make('categories')
                    ->label('Categories')
                    ->getStateUsing(function ($record) {
                        return $record->categories->pluck('title')->join(', ');
                    })
                    ->limit(30),

                TextColumn::make('price')
                    ->money('PKR'),

                TextColumn::make('sale_price')
                    ->label('Sale Price')
                    ->money('PKR'),

                TextColumn::make('duration')
                    ->limit(30),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(TestType::toOptions()),

                SelectFilter::make('categories')
                    ->label('Test Category')
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
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Filter::make('is_featured')
                    ->label('Featured Only')
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
