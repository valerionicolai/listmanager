<?php

namespace App\Filament\Resources\SourceResource\RelationManagers;

use App\Models\Contact; // Import Contact model
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
    protected static string $relationship = 'contacts'; // This uses the 'contacts' relationship defined in Source model
    protected static ?string $recordTitleAttribute = 'email';

    public function form(Form $form): Form
    {
        // This form is primarily for the AttachAction's modal.
        // We don't typically create/edit full contact details here.
        return $form
            ->schema([
                // The AttachAction will provide its own select field.
                // If you needed other fields for attaching, they'd go here.
                // For example, if attaching had intermediate pivot data:
                // Forms\Components\TextInput::make('pivot_notes')->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('last_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('first_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('company_role')
                    ->label('Company Role')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('secondary_email')
                    ->label('Secondary Email')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->searchable()
                ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    // Optionally, you can customize the form for the attach modal
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect() // This is the select for choosing which contact to attach
                            ->optionsLimit(20) // Example: limit options for performance
                            ->helperText('Select existing contacts to associate with this source.'),
                        // Add other fields here if your pivot table has more columns
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->url(fn (Model $record): string => \App\Filament\Resources\ContactResource::getUrl('edit', ['record' => $record])),
                //Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    // To ensure only SuperAdmins/Admins can manage these
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) return false;
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }
}
