<?php

namespace App\Filament\Resources\TestCategoryResource\Pages;

use App\Filament\Resources\TestCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestCategory extends EditRecord
{
    protected static string $resource = TestCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
