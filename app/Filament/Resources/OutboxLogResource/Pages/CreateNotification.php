<?php

namespace App\Filament\Resources\OutboxLogResource\Pages;

use App\Filament\Resources\OutboxLogResource;
use App\Models\Customer;
use App\Models\OperatingCity;
use App\Models\OutboxLog;
use App\Support\PakistanMobile;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\URL;

class CreateNotification extends Page
{
    protected static string $resource = OutboxLogResource::class;

    protected static string $view = 'filament.pages.create-notification';

    protected static ?string $title = 'Create Notification';

    #[URL]
    public $customer = '';

    public ?array $data = [];

    public function mount(): void
    {
        if ($this->customer) {
            $this->form->fill([
                'recipient_type' => 'specific_customers',
                'customer_ids' => [$this->customer],
                'channel' => 'expo',
            ]);
        } else {
            $this->form->fill([
                'recipient_type' => 'all_customers',
                'channel' => 'auto',
            ]);
        }
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
                                'all_customers' => 'All Customers',
                                'specific_customers' => 'Specific Customers',
                                'city_customers' => 'Customers by City',
                                'manual' => 'Manual Mobile Number',
                            ])
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('customer_ids', null)),

                        Forms\Components\Select::make('customer_ids')
                            ->label('Select Customers')
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->options(fn () => Customer::whereNotNull('mobile_number')->pluck('name', 'id'))
                            ->visible(fn (Get $get) => $get('recipient_type') === 'specific_customers')
                            ->required(fn (Get $get) => $get('recipient_type') === 'specific_customers'),

                        Forms\Components\Select::make('city_id')
                            ->label('Select City')
                            ->searchable()
                            ->native(false)
                            ->options(fn () => OperatingCity::pluck('name', 'id'))
                            ->visible(fn (Get $get) => $get('recipient_type') === 'city_customers')
                            ->required(fn (Get $get) => $get('recipient_type') === 'city_customers'),

                        Forms\Components\TextInput::make('mobile')
                            ->label('Mobile Number')
                            ->placeholder('923001234567')
                            ->helperText('Include country code, digits only (e.g. 923001234567)')
                            ->tel()
                            ->visible(fn (Get $get) => $get('recipient_type') === 'manual')
                            ->required(fn (Get $get) => $get('recipient_type') === 'manual'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Message')
                    ->schema([
                        Forms\Components\Select::make('channel')
                            ->label('Channel')
                            ->options([
                                'auto' => 'Auto (Expo → WhatsApp → SMS)',
                                'expo' => 'Expo Push Notification',
                                'whatsapp' => 'WhatsApp',
                                'sms' => 'SMS',
                            ])
                            ->native(false)
                            ->required()
                            ->default('auto')
                            ->helperText('Auto tries each channel in order and stops on first success'),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Schedule For')
                            ->nullable()
                            ->seconds(false)
                            ->helperText('Leave empty to send immediately or set to future date/time to schedule'),

                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('body')
                            ->label('Message Body')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('queue')
                ->label('Queue Notification')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action(fn () => $this->queueNotification()),
        ];
    }

    private function queueNotification(): void
    {
        $data = $this->form->getState();
        $mobiles = $this->resolveMobiles($data);

        if (empty($mobiles)) {
            Notification::make()
                ->title('No Recipients Found')
                ->body('No customers with mobile numbers found for the selected recipient type.')
                ->danger()
                ->send();

            return;
        }

        $scheduledAt = $data['scheduled_at'] ? Carbon::parse($data['scheduled_at']) : null;
        $channel = $data['channel'] !== 'auto' ? $data['channel'] : null;

        foreach ($mobiles as $mobile) {
            OutboxLog::create([
                'mobile' => $mobile,
                'event' => 'IN_APP',
                'preferred_channel' => $channel,
                'payload' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                ],
                'scheduled_at' => $scheduledAt,
            ]);
        }

        $label = $scheduledAt
            ? 'Scheduled for '.$scheduledAt->format('M j, Y g:i A')
            : 'Queued for immediate delivery';

        Notification::make()
            ->title("Queued for {$this->countLabel(count($mobiles))}")
            ->body($label)
            ->success()
            ->send();

        $this->redirect(OutboxLogResource::getUrl('index'));
    }

    private function resolveMobiles(array $data): array
    {
        $numbers = match ($data['recipient_type']) {
            'all_customers' => Customer::whereNotNull('mobile_number')
                ->pluck('mobile_number')
                ->toArray(),

            'specific_customers' => Customer::whereIn('id', $data['customer_ids'] ?? [])
                ->whereNotNull('mobile_number')
                ->pluck('mobile_number')
                ->toArray(),

            'city_customers' => Customer::where('city_id', $data['city_id'])
                ->whereNotNull('mobile_number')
                ->pluck('mobile_number')
                ->toArray(),

            'manual' => [PakistanMobile::normalize((string) ($data['mobile'] ?? ''))],

            default => [],
        };

        return collect($numbers)
            ->map(fn ($mobile) => PakistanMobile::normalize((string) $mobile))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function countLabel(int $count): string
    {
        return $count === 1 ? '1 recipient' : "{$count} recipients";
    }
}
