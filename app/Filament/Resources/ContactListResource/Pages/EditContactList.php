<?php

namespace App\Filament\Resources\ContactListResource\Pages;

use App\Filament\Resources\ContactListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactList extends EditRecord
{
    protected static string $resource = ContactListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
