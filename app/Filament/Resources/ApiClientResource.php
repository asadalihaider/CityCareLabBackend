<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiClientResource\Pages;
use App\Models\ApiClient;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApiClientResource extends Resource
{
    protected static ?string $model = ApiClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Clients';

    protected static ?string $navigationGroup = 'Outbox';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Client Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Booking System'),

                TextInput::make('rate_limit')
                    ->label('Rate Limit (req/min)')
                    ->numeric()
                    ->required()
                    ->default(60)
                    ->minValue(1)
                    ->maxValue(1000)
                    ->helperText('Maximum API requests allowed per minute for this client.'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('api_key')
                    ->label('API Key')
                    ->getStateUsing(fn (ApiClient $record) => $record->maskedKey())
                    ->copyable()
                    ->copyableState(fn (ApiClient $record) => $record->api_key)
                    ->tooltip('Click the copy icon to copy the full key'),

                TextColumn::make('rate_limit')
                    ->label('Rate Limit')
                    ->suffix(' req/min')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('regenerate_key')
                    ->label('Regenerate Key')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate API Key')
                    ->modalDescription('This will invalidate the existing key immediately. Any service using the old key will stop working. Proceed?')
                    ->action(function (ApiClient $record) {
                        $record->update(['api_key' => ApiClient::generateApiKey()]);

                        Notification::make()
                            ->title('API key regenerated successfully.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiClients::route('/'),
            'create' => Pages\CreateApiClient::route('/create'),
            'edit' => Pages\EditApiClient::route('/{record}/edit'),
        ];
    }
}
