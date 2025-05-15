<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

class OperatorContactsStat extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = Auth::user();
        if ($user && $user->hasRole('Operatore')) {
            $count = Contact::where('user_id', $user->id)->count();
            return [
                Stat::make('Your Contacts', $count),
            ];
        }
        return [];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('Operatore');
    }
}