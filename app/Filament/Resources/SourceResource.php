<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourceResource\Pages;
use App\Filament\Resources\SourceResource\RelationManagers; // We'll create this
use App\Models\Source;
use App\Models\ContactList; // Import ContactList
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SourceResource extends Resource
{
    protected static ?string $model = Source::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square-stack';
    protected static ?int $navigationSort = 3; // To order it in the sidebar
    protected static ?string $navigationGroup = 'Contacts Management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Forms\Get $get, \Illuminate\Validation\Rules\Unique $rule) { // Changed 'callback' to 'modifyRuleUsing'
                            // Source name should be unique within a specific ContactList
                            return $rule->where('contact_list_id', $get('contact_list_id'));
                        }
                    )
                    ->validationMessages([
                        'unique' => 'This source name already exists for the selected contact list.',
                    ]),
                Forms\Components\Select::make('contact_list_id')
                    ->relationship('contactList', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Select the contact list this source belongs to.'),
                // user_id is set automatically in CreateSource page
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contactList.name') // Display ContactList name
                    ->label('Contact List')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contacts_count')
                    ->label('Contacts Count')
                    ->counts('contacts'),
                Tables\Columns\TextColumn::make('user.name') // Owner
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
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
                Tables\Filters\SelectFilter::make('contact_list_id')
                    ->label('Contact List')
                    ->relationship('contactList', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->contacts()->count() === 0),
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
            RelationManagers\ContactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSources::route('/'),
            'create' => Pages\CreateSource::route('/create'),
            'edit' => Pages\EditSource::route('/{record}/edit'),
        ];
    }
}
