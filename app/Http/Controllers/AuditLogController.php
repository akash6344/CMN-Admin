<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        [$search, $action] = $this->filters($request);

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('pages.audit-logs', [
            'logs' => $this->filteredQuery($search, $action)->paginate(20)->withQueryString(),
            'actions' => $actions,
            'filters' => [
                'q' => $search,
                'action' => $action,
            ],
        ]);
    }

    /** CSV of the logs matching the current filters. */
    public function export(Request $request): StreamedResponse
    {
        [$search, $action] = $this->filters($request);
        $query = $this->filteredQuery($search, $action);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'Admin', 'Admin email', 'Action', 'Description', 'Details', 'IP address']);
            $query->chunk(500, function ($logs) use ($out): void {
                foreach ($logs as $log) {
                    $details = collect($log->meta ?? [])
                        ->filter(fn ($v) => is_scalar($v) || $v === null)
                        ->map(fn ($v, $k) => str_replace('_', ' ', (string) $k).': '.($v === null || $v === '' ? '—' : $v))
                        ->implode('; ');
                    fputcsv($out, [
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->user?->name ?: 'System',
                        $log->user?->email,
                        $log->action,
                        $log->description,
                        $details,
                        $log->ip_address,
                    ]);
                }
            });
            fclose($out);
        }, 'cmnhousing-audit-log-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** @return array{0: string, 1: string} */
    private function filters(Request $request): array
    {
        return [trim((string) $request->query('q', '')), trim((string) $request->query('action', ''))];
    }

    /** @return Builder<AuditLog> */
    private function filteredQuery(string $search, string $action): Builder
    {
        $logsQuery = AuditLog::query()
            ->with('user')
            ->latest('created_at')
            ->latest('id');

        if ($action !== '') {
            $logsQuery->where('action', $action);
        }

        if ($search !== '') {
            $logsQuery->where(function ($query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $logsQuery;
    }
}
