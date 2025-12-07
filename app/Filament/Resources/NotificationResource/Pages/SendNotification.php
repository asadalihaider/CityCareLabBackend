<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\Customer;
use App\Models\ExpoToken;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class SendNotification extends Page
{
    protected static string $resource = NotificationResource::class;

    protected static string $view = 'filament.pages.send-notification';

    protected static ?string $title = 'Send Notification';

    public ?array $data = [];

    public function mount(): void
    {
        $customerId = request()->integer('customer_id');

        $defaults = [];

        if ($customerId) {
            $defaults = [
                'recipient_type' => 'specific_customers',
                'specific_customers' => [$customerId],
            ];
        }

        $this->form->fill($defaults);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recipients')
                    ->schema([
                        Forms\Components\Select::make('recipient_type')
                            ->label('Send To')
                            ->options([
                                'all_customers' => 'Registered Customers Only',
                                'anonymous_only' => 'Anonymous Users (Not logged in)',
                                'all_devices' => 'All Devices (Everyone)',
                                'specific_customers' => 'Specific Customers',
                                'city_customers' => 'Customers by City',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('specific_customers', null)),

                        Forms\Components\Select::make('specific_customers')
                            ->label('Select Customers')
                            ->multiple()
                            ->searchable()
                            ->options(fn () => Customer::whereHas('expoTokens')->pluck('name', 'id'))
                            ->visible(fn (callable $get) => $get('recipient_type') === 'specific_customers'),

                        Forms\Components\Select::make('city_id')
                            ->label('Select City')
                            ->searchable()
                            ->options(fn () => \App\Models\OperatingCity::pluck('name', 'id'))
                            ->visible(fn (callable $get) => $get('recipient_type') === 'city_customers'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notification Content')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Title')
                            ->helperText('The main title of the notification'),

                        Forms\Components\TextInput::make('body')
                            ->required()
                            ->maxLength(255)
                            ->label('Message')
                            ->helperText('The notification message body'),

                        Forms\Components\KeyValue::make('data')
                            ->label('Additional Data')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Extra data to send with the notification (JSON format)')
                            ->columnSpan(2),

                        Forms\Components\Checkbox::make('send_immediately')
                            ->label('Send Immediately')
                            ->helperText('Enable to send notifications immediately without batching')
                            ->default(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send')
                ->label('Send Notification')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action(fn () => $this->sendNotification())
                ->requiresConfirmation(function (): bool {
                    $data = $this->form->getState();

                    return $data['send_immediately'] === false;
                }),
        ];
    }

    private function sendNotification(): void
    {
        $data = $this->form->getState();

        try {
            $recipients = $this->getRecipients($data['recipient_type'], $data);

            if ($recipients->isEmpty()) {
                Notification::make()
                    ->title('No Recipients Found')
                    ->body('No devices found for the selected recipient type.')
                    ->danger()
                    ->send();

                return;
            }

            $pushNotification = new \App\Notifications\PushNotification(
                $data['title'],
                $data['body'],
                $data['data'] ?? [],
                ! ($data['send_immediately'] ?? false)
            );

            foreach ($recipients as $recipient) {
                $recipient->notify($pushNotification);
            }

            $message = $data['send_immediately']
                ? "Notification sent immediately to {$recipients->count()} recipients."
                : "Notification queued for {$recipients->count()} recipients. Check the notifications list to process them.";

            Notification::make()
                ->title('Notification Processed Successfully!')
                ->body($message)
                ->success()
                ->send();

            // Redirect back to notifications list
            $this->redirect(NotificationResource::getUrl('index'));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Send Notification')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getRecipients(string $type, array $data): \Illuminate\Support\Collection
    {
        return match ($type) {
            'all_customers' => Customer::whereHas('expoTokens')->get(),
            'anonymous_only' => ExpoToken::anonymous()->get()->map(function ($token) {
                $anonymous = new \Illuminate\Notifications\AnonymousNotifiable;
                $anonymous->route('expo', $token->value);

                return $anonymous;
            }),
            'specific_customers' => Customer::whereIn('id', $data['specific_customers'] ?? [])
                ->whereHas('expoTokens')
                ->get(),
            'city_customers' => Customer::where('city_id', $data['city_id'])
                ->whereHas('expoTokens')
                ->get(),
            'all_devices' => Customer::whereHas('expoTokens')->get()
                ->merge(ExpoToken::anonymous()->get()->map(function ($token) {
                    $anonymous = new \Illuminate\Notifications\AnonymousNotifiable;
                    $anonymous->route('expo', $token->value);

                    return $anonymous;
                })),
            default => collect(),
        };
    }
}
