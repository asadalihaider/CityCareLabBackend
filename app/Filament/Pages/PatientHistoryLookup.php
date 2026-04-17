<?php

namespace App\Filament\Pages;

use App\Services\PathCareSoftApiService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PatientHistoryLookup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.patient-history-lookup';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Patient History';

    protected static ?string $title = 'Patient History Lookup';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof \App\Models\User && $user->can('page_PatientHistoryLookup');
    }

    public ?string $phoneNumber = null;

    public array $patientData = [];

    public bool $hasSearched = false;

    public function mount(): void
    {
        $this->form->fill([
            'phoneNumber' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('phoneNumber')
                    ->required()
                    ->label('Phone Number')
                    ->placeholder('Enter patient phone number (e.g., 03001234567)')
                    ->helperText('Enter the patient phone number to retrieve medical history')
                    ->regex('/^(03)([0-9]{9})$/')
                    ->validationMessages([
                        'regex' => 'The :attribute must be a valid Pakistani mobile number.',
                    ])
                    ->autocomplete(false),
            ]);
    }

    public function searchPatient(): void
    {
        $this->validate();
        $phoneNumber = $this->phoneNumber;

        if (! $phoneNumber) {
            Notification::make()
                ->title('Error')
                ->body('Please enter a phone number')
                ->danger()
                ->send();

            return;
        }

        try {
            $service = new PathCareSoftApiService;
            $data = $service->getPatientTestHistory($phoneNumber);

            if (empty($data)) {
                Notification::make()
                    ->title('No Data Found')
                    ->body('No patient history found for the provided phone number.')
                    ->warning()
                    ->send();

                $this->patientData = [];
                $this->hasSearched = true;

                return;
            }

            $this->patientData = is_array($data) ? $data : [$data];
            $this->hasSearched = true;

            Notification::make()
                ->title('Success')
                ->body('Patient history retrieved successfully.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->patientData = [];
            $this->hasSearched = true;
        }
    }
}
