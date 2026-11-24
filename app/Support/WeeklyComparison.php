<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class WeeklyComparison
{
    /**
     * @return array{total: int, this_week: int, last_week: int, change: int|null, label: string, trend: string}
     */
    public static function forQuery(Builder $baseQuery, ?string $createdAtColumn = 'created_at'): array
    {
        $currentWeekStart = now()->startOfWeek();
        $previousWeekStart = (clone $currentWeekStart)->subWeek();
        $previousWeekEnd = (clone $currentWeekStart)->subSecond();
        $currentWeekEnd = now();

        return self::compare(
            total: (clone $baseQuery)->count(),
            thisWeek: (clone $baseQuery)->whereBetween($createdAtColumn, [$currentWeekStart, $currentWeekEnd])->count(),
            lastWeek: (clone $baseQuery)->whereBetween($createdAtColumn, [$previousWeekStart, $previousWeekEnd])->count(),
        );
    }

    /**
     * @param  Closure(Builder): Builder  $constraints
     * @return array{total: int, this_week: int, last_week: int, change: int|null, label: string, trend: string}
     */
    public static function forConstrained(Builder $baseQuery, Closure $constraints, ?string $createdAtColumn = 'created_at'): array
    {
        return self::forQuery($constraints(clone $baseQuery), $createdAtColumn);
    }

    /**
     * @return array{total: int, this_week: int, last_week: int, change: int|null, label: string, trend: string}
     */
    public static function compare(int $total, int $thisWeek, int $lastWeek): array
    {
        $change = $lastWeek === 0 ? null : (int) round((($thisWeek - $lastWeek) / $lastWeek) * 100);

        if ($change === null) {
            $label = $thisWeek > 0 ? 'New this week' : 'No change vs last week';
            $trend = $thisWeek > 0 ? 'positive' : 'positive';
        } else {
            $label = ($change >= 0 ? '↑ ' : '↓ ').abs($change).'% from last week';
            $trend = $change < 0 ? 'negative' : 'positive';
        }

        return [
            'total' => $total,
            'this_week' => $thisWeek,
            'last_week' => $lastWeek,
            'change' => $change,
            'label' => $label,
            'trend' => $trend,
        ];
    }

    /**
     * @return array{start: CarbonInterface, end: CarbonInterface}
     */
    public static function currentWeekBounds(): array
    {
        return [
            'start' => now()->startOfWeek(),
            'end' => now(),
        ];
    }

    /**
     * @return array{start: CarbonInterface, end: CarbonInterface}
     */
    public static function previousWeekBounds(): array
    {
        $currentWeekStart = now()->startOfWeek();

        return [
            'start' => (clone $currentWeekStart)->subWeek(),
            'end' => (clone $currentWeekStart)->subSecond(),
        ];
    }
}
