<?php

namespace App\Filament\Exports;

use App\Models\Test;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TestExporter extends Exporter
{
    protected static ?string $model = Test::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('title'),
            ExportColumn::make('short_title'),
            ExportColumn::make('duration'),
            ExportColumn::make('price'),
            ExportColumn::make('discount'),
            ExportColumn::make('specimen'),
            ExportColumn::make('type')
                ->getStateUsing(fn (Test $record) => $record->type->value),
            ExportColumn::make('includes'),
            ExportColumn::make('prerequisites'),
            ExportColumn::make('relevant_symptoms'),
            ExportColumn::make('relevant_diseases'),
            ExportColumn::make('is_active'),
            ExportColumn::make('is_featured'),
            ExportColumn::make('image'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your test export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
