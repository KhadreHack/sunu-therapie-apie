<?php

namespace App\Filament\Resources\DisponibiliteResource\Pages;

use App\Filament\Resources\DisponibiliteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDisponibilite extends EditRecord
{
    protected static string $resource = DisponibiliteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
