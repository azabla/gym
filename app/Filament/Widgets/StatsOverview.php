<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Package;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $expiredCount = Member::where('valid_until', '<', now())->count();
        return [
            Stat::make('Total Packages', Package::count())
            ->description('The total number of packages which are now in our gym')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->color('success'),
            Stat::make('Total Member', Member::count())
            ->description('The amount of total member which are now in our gym')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->color('success'),

             Stat::make('Expired Members', $expiredCount)
                ->description('Membership has expired')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('danger'),
               
        ];
    }
}
