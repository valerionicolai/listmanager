<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

class TotalContactsStat extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['SuperAmministratore', 'Amministratore'])) {
            $count = Contact::count();
            return [
                Stat::make('Total Contacts', $count),
            ];
        }
        return [];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['SuperAmministratore', 'Amministratore']);
    }
}