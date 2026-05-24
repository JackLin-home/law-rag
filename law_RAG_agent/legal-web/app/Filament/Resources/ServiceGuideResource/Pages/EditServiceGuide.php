<?php

namespace App\Filament\Resources\ServiceGuideResource\Pages;

use App\Filament\Resources\ServiceGuideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceGuide extends EditRecord
{
    protected static string $resource = ServiceGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
