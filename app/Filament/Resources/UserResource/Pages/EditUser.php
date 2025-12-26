<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Temporarily store role ID for syncing after user update
     */
    protected ?int $roleId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->record->isSuperAdmin() && \App\Models\User::role('super_admin')->count() === 1),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the current role
        $data['roles'] = $this->record->roles->first()?->id;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store role ID temporarily, remove from data to avoid mass assignment issue
        $this->roleId = $data['roles'] ?? null;
        unset($data['roles']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Sync the role after user is saved
        if ($this->roleId) {
            $this->record->syncRoles([$this->roleId]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
