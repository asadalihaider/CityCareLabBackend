<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\ExpoPushToken;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExpoPushTokenRelationManager extends RelationManager
{
    protected static string $relationship = 'expoPushTokens';

    protected static ?string $title = 'Notification Tokens';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('token')
                    ->label('Token')
                    ->copyable(),

                Tables\Columns\TextColumn::make('last_used')
                    ->label('Last Used')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->label('Remove Token')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn (ExpoPushToken $record) => $record->delete())
                    ->requiresConfirmation()
                    ->modalDescription('This will remove the token from the customer and make it available for others.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('remove_tokens')
                        ->label('Remove Selected Tokens')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->delete();
                            });
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will remove the selected cards from customers and make them available for others.'),
                ]),
            ])
            ->defaultSort('last_used', 'desc');
    }
}
