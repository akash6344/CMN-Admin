<?php

namespace App\Http\Controllers;

use App\Models\PaymentsPlan;
use App\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plan payments made on the CMNHousing website (Razorpay), read from the
 * live `payment_transactions` table. Read-only: the website owns the writes.
 */
class PaymentTransactionsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $available = PaymentTransaction::available();

        $all = $available ? PaymentTransaction::query()->orderByDesc('created_at')->get() : collect();
        $filtered = $this->apply($all, $filters);

        $paid = $all->where('status', 'paid');
        $monthStart = now()->startOfMonth();
        $prevStart = now()->subMonthNoOverflow()->startOfMonth();
        $paidThis = $paid->filter(fn ($t) => $t->paid_at && $t->paid_at->gte($monthStart));
        $paidPrev = $paid->filter(fn ($t) => $t->paid_at && $t->paid_at->between($prevStart, $monthStart));

        $stats = [
            'collected' => $paid->sum('amount') / 100,
            'net' => $paid->sum('base_amount') / 100,
            'gst' => $paid->sum('tax_amount') / 100,
            'paid_count' => $paid->count(),
            'pending_count' => $all->where('status', 'pending')->count(),
            'failed_count' => $all->where('status', 'failed')->count(),
            'this_month' => $paidThis->sum('amount') / 100,
            'prev_month' => $paidPrev->sum('amount') / 100,
            'subscribers' => $paid->pluck('user_id')->unique()->count(),
            'avg_order' => $paid->count() ? $paid->avg('amount') / 100 : 0,
        ];

        // Revenue by plan (paid only)
        $byPlan = $paid->groupBy(fn ($t) => $t->plan_name.' · '.ucfirst($t->billing_period))
            ->map(fn (Collection $g, $label) => ['label' => $label, 'count' => $g->count(), 'amount' => $g->sum('amount') / 100])
            ->sortByDesc('amount')->values()->all();

        // Revenue by audience (paid only)
        $byAudience = $paid->groupBy(fn ($t) => $t->audience() ?? 'unknown')
            ->map(fn (Collection $g, $key) => ['label' => PaymentsPlan::AUDIENCES[$key] ?? 'Unmatched plan', 'amount' => $g->sum('amount') / 100])
            ->sortByDesc('amount')->values()->all();

        // Monthly collected, last 6 months
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonthsNoOverflow($i)->startOfMonth();
            $trend[] = [
                'label' => $m->format('M'),
                'amount' => $paid->filter(fn ($t) => $t->paid_at && $t->paid_at->between($m, $m->copy()->endOfMonth()))->sum('amount') / 100,
            ];
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;
        $rows = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(), $filtered->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pages.payment-transactions', [
            'available' => $available,
            'filters' => $filters,
            'stats' => $stats,
            'byPlan' => $byPlan,
            'byAudience' => $byAudience,
            'trend' => $trend,
            'rows' => $rows,
            'planKeys' => $all->pluck('plan_name', 'plan_key')->unique()->sort()->all(),
            'statuses' => PaymentTransaction::STATUSES,
            'audiences' => PaymentsPlan::AUDIENCES,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = PaymentTransaction::available()
            ? $this->apply(PaymentTransaction::query()->orderByDesc('created_at')->get(), $this->filters($request))
            : collect();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Created', 'Paid At', 'User ID', 'Payer', 'Plan', 'Billing', 'Audience', 'Base (INR)', 'GST (INR)', 'Total (INR)',
                'Status', 'Gateway', 'Order ID', 'Payment ID', 'Invoice No', 'Failure Reason']);
            foreach ($rows as $t) {
                fputcsv($out, [$t->id, $t->created_at, $t->paid_at?->format('Y-m-d H:i:s'), $t->user_id, $t->payer()['name'], $t->plan_name, $t->billing_period,
                    PaymentsPlan::AUDIENCES[$t->audience()] ?? '', number_format($t->rupees('base_amount'), 2, '.', ''),
                    number_format($t->rupees('tax_amount'), 2, '.', ''), number_format($t->rupees(), 2, '.', ''),
                    $t->status, $t->gateway, $t->gateway_order_id, $t->gateway_payment_id, $t->invoice_number, $t->failure_reason]);
            }
            fclose($out);
        }, 'payment-transactions-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => in_array($request->query('status'), PaymentTransaction::STATUSES, true) ? $request->query('status') : '',
            'plan' => (string) $request->query('plan', ''),
            'billing' => in_array($request->query('billing'), ['monthly', 'yearly'], true) ? $request->query('billing') : '',
            'audience' => array_key_exists((string) $request->query('audience'), PaymentsPlan::AUDIENCES) ? $request->query('audience') : '',
            'from' => $this->date($request->query('from')),
            'to' => $this->date($request->query('to')),
        ];
    }

    private function apply(Collection $rows, array $f): Collection
    {
        return $rows->filter(function (PaymentTransaction $t) use ($f) {
            if ($f['status'] && $t->status !== $f['status']) {
                return false;
            }
            if ($f['plan'] && $t->plan_key !== $f['plan']) {
                return false;
            }
            if ($f['billing'] && $t->billing_period !== $f['billing']) {
                return false;
            }
            if ($f['audience'] && $t->audience() !== $f['audience']) {
                return false;
            }
            $created = Carbon::parse($t->created_at);
            if ($f['from'] && $created->lt(Carbon::parse($f['from'])->startOfDay())) {
                return false;
            }
            if ($f['to'] && $created->gt(Carbon::parse($f['to'])->endOfDay())) {
                return false;
            }
            if ($f['search'] !== '') {
                $hay = strtolower(implode(' ', [$t->id, $t->user_id, $t->plan_name, $t->gateway_order_id, $t->gateway_payment_id, $t->invoice_number, $t->payer()['name'], $t->payer()['email']]));

                return str_contains($hay, strtolower($f['search']));
            }

            return true;
        })->values();
    }

    private function date(mixed $v): string
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
    }
}
