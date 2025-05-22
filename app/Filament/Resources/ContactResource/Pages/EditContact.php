<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Contact;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;
    protected bool $bypassDuplicateCheck = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    { 
        if ($this->bypassDuplicateCheck) {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
            return;
        } 
        
        $this->callHook('beforeValidate');
        $data = $this->form->getState();
        $this->callHook('afterValidate');
        
        $Editemail = $data['email'] ?? null;
        $EditfirstName = $data['first_name'] ?? null;
        $EditlastName = $data['last_name'] ?? null;
        $currentId = $this->record->id;
        $EditFullName = trim("{$EditfirstName} {$EditlastName}  {$Editemail}");

        // Duplicate email check (excluding current record)
        $existingContacts = Contact::where('id', '!=', $currentId)
        ->where(function ($query) use ($Editemail, $EditfirstName, $EditlastName) {
            $query->where('email', $Editemail)
                  ->orWhere(function ($query) use ($EditfirstName, $EditlastName) {
                      $query->where('first_name', $EditfirstName)
                            ->where('last_name', $EditlastName);
                  });
        })
        ->with(['sources.contactList'])
        ->get();

    
        if ($existingContacts->count() > 0) {
            $warningMessage = "The Name or Email you provided already exists in the database.\n\n";
            $warningMessage.= "Found ". $existingContacts->count(). " contact(s) with similar informations:\n\n";
            
            // Enumerate all existing contacts with the same email
            foreach ($existingContacts as $index => $contact) {
                $contactFullNameEmail = trim("{$contact->first_name} {$contact->last_name} {$contact->email}");
                if (empty($contactFullNameEmail)) {
                    $contactFullNameEmail = "Unnamed contact";
                }
                
                // Get lists and sources for this contact
                $listNames = $contact->sources->map(function ($source) {
                    return $source->contactList?->name; // Get the name of the contact list for each source
                })->filter()->unique()->implode(', '); // Remove nulls, get unique names, and join

                $sourceNames = $contact->sources->pluck('name')
                                           ->filter()->unique()->implode(', '); // Get unique source names and join
                
                $warningMessage .= ($index + 1) . ". {$contactFullNameEmail} (ID: {$contact->id})\n";
                
                if (!empty($listNames)) {
                    $warningMessage .= "   - Lists: {$listNames}\n";
                }
                if (!empty($sourceNames)) {
                    $warningMessage .= "   - Sources: {$sourceNames}\n";
                }
                $warningMessage .= "\n";
            }
            
            $warningMessage .= "Do you still want to proceed with the update of {$EditFullName}?";

           Notification::make()
           ->warning()
           ->title('Similar Contact(s) Already Exists')
           ->body(nl2br(htmlspecialchars($warningMessage)))
           ->persistent()
           ->actions([
               Action::make('proceed')
                   ->label('Yes, Update Anyway')
                   ->color('danger')
                   ->button()
                   ->dispatch('proceedUpdateAnyway', ['shouldRedirect' => $shouldRedirect, 'shouldSendSavedNotification' => $shouldSendSavedNotification]),
               Action::make('cancel')
                   ->label('Cancel')
                   ->close(),
           ])
           ->send();
            return; // Halt current create flow, wait for notification interaction
        }
     
        // No duplicates found, proceed with update
        $this->completeUpdateProcess($data, $shouldRedirect, $shouldSendSavedNotification);
    }

    public function completeUpdateProcess(array $data,bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        // Data should be mutated before creation
        $mutatedData = $this->mutateFormDataBeforeSave($data);

        $this->callHook('beforeUpdate');
        $record = $this->handleRecordUpdate($mutatedData);
        $this->form->model($record)->saveRelationships(); // Crucial for saving many-to-many like sources
        $this->callHook('afterUpdate');

        $this->rememberData();

        if ($notification = $this->getCreatedNotification()) {
            $notification->send();
        }

        if ($redirectUrl = $this->getRedirectUrl()) {
            // Determine if SPA navigation can be used
            // MODIFIED LINE:
            $canSpaNavigate = method_exists(Filament::class, 'isSpaEnabled') && Filament::isSpaEnabled() && method_exists($this, 'canSpaNavigate') ? $this->canSpaNavigate() : false;
            $this->redirect($redirectUrl, navigate: $canSpaNavigate);
        }
    }

    protected $listeners = ['proceedUpdateAnyway'];
    public function proceedUpdateAnyway($params = [])
    {
        $this->bypassDuplicateCheck = true;
        $shouldRedirect = $params['shouldRedirect'] ?? true;
        $shouldSendSavedNotification = $params['shouldSendSavedNotification']?? true;
        $this->save($shouldRedirect, $shouldSendSavedNotification);
    }


    public function OLDsave(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    { 
        if ($this->bypassDuplicateCheck) {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
            return;
        } 
        
        $this->callHook('beforeValidate');
        $data = $this->form->getState();
        $this->callHook('afterValidate');
        
        $Editemail = $data['email'] ?? null;
        $EditfirstName = $data['first_name'] ?? null;
        $EditlastName = $data['last_name'] ?? null;
        $currentId = $this->record->id;

        $EditFullName = trim("{$EditfirstName} {$EditlastName}");
        // Duplicate email check (excluding current record)
        $existingContacts = Contact::where('email', $Editemail)
            ->where('id', '!=', $currentId)
            ->with(['sources.contactList'])
            ->get();
    
        if ($existingContacts->count() > 0) {

            $warningMessage = "The email '{$Editemail}' already exists in the database.\n\n";
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
            
            $warningMessage .= "Do you still want to proceed with the update of {$EditFullName}?";

           Notification::make()
           ->warning()
           ->title('Email Already Exists')
           ->body(nl2br(htmlspecialchars($warningMessage)))
           ->persistent()
           ->actions([
               Action::make('proceed')
                   ->label('Yes, Update Anyway')
                   ->color('danger')
                   ->button()
                   ->dispatch('proceedUpdateAnyway', ['shouldRedirect' => $shouldRedirect, 'shouldSendSavedNotification' => $shouldSendSavedNotification]),
               Action::make('cancel')
                   ->label('Cancel')
                   ->close(),
           ])
           ->send();
            return; // Halt current create flow, wait for notification interaction
        }
    
        // Duplicate name check (excluding current record)
        $existingNameContacts = Contact::where('first_name', $EditfirstName)
            ->where('last_name', $EditlastName)
            ->where('id', '!=', $currentId)
            ->with(['sources.contactList'])
            ->get();
    
        if ($existingNameContacts->count() > 0) {
            $warningMessage = "A contact with the name '{$EditFullName}' already exists in the database (even if the email is different).\n\n";
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
    
            $warningMessage .= "Do you still want to proceed with the update of {$EditFullName}?";
    
            Notification::make()
                ->warning()
                ->title('Name Already Exists')
                ->body(nl2br(htmlspecialchars($warningMessage)))
                ->persistent()
                ->actions([
                    Action::make('proceed')
                        ->label('Yes, Update Anyway')
                        ->color('danger')
                        ->button()
                        ->dispatch('proceedUpdateAnyway', ['shouldRedirect' => $shouldRedirect, 'shouldSendSavedNotification' => $shouldSendSavedNotification]),
                    Action::make('cancel')
                        ->label('Cancel')
                        ->close(),
                ])
                ->send();
    
            return; // Halt current update flow, wait for notification interaction
        }
    
        // No duplicates found, proceed with update
        $this->completeUpdateProcess($data, $shouldRedirect, $shouldSendSavedNotification);
    }
    
}
