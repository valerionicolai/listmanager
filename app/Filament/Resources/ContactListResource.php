<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactListResource\Pages;
use App\Filament\Resources\ContactListResource\RelationManagers; // We'll create these
use App\Models\ContactList;
use Filament\Forms;
use Filament\Forms\Components\Textarea; // Make sure Textarea is imported if you use it
use Filament\Forms\Components\TextInput; // Or TextInput
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactListResource extends Resource
{
    protected static ?string $model = ContactList::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?int $navigationSort = 2; // To order it in the sidebar

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description') // Or Forms\Components\TextInput::make('description')
                    ->nullable()
                    ->maxLength(65535), // Max length for TEXT type
                    Forms\Components\TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers have higher priority (e.g., 0 is highest).'),
                // user_id is usually handled automatically by mutateFormDataBeforeCreate
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (ContactList $record): ?string => $record->description),
                Tables\Columns\TextColumn::make('user.name') // Owner
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Usually only Admins care who created it
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
            ])
            ->defaultSort('priority', 'asc'); // Sort by priority by default
    }

    public static function getRelations(): array
    {
        return [
             RelationManagers\SourcesRelationManager::class,
             RelationManagers\ContactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactLists::route('/'),
            'create' => Pages\CreateContactList::route('/create'),
            'edit' => Pages\EditContactList::route('/{record}/edit'),
            // 'view' => Pages\ViewContactList::route('/{record}'), // If you want a dedicated view page
        ];
    }
}
