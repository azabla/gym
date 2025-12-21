<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Package;
use App\Models\User;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString(); // e.g., '2025-12-21'
        $expiredCount = Member::where('valid_until', '<', $today)->count();

        // Build URL params for your CUSTOM filter named 'valid_until'
        $filterParams = [
            'tableFilters[valid_until][valid_from]' => '',     // empty = no start date
            'tableFilters[valid_until][valid_until]' => $today, // expire until today
        ];

        $expiredUrl = url('/admin/members') . '?' . http_build_query($filterParams);

        return [
            Stat::make('Total Packages', Package::count())
            ->description('Total number of packages in our gym')
            ->descriptionIcon('heroicon-m-clipboard-document-list', IconPosition::Before)

            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->color('success')
             ->extraAttributes([
                'class' => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors',
                'x-on:click' => "window.location.href = '" . url('/admin/packages') . "'",
            ]),

            //  Stat::make('Total Cashier', User::where('role', 'cashier')->count())
            // ->description('Total Number of cashier ')
            // ->descriptionIcon('heroicon-m-user-group', IconPosition::Before)
            //   ->extraAttributes([
            //     'class' => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors',
            //     'x-on:click' => "window.location.href = '" . url('/admin/User') . "'",
            // ]),

            

            Stat::make('Total Member', Member::count())
            ->description('Total number of members in our gym')
            ->descriptionIcon('heroicon-m-user-group', IconPosition::Before)
            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->color('success')->extraAttributes([
                'class' => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors',
                'x-on:click' => "window.location.href = '" . url('/admin/members') . "'",
            ]),


             Stat::make('Expired Members', $expiredCount)
                ->description('Members who\'s membership has expired')
                 ->descriptionIcon('heroicon-m-shield-exclamation', IconPosition::Before)
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('danger')
                 ->extraAttributes([
                'class' => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors',
                'x-on:click' => "window.location.href = '" . url('/admin/Member') . "'",
            ]),
               
        ];
    }
}
