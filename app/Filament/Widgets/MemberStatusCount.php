<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB; // ✅ Required for DB::raw()

class MemberStatusCount extends ChartWidget
{
    protected static ?string $heading = 'Member Status Distribution';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';
    protected function getData(): array
    {
        // Get count per status
        $data = Member::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status') // ['active' => 42, 'expired' => 15, ...]
            ->toArray();

        // Extract labels (status names) and values (counts)
        $labels = array_keys($data);   // ['active', 'expired', ...]
        $counts = array_values($data); // [42, 15, ...]

        return [
            'labels' => $labels, // ✅ Must be an array
            'datasets' => [
                [
                    'label' => 'Members',
                    'data' => $counts, // ✅ Array of numbers
                    'backgroundColor' => [
                        '#10B981', // active
                        '#EF4444', // expired
                        '#3B82F6', // pending
                        '#F59E0B', // other
                        // Add more colors if you have more statuses
                    ],
                    'borderColor' => '#FFFFFF',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // or 'doughnut'
    }
}