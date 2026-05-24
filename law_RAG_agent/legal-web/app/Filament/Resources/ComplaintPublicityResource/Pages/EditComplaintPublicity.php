<?php

namespace App\Filament\Resources\ComplaintPublicityResource\Pages;

use App\Filament\Resources\ComplaintPublicityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComplaintPublicity extends EditRecord
{
    protected static string $resource = ComplaintPublicityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
