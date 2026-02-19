<?php

namespace App\Filament\Resources\PsychologueResource\Pages;

use App\Filament\Resources\PsychologueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPsychologue extends EditRecord
{
    protected static string $resource = PsychologueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
