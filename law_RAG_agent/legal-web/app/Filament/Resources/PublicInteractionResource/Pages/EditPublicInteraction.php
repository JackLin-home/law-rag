<?php

namespace App\Filament\Resources\PublicInteractionResource\Pages;

use App\Filament\Resources\PublicInteractionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPublicInteraction extends EditRecord
{
    protected static string $resource = PublicInteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
