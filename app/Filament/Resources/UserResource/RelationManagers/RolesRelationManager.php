<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\User; // Make sure User model is imported
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; // Add this line
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $recordTitleAttribute = 'name';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('name') // This should be 'id' for the record, 'name' for display
                    ->label('Role')
                    ->relationship('roles', 'name') // This is incorrect for the form context of attaching.
                                                    // It should be a select of existing roles.
                    ->options(Role::pluck('name', 'id')) // Correct: Role name for display, ID for value
                    ->required()
                    // ->unique(ignoreRecord: true, table: 'roles', column: 'name') // Not needed for attaching existing roles
                    ->searchable()
                    ->preload(),
                    // If you want to allow creating roles from here, use createOptionForm,
                    // but the select should target the role ID for attachment.
                    // For AttachAction, the form is usually simpler, often just a Select.
                    // The CreateAction on the relation manager would have a more detailed form if creating roles.
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [ // Define form for attach modal
                        $action->getRecordSelect() // This is the select for choosing which role to attach
                            ->options(Role::query()
                                ->whereNotIn('name', $this->getOwnerRecord()->roles->pluck('name')->all()) // Exclude already attached roles
                                ->pluck('name', 'id')
                            )
                            ->required(),
                    ]),
                // Tables\Actions\CreateAction::make(), // Uncomment if you want to create roles from here
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
                // Tables\Actions\EditAction::make(), // If roles have other editable fields
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    /**
     * This is important to ensure that Admins cannot assign/detach SuperAdmin role
     * or manage roles for SuperAdmin users.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // Ensure $ownerRecord is an instance of User before proceeding
        if (! $ownerRecord instanceof User) {
            return false; // Or handle as appropriate for your application
        }

        $loggedInUser = auth()->user();

        // Ensure loggedInUser is an instance of User as well
        if (! $loggedInUser instanceof User) {
            return false;
        }

        if ($loggedInUser->hasRole('SuperAmministratore')) {
            return true;
        }

        // If the user being edited is a SuperAdmin, non-SuperAdmins cannot manage their roles.
        if ($ownerRecord->hasRole('SuperAmministratore')) {
            return false;
        }

        // Admins can manage roles for other Admins (not SuperAdmins) and Operators.
        if ($loggedInUser->hasRole('Amministratore')) {
            // Check if $ownerRecord is one of the manageable roles
            return $ownerRecord->hasAnyRole(['Amministratore', 'Operatore']);
        }

        return false; // Operators cannot manage roles.
    }


    // The can() method on AttachAction and DetachAction is better for controlling attach/detach logic
    // The following methods (canAttach, canDetach) are for older Filament versions or different contexts.
    // For Filament v3, use the ->can() method on the actions themselves if needed,
    // or rely on the canViewForRecord and general policy.

    // Example of how you might control attach action based on roles:
    // In headerActions:
    // Tables\Actions\AttachAction::make()
    //     ->can(function (RelationManager $livewire) {
    //         $loggedInUser = auth()->user();
    //         $ownerRecord = $livewire->getOwnerRecord();
    //         if ($loggedInUser->hasRole('SuperAmministratore')) return true;
    //         if ($loggedInUser->hasRole('Amministratore') && !$ownerRecord->hasRole('SuperAmministratore')) {
    //             // Further logic: prevent attaching 'SuperAmministratore' role
    //             return true; // Placeholder
    //         }
    //         return false;
    //     })
    //     ->form(...)
}