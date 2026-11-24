<?php

namespace App\Http\Controllers;

use App\Models\Housing\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Leads & Bargains. Leads are read-only from the shared housing DB;
 * bargains (Smart Bargain negotiations) are not built yet, so that half of the page is a labelled preview.
 */
class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'status' => array_key_exists((string) $request->query('status'), Lead::STATUSES) ? (string) $request->query('status') : '',
            'platform' => trim((string) $request->query('platform', '')),
            'q' => trim((string) $request->query('q', '')),
        ];
        $available = Lead::available();

        $leads = null;
        $counts = [];
        $platforms = collect();

        if ($available) {
            $leads = Lead::query()
                ->with('project:id,name')
                ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
                ->when($filters['platform'] !== '', fn ($q) => $q->where('source_platform', $filters['platform']))
                ->when($filters['q'] !== '', function ($q) use ($filters): void {
                    $term = '%'.$filters['q'].'%';
                    $q->where(fn ($w) => $w->where('lead_code', 'like', $term)->orWhere('name', 'like', $term)
                        ->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('city', 'like', $term)
                        ->orWhere('interest_title', 'like', $term)->orWhere('builder_name', 'like', $term));
                })
                ->orderByRaw('COALESCE(last_activity_at, created_at) DESC')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();

            $counts = Lead::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->all();
            $platforms = Lead::query()->select('source_platform')->distinct()->orderBy('source_platform')->pluck('source_platform')->filter()->values();
        }

        return view('pages.leads', [
            'available' => $available,
            'leads' => $leads,
            'counts' => $counts,
            'total' => array_sum($counts),
            'platforms' => $platforms,
            'filters' => $filters,
        ]);
    }
}
