<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\ExpoNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class NotificationResource extends Resource
{
    protected static ?string $model = ExpoNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notifications';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data')
                    ->label('Notification Details')
                    ->getStateUsing(function (ExpoNotification $record) {
                        $data = json_decode($record->data, true);
                        $title = $data['title'] ?? 'N/A';
                        $body = $data['body'] ?? 'N/A';
                        $tokensCount = count($data['to'] ?? []);
                        
                        return "{$title} - {$body} (To: {$tokensCount} devices)";
                    })
                    ->limit(80)
                    ->tooltip(function (ExpoNotification $record) {
                        $data = json_decode($record->data, true);
                        return "Title: " . ($data['title'] ?? 'N/A') . "\n" . 
                               "Body: " . ($data['body'] ?? 'N/A') . "\n" .
                               "Tokens: " . count($data['to'] ?? []);
                    }),

                Tables\Columns\TextColumn::make('tokens_count')
                    ->label('Recipients')
                    ->getStateUsing(function (ExpoNotification $record) {
                        $data = json_decode($record->data, true);
                        return count($data['to'] ?? []);
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Queued At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(function (ExpoNotification $record) {
                        $data = json_decode($record->data, true);
                        
                        $content = "**Title:** " . ($data['title'] ?? 'N/A') . "\n\n";
                        $content .= "**Body:** " . ($data['body'] ?? 'N/A') . "\n\n";
                        $content .= "**Recipients:** " . count($data['to'] ?? []) . " devices\n\n";
                        
                        if (!empty($data['data'])) {
                            $content .= "**Additional Data:**\n```json\n" . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n```\n\n";
                        }
                        
                        $content .= "**Tokens:**\n";
                        foreach ($data['to'] ?? [] as $token) {
                            $content .= "- " . substr($token, 0, 20) . "...\n";
                        }
                        
                        return view('filament::pages.simple-page', ['slot' => str($content)->markdown()]);
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Remove')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Remove Selected')
                        ->requiresConfirmation()
                        ->modalDescription('This will remove the selected notifications from the queue. They will not be sent.'),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('processNotifications')
                    ->label('Process All Notifications')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Process All Queued Notifications')
                    ->modalDescription('This will process all queued notifications and send them to their recipients. Are you sure you want to continue?')
                    ->action(function () {
                        $exitCode = Artisan::call('expo:notifications:send');
                        
                        if ($exitCode === 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Notifications Processed')
                                ->body('All queued notifications have been processed and sent successfully.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Processing Failed')
                                ->body('There was an error processing the notifications. Please check the logs.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('No Queued Notifications')
            ->emptyStateDescription('All notifications have been processed or no notifications have been queued yet.')
            ->emptyStateIcon('heroicon-o-bell-slash');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'send' => Pages\SendNotification::route('/send'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}