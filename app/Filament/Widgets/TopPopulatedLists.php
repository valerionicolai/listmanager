<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use App\Models\ContactList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class TopPopulatedLists extends BaseWidget
{
    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['SuperAmministratore', 'Amministratore'])) {
            return ContactList::query()
                ->select('contact_lists.id', 'contact_lists.name')
                ->selectRaw('COUNT(contact_source.contact_id) as contacts_count')
                ->join('sources', 'contact_lists.id', '=', 'sources.contact_list_id')
                ->join('contact_source', 'sources.id', '=', 'contact_source.source_id')
                ->groupBy('contact_lists.id', 'contact_lists.name')
                ->orderByDesc('contacts_count');
        }
        // Return an empty query for others
        return ContactList::query()->whereRaw('0=1');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->label('List Name'),
            Tables\Columns\TextColumn::make('contacts_count')->label('Contacts Count'),
        ];
    }

    public function getTableRecordsPerPage(): int
    {
        return 5; // Show only top 5 lists
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }
}