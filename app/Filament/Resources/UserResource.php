<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role; // Import Role
use Filament\Forms\Components\Select; // Import Select
use Filament\Tables\Columns\TextColumn; // Import TextColumn
use App\Filament\Resources\UserResource\RelationManagers\RolesRelationManager; // Import RolesRelationManager

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
                    ->unique(ignoreRecord: true) // Ensure email is unique, ignoring current record on edit
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state)) // Only hash and save if filled
                    ->required(fn (string $context): bool => $context === 'create') // Required only on create
                    ->maxLength(255)
                    ->confirmed(), // Adds password_confirmation field
                Forms\Components\Select::make('roles') // For assigning roles
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload() // Preload options for better UX
                    ->options(Role::all()->pluck('name', 'id')) // Ensure roles are loaded correctly
                    ->helperText('Select roles for this user. SuperAdmin can manage all roles. Admin can manage Admin and Operator roles.')
                    // Logic to disable role options based on current user's role can be complex here.
                    // It's often better handled by policy or by limiting options based on the logged-in user.
                    // For now, SuperAdmin will see all. Admins might need a more restricted list.
                    ->visible(fn () => auth()->user()->hasRole('SuperAmministratore')), // Simplification: only SuperAdmin manages roles directly in form
                                                                                    // More granular control can be added if needed.
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At')
                    ->nullable(),
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
}
