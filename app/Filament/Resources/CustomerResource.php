<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\Enum\CustomerStatus;
use App\Models\Enum\Gender;
use App\Models\OperatingCity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->placeholder('Enter customer name'),
                TextInput::make('mobile_number')
                    ->label(__('Mobile Number'))
                    ->required()
                    ->placeholder('Enter customer mobile number'),
                TextInput::make('email')
                    ->label(__('Email'))
                    ->required()
                    ->placeholder('Enter customer email'),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->required()
                    ->placeholder('Enter customer password')
                    ->password()
                    ->hiddenOn('edit'),

                DatePicker::make('dob')
                    ->label(__('Date of Birth'))
                    ->native(false)
                    ->required()
                    ->maxDate(now())
                    ->placeholder('Enter customer date of birth'),

                Select::make('gender')
                    ->label(__('Gender'))
                    ->options(Gender::toOptions()),

                Select::make('location_id')
                    ->label(__('Location'))
                    ->required()
                    ->relationship('location', 'name')
                    ->native(false),

                FileUpload::make('image')
                    ->label(__('Image'))
                    ->image()
                    ->disk('s3')
                    ->visibility('publico')
                    ->directory('customers')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->helperText('Required for featured tests. Max size: 2MB'),

                Select::make('status')
                    ->label(__('Status'))
                    ->options(CustomerStatus::toOptions())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                TextColumn::make('mobile_number')
                    ->label(__('Mobile'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('dob')
                    ->label(__('Date of Birth'))
                    ->date('d M, Y'),
                TextColumn::make('location_id')
                    ->label(__('Location'))
                    ->getStateUsing(fn (Customer $record) => $record->location?->name),
                TextColumn::make('gender')
                    ->label(__('Gender'))
                    ->getStateUsing(fn (Customer $record) => $record->gender?->label()),
            ])
            ->filters([
                SelectFilter::make('gender')
                    ->label(__('Gender'))
                    ->options(Gender::toOptions()),

                SelectFilter::make('location')
                    ->label(__('Location'))
                    ->options(OperatingCity::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['value'])) {
                            return $query->where('location_id', $data['value']);
                        }

                        return $query;
                    }),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(CustomerStatus::toOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['value'])) {
                            return $query->where('status', $data['value']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
