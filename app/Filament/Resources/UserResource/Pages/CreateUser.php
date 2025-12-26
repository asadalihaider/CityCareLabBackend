<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Temporarily store role ID for syncing after user creation
     */
    protected ?int $roleId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store role ID temporarily, remove from data to avoid mass assignment issue
        $this->roleId = $data['roles'] ?? null;
        unset($data['roles']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync the role after user is created
        if ($this->roleId) {
            $this->record->syncRoles([$this->roleId]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
