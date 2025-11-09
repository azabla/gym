<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MemberListChart extends ChartWidget
{
    protected static ?string $heading = 'Member Activity';
    protected static ?int $sort = 3;
  
    // Must be public so Livewire can update it
    public ?string $filter = 'month'; // default

    protected function getData(): array
    {
        return match ($this->filter) {
            // 'day' => $this->getDataForDay(),
            'week' => $this->getDataForWeek(),
            'month' => $this->getDataForMonth(),
            'last_6_months' => $this->getDataForLast6Months(),
            'year' => $this->getDataForYear(),
            default => $this->getDataForMonth(),
        };
    }

    protected function getType(): string
    {
        return 'line'; // or 'bar'
    }

    protected function getFilters(): ?array
    {
        return [
            // 'day' => 'Today',
            'week' => 'Last 7 days',
            'month' => 'Last 30 days',
            'last_6_months' => 'Last 6 months',
            'year' => 'This year',
        ];
    }

    // ──────────────────────── DAILY ────────────────────────
    // private function getDataForDay(): array
    // {
    //     $hours = [];
    //     $newCounts = [];
    //     $expiredCounts = [];
    //     $activeCounts = [];

    //     $start = now()->startOfDay();
    //     for ($i = 0; $i < 24; $i++) {
    //         $hourStart = $start->copy()->addHours($i);
    //         $hourEnd = $hourStart->copy()->addHour();

    //         $new = Member::whereBetween('created_at', [$hourStart, $hourEnd])->count();
    //         $expired = Member::whereNotNull('valid_until')
    //             ->whereBetween('valid_until', [$hourStart, $hourEnd])
    //             ->count();

    //         $hours[] = $hourStart->format('H:i');
    //         $newCounts[] = $new;
    //         $expiredCounts[] = $expired;
    //         $activeCounts[] = $active;
    //     }

    //     return $this->buildResponse($hours, $newCounts, $expiredCounts);
    // }

    // ──────────────────────── WEEKLY ────────────────────────
    private function getDataForWeek(): array
    {
        $dates = [];
        $newCounts = [];
        $expiredCounts = [];
        $activeCounts = [];

        // Go backwards 6 days, then today → 7 days total
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->copy()->subDays($i)->startOfDay();
            $nextDay = $date->copy()->addDay();

            $new = Member::whereBetween('created_at', [$date, $nextDay])->count();
            $expired = Member::whereNotNull('valid_until')
                ->whereBetween('valid_until', [$date, $nextDay])
                ->count();
            $active = Member::where('created_at', '<=', $nextDay)
                ->where('valid_until', '>=', $date)
                ->count();

            $dates[] = $date->format('D'); // Mon, Tue...
            $newCounts[] = $new;
            $expiredCounts[] = $expired;
            $activeCounts [] = $active;
        }

        return $this->buildResponse($dates, $newCounts, $activeCounts, $expiredCounts);
    }

    // ──────────────────────── LAST 30 DAYS ────────────────────────
    private function getDataForMonth(): array
    {
        $dates = [];
        $newCounts = [];
        $expiredCounts = [];
        $activeCounts = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->copy()->subDays($i)->startOfDay();
            $nextDay = $date->copy()->addDay();

            $new = Member::whereBetween('created_at', [$date, $nextDay])->count();
            $expired = Member::whereNotNull('valid_until')
                ->whereBetween('valid_until', [$date, $nextDay])
                ->count();
            $active = Member::where('created_at', '<=', $nextDay)
                ->where('valid_until', '>=', $date)
                ->count();

            $dates[] = $date->format('M j'); // e.g., Nov 10
            $newCounts[] = $new;
            $expiredCounts[] = $expired;
            $activeCounts[] = $active;
        }

        return $this->buildResponse($dates, $newCounts, $activeCounts, $expiredCounts);
    }

    // ──────────────────────── LAST 6 MONTHS ────────────────────────
    private function getDataForLast6Months(): array
    {
        $months = [];
        $newCounts = [];
        $expiredCounts = [];
        $activeCounts = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->copy()->subMonths($i);
            $year = $date->year;
            $month = $date->month;

            // Last day of the month at 23:59:59
            $monthEnd = $date->copy()->endOfMonth();

            $new = Member::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

            $expired = Member::whereNotNull('valid_until')
                ->whereYear('valid_until', $year)
                ->whereMonth('valid_until', $month)
                ->count();
            $active = Member::where('created_at', '<=', $monthEnd)
                ->where('valid_until', '>=', $monthEnd)
                ->count();

            $months[] = $date->format('M y'); // e.g., Jun 25
            $newCounts[] = $new;
            $expiredCounts[] = $expired;
            $activeCounts[] = $active;
        }

        return $this->buildResponse($months, $newCounts, $activeCounts, $expiredCounts);
    }

    // ──────────────────────── CURRENT YEAR ────────────────────────
    private function getDataForYear(): array
    {
        $currentYear = now()->year;
        $resultsNew = Member::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $resultsExpired = Member::selectRaw('MONTH(valid_until) as month, COUNT(*) as count')
            ->whereNotNull('valid_until')
            ->whereYear('valid_until', $currentYear)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();
        
        $resultsExpired = Member::selectRaw('MONTH(valid_until) as month, COUNT(*) as count')
            ->whereNotNull('valid_until')
            ->whereYear('created_at','<=', $currentYear)
            ->whereYear('valid_until','>=', $currentYear)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $months = [];
        $newCounts = [];
        $expiredCounts = [];
        $activeCounts = [];

        foreach (range(1, 12) as $monthNum) {

             // Get last day of this month in current year
            $monthEnd = Carbon::create($currentYear, $monthNum, 1)->endOfMonth();

            // Count active members on that day
            $active = Member::where('created_at', '<=', $monthEnd)
                ->where(function ($q) use ($monthEnd) {
                    $q->where('valid_until', '>=', $monthEnd)
                    ->orWhereNull('valid_until');
                })
                ->count();

            $months[] = Carbon::create($currentYear, $monthNum, 1)->format('M');
            $newCounts[] = $resultsNew[$monthNum] ?? 0;
            $expiredCounts[] = $resultsExpired[$monthNum] ?? 0;
            $activeCounts[] = $active;
        }

        return $this->buildResponse($months, $newCounts,$activeCounts, $expiredCounts);
    }

    // ──────────────────────── HELPER ────────────────────────
    private function buildResponse(array $labels, array $newCounts, $activeCounts, array $expiredCounts): array
    {
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'New Members',
                    'data' => $newCounts,
                    'borderColor' => '#10B981', // emerald-500
                    'backgroundColor' => '#10B98120',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                   'label' => 'Active Members',
                   'data' => $activeCounts,
                   'borderColor' => '#abba09ff', // red-500
                   'backgroundColor' => '#78a86120',
                   'fill' => true,
                   'tension' => 0.4,
               ],
                [
                    'label' => 'Expired Members',
                    'data' => $expiredCounts,
                    'borderColor' => '#EF4444', // red-500
                    'backgroundColor' => '#EF444420',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }
}
