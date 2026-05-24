<?php

namespace App\Filament\Resources\LegalArticleResource\Pages;

use App\Filament\Resources\LegalArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLegalArticle extends EditRecord
{
    protected static string $resource = LegalArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
