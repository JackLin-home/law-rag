<?php

namespace App\Filament\Resources\PolicyInsightResource\Pages;

use App\Filament\Resources\PolicyInsightResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolicyInsight extends EditRecord
{
    protected static string $resource = PolicyInsightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
