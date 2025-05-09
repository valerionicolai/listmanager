<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        // The 'contact_list_id' was a helper field and should not be part of the contact's direct data.
        // The 'source_ids' will be handled by the relationship manager in Filament v3
        // or by syncing the relationship after creation if not using a relation manager directly in the form.
        // Since 'source_ids' is using ->relationship(), Filament should handle it.
        // We remove contact_list_id as it's not a direct field on the Contact model.
        unset($data['contact_list_id']);
        return $data;
    }

    // If you need to handle the many-to-many relationship with sources explicitly after creation
    // (though Filament's ->relationship() on the Select should handle it):
    // protected function afterCreate(): void
    // {
    //     if (!empty($this->data['source_ids'])) {
    //         $this->record->sources()->sync($this->data['source_ids']);
    //     }
    // }
}
