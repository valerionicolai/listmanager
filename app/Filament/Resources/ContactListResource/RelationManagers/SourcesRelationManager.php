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
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function ($rule, Forms\Get $get, RelationManager $livewire) { // Changed 'callback' to 'modifyRuleUsing'
                            return $rule->where('contact_list_id', $livewire->ownerRecord->id);
                        }
                    ),
                // contact_list_id is automatically set because it's a relationship
                // user_id will be set in mutateFormDataBeforeCreate
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contacts_count')
                    ->label('Contacts Count')
                    ->counts('contacts'), // This uses the contacts() relationship on the Source model
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // REMOVE: Tables\Actions\AttachAction::make(),
                Tables\Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data) {
                    return $this->mutateFormDataBeforeCreate($data);
                })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->contacts()->count() === 0),
            ]);
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        // dd('mutateFormDataBeforeCreate called', $data);
        $data['user_id'] = Auth::id();
        return $data;
    }
    // To ensure only SuperAdmins/Admins can manage these
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = Auth::user();
        if (!$user instanceof User) return false;
        return $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

}
