<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Listing page views recorded by the public website (CMN-Dashboard `property_views`).
 *
 * The live table (cmn_laravel_13) has `property_id`, `seller_id`, `ip_address`, `viewed_at`;
 * older/local copies use `post_property_id` and no `ip_address`. Columns are detected at
 * runtime, and every method returns empty results when the table is missing.
 */
class WebsiteViews
{
    private const CONNECTION = 'dashboard';

    private const TABLE = 'property_views';

    /** @return array{property: string, ip: bool, at: string}|null */
    public static function columns(): ?array
    {
        try {
            $schema = Schema::connection(self::CONNECTION);
            if (! $schema->hasTable(self::TABLE)) {
                return null;
            }
            $cols = array_map('strtolower', $schema->getColumnListing(self::TABLE));
        } catch (\Throwable) {
            return null;
        }

        $property = in_array('property_id', $cols, true) ? 'property_id' : (in_array('post_property_id', $cols, true) ? 'post_property_id' : null);
        if ($property === null) {
            return null;
        }
        $at = match (true) {
            in_array('viewed_at', $cols, true) && in_array('created_at', $cols, true) => 'COALESCE(viewed_at, created_at)',
            in_array('viewed_at', $cols, true) => 'viewed_at',
            default => 'created_at',
        };

        return ['property' => $property, 'ip' => in_array('ip_address', $cols, true), 'at' => $at];
    }

    public static function available(): bool
    {
        return self::columns() !== null;
    }

    /** Whether distinct viewers can be counted (needs `ip_address`). */
    public static function tracksViewers(): bool
    {
        return (bool) (self::columns()['ip'] ?? false);
    }

    /** Views of the given listings (null = all listings), optionally within [from, to]. */
    public static function count(?array $propertyIds = null, ?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        return rescue(fn () => ($q = self::query($propertyIds, $from, $to)) ? (int) $q->count() : 0, 0, false);
    }

    /** Distinct viewers (by IP) of the given listings; null when the table cannot tell. */
    public static function uniqueViewers(?array $propertyIds = null, ?CarbonInterface $from = null, ?CarbonInterface $to = null): ?int
    {
        if (! self::tracksViewers()) {
            return null;
        }

        return rescue(fn () => (int) self::query($propertyIds, $from, $to)?->whereNotNull('ip_address')->distinct()->count('ip_address'), null, false);
    }

    /** @return array<int, int> listing id => views */
    public static function perProperty(array $propertyIds, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $cols = self::columns();
        if ($cols === null || $propertyIds === []) {
            return [];
        }

        return rescue(fn () => self::query($propertyIds, $from, $to)
            ->selectRaw($cols['property'].' as pid, COUNT(*) as n')
            ->groupBy($cols['property'])
            ->pluck('n', 'pid')
            ->mapWithKeys(fn ($n, $pid) => [(int) $pid => (int) $n])
            ->all(), [], false);
    }

    /** @return array<string, int> Y-m-d => views */
    public static function daily(?array $propertyIds, CarbonInterface $from, CarbonInterface $to): array
    {
        $cols = self::columns();
        if ($cols === null) {
            return [];
        }

        return rescue(fn () => self::query($propertyIds, $from, $to)
            ->selectRaw('DATE('.$cols['at'].') as d, COUNT(*) as n')
            ->groupByRaw('DATE('.$cols['at'].')')
            ->pluck('n', 'd')
            ->mapWithKeys(fn ($n, $d) => [substr((string) $d, 0, 10) => (int) $n])
            ->all(), [], false);
    }

    private static function query(?array $propertyIds, ?CarbonInterface $from, ?CarbonInterface $to): ?Builder
    {
        $cols = self::columns();
        if ($cols === null) {
            return null;
        }

        $q = DB::connection(self::CONNECTION)->table(self::TABLE);
        if ($propertyIds !== null) {
            // Bound as strings so they match integer and text-typed id columns alike.
            $q->whereIn($cols['property'], array_map(fn ($id) => (string) (int) $id, array_values($propertyIds)));
        }
        if ($from) {
            $q->whereRaw($cols['at'].' >= ?', [$from->format('Y-m-d H:i:s')]);
        }
        if ($to) {
            $q->whereRaw($cols['at'].' <= ?', [$to->format('Y-m-d H:i:s')]);
        }

        return $q;
    }
}
