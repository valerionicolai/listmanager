<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

class DistinctContactsByEmailStat extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['SuperAmministratore', 'Amministratore'])) {
            $count = Contact::distinct('email')->count('email');
            return [
                Stat::make('Distinct Contacts (by Email)', $count),
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