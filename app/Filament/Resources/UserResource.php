<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput; // Ensure TextInput is imported
use Illuminate\Support\Facades\Hash; // Ensure Hash is imported
use Spatie\Permission\Models\Role; // Import Role
use Filament\Forms\Components\Select; // Import Select
use Filament\Tables\Columns\TextColumn; // Import TextColumn
use App\Filament\Resources\UserResource\RelationManagers\RolesRelationManager; // Import RolesRelationManager
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Changed icon

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true), // Ensure unique email, ignoring the current record on edit
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At')
                    ->nullable(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context): bool => $context === 'create') // Only required on create
                    ->dehydrated(fn ($state) => filled($state)) // Only send to backend if filled (for updates)
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)) // Hash password before saving
                    ->confirmed() // This will automatically look for 'password_confirmation'
                    ->maxLength(255),
                TextInput::make('password_confirmation')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context): bool => $context === 'create') // Only required on create
                    ->dehydrated(false) // Don't save this field to the database
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->required()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->options(function () {
                        $loggedInUser = auth()->user();
                        $rolesQuery = \Spatie\Permission\Models\Role::query();
                        if ($loggedInUser && $loggedInUser->hasRole('Amministratore')) {
                            $rolesQuery->where('name', '!=', 'SuperAmministratore');
                        }
                        return $rolesQuery->pluck('name', 'id');
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->helperText('Select roles for the user. SuperAmministratore can only be managed by another SuperAmministratore.')
                    ->hidden(function () {
                        $loggedInUser = auth()->user();
                        return !($loggedInUser->hasRole('SuperAmministratore') || $loggedInUser->hasRole('Amministratore'));
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name') // Display roles
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
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
                // You can add filters here, e.g., by role
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
            RolesRelationManager::class, // Add this for managing roles
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user = auth()->user();

    if ($user->hasRole('SuperAmministratore')) {
        return $query;
    }

    if ($user->hasRole('Amministratore')) {
        // Only show Amministratore and Operatore users
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['Amministratore', 'Operatore']);
        });
    }

    // Operators should not see any users
    return $query->whereRaw('0=1');
}


    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }

}
