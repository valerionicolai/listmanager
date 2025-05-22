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
                

                $warningMessage .= ($index + 1) . ". {$contactFullNameEmail} (ID: {$contact->id})\n";
                
          
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

    
}
