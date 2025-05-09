<?php

namespace App\Filament\Resources\ContactListResource\RelationManagers;

use App\Models\User; // Import User model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; // Import Model
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contactsViaSources'; // Using the new relationship
    protected static ?string $recordTitleAttribute = 'email';

    public function form(Form $form): Form
    {
        // Generally, contacts are not created/edited directly from here.
        // This form would be for actions like AttachAction if enabled.
        return $form
            ->schema([
                Forms\Components\TextInput::make('email') // Example field
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('email') // Already set
            ->columns([
                Tables\Columns\TextColumn::make('last_name')->searchable(),
                Tables\Columns\TextColumn::make('first_name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->searchable(),
                Tables\Columns\TextColumn::make('sources.name') // Show which sources link it to this list
                    ->badge()
                    ->getStateUsing(function (Model $record) {
                        // Get sources that belong to the current ContactList
                        $contactListId = $this->getOwnerRecord()->id;
                        return $record->sources()->where('contact_list_id', $contactListId)->pluck('name');
                    })
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\AttachAction::make(), // Attaching existing contacts can be complex due to source linkage
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->url(fn (Model $record): string => \App\Filament\Resources\ContactResource::getUrl('edit', ['record' => $record])),
                // Detaching a contact from a list means removing it from all sources of that list.
                // This requires custom logic.
                // Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DetachBulkAction::make(),
                // ]),
            ]);
    }

    // To ensure only SuperAdmins/Admins can view these
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) return false;
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

    // Override getTableQuery to use the custom relationship
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->distinct(); // Ensure distinct contacts if multiple sources link them
    }
}
