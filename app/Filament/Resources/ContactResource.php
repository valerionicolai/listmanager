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
        $regexname = '/^(?![\s\-.\'\'])([\p{L}\s\-.\']){1,98}[\p{L}]$/u';
        
        return $form
            ->schema([
                
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->regex($regexname)
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->regex($regexname)
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Forms\Get $get, \Illuminate\Validation\Rules\Unique $rule) { // Corrected Unique type hint
                            return $rule->where('first_name', $get('first_name'))
                                        ->where('last_name', $get('last_name'));
                        }
                    )
                    ->validationMessages([
                        'unique' => function (Forms\Get $get) {
                            $existing = Contact::where('email', $get('email'))
                                ->where('first_name', $get('first_name'))
                                ->where('last_name', $get('last_name'))
                                ->with('sources.contactList')  // ✅ Correct relationship name
                                ->first();
                            if ($existing && $existing->sources->count() > 0) {
                                $listNames = $existing->sources
                                    ->map(fn($source) => $source->contactList?->name)
                                    ->filter()
                                    ->unique()
                                    ->join(', ');
                                return "A contact with this first name, last name, and email already exists in the following lists: {$listNames}.";
                            }

                            return "A contact with this first name, last name, and email already exists.";                           
                        }
                    ]),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('company_role')
                    ->nullable()
                    ->regex($regexname)
                    ->maxLength(255),
                Forms\Components\TextInput::make('secondary_email')
                    ->email()
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->visible(fn () => !auth()->user()->hasRole('Operatore'))
                    ->nullable()
                    ->columnSpanFull(),
               
                Forms\Components\Select::make('source_ids')
                    ->label('Sources')
                    ->relationship(name: 'sources', titleAttribute: 'name')
                    ->options(function (): Collection {
                        // Fetch all sources and format them to include their contact list name
                        return Source::with('contactList')->get()->mapWithKeys(function ($source) {
                            $listName = $source->contactList ? $source->contactList->name : 'No List';
                            return [$source->id => " {$listName} ({$source->name})"];
                        });
                    })
                    ->preload()
                    ->searchable()
                    //->multiple(fn (string $context): bool => $context === 'edit') // Multiple selection only in edit form
                    ->multiple()
                    ->helperText(function (string $context): string {
                        return $context === 'edit'
                            ? 'Select one or more sources. Sources are shown with their respective contact lists.'
                            : 'Select a source. Sources are shown with their respective contact lists.';
                    })
                    ->required() // Keep it required in all contexts
                    ->maxItems(fn (string $context): int => $context === 'create' ? 1 : 10) // Limit to 1 item in create context
                    ->minItems(1) 
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sources.contactList.name')
                    ->label('Contact List')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sources.contactList.priority')
                ->label('List Priority')
                ->badge(),
                Tables\Columns\TextColumn::make('sources.name')
                    ->label('Sources')
                    ->badge()
                    ->searchable(),
                    Tables\Columns\TextColumn::make('company_role')
                    ->label('Company Role')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('secondary_email')
                    ->label('Secondary Email')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notes')
                    ->visible(fn () => !auth()->user()->hasRole('Operatore'))
                    ->label('Notes')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\Filter::make('first_name')
                    ->form([
                        Forms\Components\TextInput::make('first_name')->label('First Name'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['first_name']) {
                            $query->where('first_name', 'like', '%' . $data['first_name'] . '%');
                        }
                    }),
    
                Tables\Filters\Filter::make('last_name')
                    ->form([
                        Forms\Components\TextInput::make('last_name')->label('Last Name'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['last_name']) {
                            $query->where('last_name', 'like', '%' . $data['last_name'] . '%');
                        }
                    }),
    
                Tables\Filters\Filter::make('email')
                    ->form([
                        Forms\Components\TextInput::make('email')->label('Email'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['email']) {
                            $query->where('email', 'like', '%' . $data['email'] . '%');
                        }
                    }),
    
                Tables\Filters\MultiSelectFilter::make('lists')
                    ->label('List')
                    ->relationship('sources.contactList', 'name'),
    
                Tables\Filters\MultiSelectFilter::make('sources')
                    ->label('Source')
                    ->relationship('sources', 'name'),
    
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name'),
    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Date From'),
                        Forms\Components\DatePicker::make('to')->label('Date To'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['from']) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }
                        if ($data['to']) {
                            $query->whereDate('created_at', '<=', $data['to']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    Tables\Actions\BulkAction::make('export_excel')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Export logic here
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\ContactsExport($records),
                                'contacts.xlsx'
                            );
                        }),
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
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['SuperAmministratore', 'Amministratore', 'Operatore']);
    }
}
