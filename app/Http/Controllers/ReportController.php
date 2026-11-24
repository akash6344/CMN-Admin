<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\PostProperty;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Reports & Analytics: listings, property types, cities and revenue for the chosen period — on screen or as CSV. */
class ReportController extends Controller
{
    /** range => [label, number of buckets, bucket unit] */
    public const RANGES = [
        '30d' => ['Last 30 Days', 30, 'day'],
        '6m' => ['Last 6 Months', 6, 'month'],
        '1y' => ['Last 1 Year', 12, 'month'],
    ];

    public function __invoke(Request $request): View
    {
        $range = $this->range($request);

        return view('pages.reports', ['range' => $range, 'ranges' => self::RANGES] + $this->data($range));
    }

    public function export(Request $request): StreamedResponse
    {
        $range = $this->range($request);
        $d = $this->data($range);
        [$label] = self::RANGES[$range];

        return response()->streamDownload(function () use ($d, $label): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CMNHousing Reports & Analytics', $label, 'Generated '.now()->format('j M Y, g:i a')]);
            fputcsv($out, []);
            fputcsv($out, ['Summary']);
            fputcsv($out, ['New listings', $d['totals']['listings']]);
            fputcsv($out, ['Paid transactions', $d['totals']['payments']]);
            fputcsv($out, ['Revenue (₹)', number_format($d['totals']['revenue'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Period', 'New listings', 'Revenue (₹)']);
            foreach ($d['monthlyLabels'] as $i => $period) {
                fputcsv($out, [$period, $d['monthlyValues'][$i], number_format($d['revenueValues'][$i], 2, '.', '')]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Property type', 'Listings', 'Share %']);
            foreach ($d['propertyTypeLegend'] as $row) {
                fputcsv($out, [$row['label'], $row['total'], $row['percent']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['City', 'Listings']);
            foreach ($d['cityLabels'] as $i => $city) {
                fputcsv($out, [$city, $d['cityValues'][$i]]);
            }
            fclose($out);
        }, 'cmnhousing-report-'.str_replace(' ', '-', strtolower($label)).'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function range(Request $request): string
    {
        return array_key_exists((string) $request->query('range'), self::RANGES) ? (string) $request->query('range') : '6m';
    }

    /**
     * @return array<string, mixed>
     */
    private function data(string $range): array
    {
        [, $count, $unit] = self::RANGES[$range];
        $buckets = $this->buckets($count, $unit);
        $start = $buckets->first()['start'];
        $hasProperties = rescue(fn () => Schema::connection('dashboard')->hasTable('post_properties'), false, false);

        $listings = $hasProperties
            ? PostProperty::query()->where('created_at', '>=', $start)->get(['id', 'property_type', 'city', 'created_at'])
            : collect();
        $payments = PaymentTransaction::available()
            ? PaymentTransaction::query()->where('status', 'paid')->where('paid_at', '>=', $start)->get(['id', 'amount', 'paid_at'])
            : collect();

        $types = $listings->groupBy(fn ($l) => trim((string) $l->property_type) ?: 'Uncategorized')->map->count()->sortDesc();
        $cities = $listings->groupBy(fn ($l) => trim((string) $l->city) ?: 'Unknown')->map->count()->sortDesc()->take(8);
        $typeTotal = max(1, $types->sum());

        $inBucket = fn (Collection $rows, string $field) => $buckets->map(fn ($b) => $rows->filter(fn ($r) => $r->{$field} && $r->{$field} >= $b['start'] && $r->{$field} < $b['end']));

        return [
            'propertyTypeLabels' => $types->keys()->values(),
            'propertyTypeValues' => $types->values(),
            'propertyTypeLegend' => $types->map(fn ($n, $label) => ['label' => $label, 'total' => $n, 'percent' => (int) round($n / $typeTotal * 100)])->values(),
            'cityLabels' => $cities->keys()->values(),
            'cityValues' => $cities->values(),
            'monthlyLabels' => $buckets->pluck('label')->values(),
            'monthlyValues' => $inBucket($listings, 'created_at')->map->count()->values(),
            'revenueValues' => $inBucket($payments, 'paid_at')->map(fn ($rows) => round($rows->sum('amount') / 100, 2))->values(),
            'bucketUnit' => $unit,
            'totals' => ['listings' => $listings->count(), 'payments' => $payments->count(), 'revenue' => round($payments->sum('amount') / 100, 2)],
        ];
    }

    /** @return Collection<int, array{label: string, start: CarbonInterface, end: CarbonInterface}> */
    private function buckets(int $count, string $unit): Collection
    {
        return collect(range($count - 1, 0))->map(function (int $back) use ($unit) {
            $start = $unit === 'day' ? now()->subDays($back)->startOfDay() : now()->subMonths($back)->startOfMonth();
            $end = $unit === 'day' ? (clone $start)->addDay() : (clone $start)->addMonth();

            return ['label' => $unit === 'day' ? $start->format('j M') : $start->format('M Y'), 'start' => $start, 'end' => $end];
        });
    }
}
