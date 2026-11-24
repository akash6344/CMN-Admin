<?php

namespace App\Http\Controllers;

use App\Models\PostProperty;
use App\Support\PropertyIndex;
use App\Support\WebsiteViews;
use App\Support\WeeklyComparison;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $hasPropertiesTable = rescue(fn () => Schema::connection('dashboard')->hasTable('post_properties'), false, false);

        $none = ['total' => 0, 'label' => 'No change vs last week', 'trend' => 'positive'];
        $weeklyMetrics = ['with_ops' => $none, 'awaiting' => $none, 'approved' => $none, 'sent_back' => $none, 'active' => $none, 'views' => $none];

        if ($hasPropertiesTable) {
            // Same stages as the Properties tabs — owner / agent listings only (builder projects are reviewed by BRM).
            $tabs = PropertyIndex::comparisons(['source' => '', 'type' => '', 'q' => '']);
            $weeklyMetrics['with_ops'] = $tabs['with_ops'];
            $weeklyMetrics['awaiting'] = $tabs['pending'];
            $weeklyMetrics['approved'] = $tabs['approved'];
            $weeklyMetrics['sent_back'] = $tabs['rejected'];
            $weeklyMetrics['active'] = WeeklyComparison::forConstrained(
                PostProperty::query(),
                fn ($query) => $query
                    ->whereRaw('LOWER(TRIM(post_status)) = ?', ['approved'])
                    ->whereRaw('LOWER(TRIM(show_on_website)) = ?', ['show'])
            );

            $current = WeeklyComparison::currentWeekBounds();
            $previous = WeeklyComparison::previousWeekBounds();
            // Real page views logged by the website (property_views), by when they were viewed.
            $weeklyMetrics['views'] = WeeklyComparison::compare(
                total: WebsiteViews::count(),
                thisWeek: WebsiteViews::count(null, $current['start'], $current['end']),
                lastWeek: WebsiteViews::count(null, $previous['start'], $previous['end']),
            );
        }

        $propertyTypeCounts = $hasPropertiesTable
            ? PostProperty::query()
                ->selectRaw("COALESCE(NULLIF(TRIM(property_type), ''), 'Uncategorized') AS property_type, COUNT(*) AS total")
                ->groupBy('property_type')
                ->orderByDesc('total')
                ->get()
            : collect();

        // In the pipeline: listings waiting for Admin first, then still with Operations
        $pendingProperties = $hasPropertiesTable
            ? PropertyIndex::rows(['tab' => 'pending', 'source' => '', 'type' => '', 'q' => ''])
                ->concat(PropertyIndex::rows(['tab' => 'with_ops', 'source' => '', 'type' => '', 'q' => '']))
                ->take(8)
                ->map(fn (array $row) => PropertyIndex::find($row[0].'-'.$row[1]))
                ->filter()
                ->values()
            : collect();

        return view('pages.dashboard', [
            'propertyTypeLabels' => $propertyTypeCounts->pluck('property_type')->values(),
            'propertyTypeValues' => $propertyTypeCounts->pluck('total')->values(),
            'pendingProperties' => $pendingProperties,
            'hasPropertiesTable' => $hasPropertiesTable,
            'weeklyMetrics' => $weeklyMetrics,
            'withBrm' => PropertyIndex::withBrmCount(),
        ]);
    }
}
