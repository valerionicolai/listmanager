<?php

namespace App\Filament\Resources\ContactListResource\RelationManagers;

use App\Models\Source; // Import Source model
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

class SourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'sources';
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        // Form for creating/editing sources directly through this relation manager
        // This is usually simpler if main source creation is via SourceResource
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, callback: function ($rule, Forms\Get $get, RelationManager $livewire) {
                        return $rule->where('contact_list_id', $livewire->ownerRecord->id);
                    }),
                // contact_list_id is automatically set because it's a relationship
                // user_id will be set in mutateFormDataBeforeCreate
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name') // Already set above
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
                        return $data;
                    }),
                Tables\Actions\AttachAction::make() // If you want to attach existing sources from other lists (less common for this setup)
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(), // Detach from this list
                Tables\Actions\DeleteAction::make(), // Delete the source entirely
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
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
