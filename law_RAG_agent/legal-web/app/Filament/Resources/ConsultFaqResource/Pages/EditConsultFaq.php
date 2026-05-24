<?php

namespace App\Filament\Resources\ConsultFaqResource\Pages;

use App\Filament\Resources\ConsultFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultFaq extends EditRecord
{
    protected static string $resource = ConsultFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
