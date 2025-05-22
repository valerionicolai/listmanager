<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact; // Added
use App\Models\Source; // Added
// Remove Dialog import
use Filament\Facades\Filament; // Added for SPA navigation check
use Filament\Notifications\Notification; // Add this for notifications
use Filament\Notifications\Actions\Action; // Add this for notification actions

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
    protected bool $bypassDuplicateCheck = false;

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

    // --- ADDED/MODIFIED CODE STARTS HERE ---

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    public function create(bool $another = false): void // Changed from protected to public
    {
        if ($this->bypassDuplicateCheck) {
            parent::create($another);
            return;
        }
        $this->callHook('beforeValidate');
        $data = $this->form->getState();
        $this->callHook('afterValidate');

        $email = $data['email'] ?? null;
        $newFirstName = $data['first_name'] ?? 'the new contact'; // Name from the current form
        $newLastName = $data['last_name'] ?? '';                 // Name from the current form

        if (!$email) {
            // If email is not set (should be caught by validation, but as a safeguard)
            $this->completeCreateProcess($data, $another);
            return;
        }

        // Check if any contacts already exist with this email - get ALL matching contacts
        $existingContacts = Contact::where('email', $email)
                                ->with(['sources.contactList']) // Eager load sources and their contact lists
                                ->get(); // Use get() instead of first() to retrieve all matching contacts

        if ($existingContacts->count() > 0) {
            $newContactFullName = trim("{$newFirstName} {$newLastName}");
            if (empty($newContactFullName)) {
                $newContactFullName = "the new contact you are trying to add";
            }

            $warningMessage = "The email '{$email}' already exists in the database.\n\n";
            $warningMessage .= "Found " . $existingContacts->count() . " contact(s) with this email:\n\n";
            
            // Enumerate all existing contacts with the same email
            foreach ($existingContacts as $index => $contact) {
                $contactFullName = trim("{$contact->first_name} {$contact->last_name}");
                if (empty($contactFullName)) {
                    $contactFullName = "Unnamed contact";
                }
                
                // Get lists and sources for this contact
                $listNames = $contact->sources->map(function ($source) {
                    return $source->contactList?->name; // Get the name of the contact list for each source
                })->filter()->unique()->implode(', '); // Remove nulls, get unique names, and join

                $sourceNames = $contact->sources->pluck('name')
                                           ->filter()->unique()->implode(', '); // Get unique source names and join
                
                $warningMessage .= ($index + 1) . ". {$contactFullName} (ID: {$contact->id})\n";
                
                if (!empty($listNames)) {
                    $warningMessage .= "   - Lists: {$listNames}\n";
                }
                if (!empty($sourceNames)) {
                    $warningMessage .= "   - Sources: {$sourceNames}\n";
                }
                $warningMessage .= "\n";
            }
            
            $warningMessage .= "Do you still want to proceed with adding {$newContactFullName}?";

           Notification::make()
           ->warning()
           ->title('Email Already Exists')
           ->body(nl2br(htmlspecialchars($warningMessage)))
           ->persistent()
           ->actions([
               Action::make('proceed')
                   ->label('Yes, Create Anyway')
                   ->color('danger')
                   ->button()
                   ->dispatch('proceedCreateAnyway', ['another' => $another]),
               Action::make('cancel')
                   ->label('Cancel')
                   ->close(),
           ])
           ->send();
            return; // Halt current create flow, wait for notification interaction
        }
        // --- NEW CONTROL: Check for duplicate first_name + last_name ---
    $firstName = $data['first_name'] ?? null;
    $lastName = $data['last_name'] ?? null;
    $existingNameContacts = Contact::where('first_name', $firstName)
        ->where('last_name', $lastName)
        ->with(['sources.contactList'])
        ->get();

    if ($existingNameContacts->count() > 0) {
        $newContactFullName = trim("{$firstName} {$lastName}");
        if (empty($newContactFullName)) {
            $newContactFullName = "the new contact you are trying to add";
        }

        $warningMessage = "A contact with the name '{$newContactFullName}' already exists in the database (even if the email is different).\n\n";
        $warningMessage .= "Found " . $existingNameContacts->count() . " contact(s) with this name:\n\n";

        foreach ($existingNameContacts as $index => $contact) {
            $contactEmail = $contact->email ?? 'No email';
            $contactFullName = trim("{$contact->first_name} {$contact->last_name}");
            if (empty($contactFullName)) {
                $contactFullName = "Unnamed contact";
            }

            $listNames = $contact->sources->map(function ($source) {
                return $source->contactList?->name;
            })->filter()->unique()->implode(', ');

            $sourceNames = $contact->sources->pluck('name')
                ->filter()->unique()->implode(', ');

            $warningMessage .= ($index + 1) . ". {$contactFullName} (ID: {$contact->id}, Email: {$contactEmail})\n";
            if (!empty($listNames)) {
                $warningMessage .= "   - Lists: {$listNames}\n";
            }
            if (!empty($sourceNames)) {
                $warningMessage .= "   - Sources: {$sourceNames}\n";
            }
            $warningMessage .= "\n";
        }

        $warningMessage .= "Do you still want to proceed with adding {$newContactFullName}?";

        Notification::make()
            ->warning()
            ->title('Name Already Exists')
            ->body(nl2br(htmlspecialchars($warningMessage)))
            ->persistent()
            ->actions([
                Action::make('proceed')
                    ->label('Yes, Create Anyway')
                    ->color('danger')
                    ->button()
                    ->dispatch('proceedCreateAnyway', ['another' => $another]),
                Action::make('cancel')
                    ->label('Cancel')
                    ->close(),
            ])
            ->send();

        return; // Halt current create flow, wait for notification interaction
    }
    // --- END NEW CONTROL ---

        // No existing contact with this email found, proceed directly
        $this->completeCreateProcess($data, $another);
    }

    public function completeCreateProcess(array $data, bool $another = false): void
    {
        // Data should be mutated before creation
        $mutatedData = $this->mutateFormDataBeforeCreate($data);

        $this->callHook('beforeCreate');
        $record = $this->handleRecordCreation($mutatedData);
        $this->form->model($record)->saveRelationships(); // Crucial for saving many-to-many like sources
        $this->callHook('afterCreate');

        $this->rememberData();

        if ($notification = $this->getCreatedNotification()) {
            $notification->send();
        }

        if ($another) {
            $this->form->fill(); // Reset form for creating another
        } elseif ($redirectUrl = $this->getRedirectUrl()) {
            // Determine if SPA navigation can be used
            // MODIFIED LINE:
            $canSpaNavigate = method_exists(Filament::class, 'isSpaEnabled') && Filament::isSpaEnabled() && method_exists($this, 'canSpaNavigate') ? $this->canSpaNavigate() : false;
            $this->redirect($redirectUrl, navigate: $canSpaNavigate);
        }
    }
    protected $listeners = ['proceedCreateAnyway'];
    public function proceedCreateAnyway($params = [])
    {
        $this->bypassDuplicateCheck = true;
        $another = $params['another'] ?? false;
        $this->create($another);
    }
}
