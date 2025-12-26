<?php

namespace App\Filament\Pages;

use App\Models\PhysicalCard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CardLookup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.card-lookup';

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Card Lookup';

    protected static ?string $title = 'Health Card Lookup';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof \App\Models\User && $user->can('page_CardLookup');
    }

    public ?string $cardNumber = null;

    public ?PhysicalCard $physicalCard = null;

    public function mount(): void
    {
        $this->form->fill([
            'cardNumber' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('cardNumber')
                    ->required()
                    ->label('Card Number')
                    ->placeholder('Enter card serial number')
                    ->helperText('Enter the card serial number to search for customer details')
                    ->autocomplete(false),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->physicalCard)
            ->schema([
                Split::make([
                    Section::make('Card Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('serial_number')
                                        ->label('Serial Number')
                                        ->copyable()
                                        ->icon('heroicon-o-hashtag'),

                                    TextEntry::make('healthCard.title')
                                        ->label('Card Type'),

                                    TextEntry::make('status')
                                        ->badge()
                                        ->color(fn ($state) => $state->color())
                                        ->formatStateUsing(fn ($state) => $state?->label() ?? 'N/A'),

                                    TextEntry::make('expiry_date')
                                        ->label('Expiry Date')
                                        ->date('d M Y')
                                        ->icon('heroicon-o-calendar'),

                                    TextEntry::make('is_active')
                                        ->label('Active Status')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                                        ->color(fn ($state) => $state ? 'success' : 'danger'),

                                    TextEntry::make('customerCard.created_at')
                                        ->label('Activated At')
                                        ->dateTime('d M Y H:i')
                                        ->icon('heroicon-o-clock')
                                        ->visible(fn ($record) => $record?->customerCard !== null),
                                ]),
                        ])
                        ->visible(fn () => $this->physicalCard !== null),

                    Section::make('Customer Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('customerCard.customer.name')
                                        ->label('Name')
                                        ->icon('heroicon-o-user'),

                                    TextEntry::make('customerCard.customer.email')
                                        ->label('Email')
                                        ->icon('heroicon-o-envelope')
                                        ->copyable()
                                        ->placeholder('N/A'),

                                    TextEntry::make('customerCard.customer.mobile_number')
                                        ->label('Mobile Number')
                                        ->icon('heroicon-o-phone')
                                        ->copyable(),

                                    TextEntry::make('customerCard.customer.city.name')
                                        ->label('City')
                                        ->icon('heroicon-o-map-pin')
                                        ->placeholder('N/A'),

                                    TextEntry::make('customerCard.customer.gender')
                                        ->label('Gender')
                                        ->formatStateUsing(fn ($state) => $state?->label() ?? 'N/A'),

                                    TextEntry::make('customerCard.customer.status')
                                        ->label('Customer Status')
                                        ->badge()
                                        ->color(fn ($state) => $state->color())
                                        ->formatStateUsing(fn ($state) => $state?->label() ?? 'N/A'),
                                ]),
                        ])
                        ->visible(fn () => $this->physicalCard?->customerCard !== null),
                ])
                    ->from('md'),
            ]);
    }

    public function searchCard(): void
    {
        $this->validate();
        $cardNumber = $this->cardNumber;

        if (! $cardNumber) {
            Notification::make()
                ->title('Error')
                ->body('Please enter a card number')
                ->danger()
                ->send();

            return;
        }

        $physicalCard = PhysicalCard::with(['healthCard', 'customerCard.customer.city'])
            ->where('serial_number', $cardNumber)
            ->first();

        if (! $physicalCard) {
            Notification::make()
                ->title('Card Not Found')
                ->body('No card found with the serial number: '.$cardNumber)
                ->warning()
                ->send();

            $this->physicalCard = null;

            return;
        }

        $this->physicalCard = $physicalCard;
    }
}
