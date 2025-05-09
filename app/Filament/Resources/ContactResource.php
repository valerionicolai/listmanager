<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
// use App\Filament\Resources\ContactResource\RelationManagers; // We might add one for Sources later if needed directly on Contact
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Source;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get; // For reactive fields
use Filament\Forms\Set; // For reactive fields
use Illuminate\Validation\Rules\Unique; // For custom unique validation
use Illuminate\Support\Collection; // For reactive source options

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        callback: function (Unique $rule, Get $get) {
                            return $rule->where('first_name', $get('first_name'))
                                        ->where('last_name', $get('last_name'));
                        }
                    )
                    ->validationMessages([
                        'unique' => 'A contact with this first name, last name, and email already exists.',
                    ]),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('company_role')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('secondary_email')
                    ->email()
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\Select::make('contact_list_id') // Temporary field to select a ContactList
                    ->label('Contact List (Lista)')
                    ->options(ContactList::orderBy('priority')->pluck('name', 'id'))
                    ->searchable()
                    ->live() // Make it reactive
                    ->afterStateUpdated(fn (Set $set) => $set('source_ids', null)) // Reset sources when list changes
                    ->helperText('Select the primary list for this contact. Sources will be filtered based on this list.')
                    ->required(fn (string $context): bool => $context === 'create') // Required on create
                    ->dehydrated(false) // Don't save this directly to contact, it's for filtering sources
                    ->columnSpanFull(),
                Forms\Components\Select::make('source_ids') // Changed from source_id to source_ids for multiple selection
                    ->label('Sources')
                    ->multiple() // Allow selecting multiple sources
                    ->relationship(name: 'sources', titleAttribute: 'name') // Define relationship for saving
                    ->options(function (Get $get): Collection {
                        $contactListId = $get('contact_list_id');
                        if (!$contactListId) {
                            return collect(); // Return empty collection if no list is selected
                        }
                        // Fetch sources belonging to the selected contact list
                        return Source::where('contact_list_id', $contactListId)->pluck('name', 'id');
                    })
                    ->preload()
                    ->searchable()
                    ->helperText('Select one or more sources. These are filtered by the selected Contact List.')
                    ->required(fn (string $context): bool => $context === 'create') // Required on create
                    ->columnSpanFull(),
                // user_id is set automatically in CreateContact page
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name') // Owner
                    ->label('Owner')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sources.name') // Display associated sources
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers can be added here if needed, e.g., for managing sources directly.
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    /**
     * Scope the query for Operators to only see their own contacts.
     * SuperAdmins and Admins see all (handled by viewAny policy and Gate::before).
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        if ($user->hasRole('Operatore') && !$user->hasAnyRole(['SuperAmministratore', 'Amministratore'])) {
            return parent::getEloquentQuery()->where('user_id', $user->id);
        }
        return parent::getEloquentQuery();
    }
}
