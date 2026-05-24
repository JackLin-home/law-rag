<?php

namespace App\Filament\Resources\PenaltyDecisionResource\Pages;

use App\Filament\Resources\PenaltyDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenaltyDecision extends EditRecord
{
    protected static string $resource = PenaltyDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
