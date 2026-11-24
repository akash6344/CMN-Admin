<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PostProperty;
use App\Support\PropertyIndex;
use App\Support\WebsiteSchema;
use App\Support\WebsiteViews;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Properties: every owner / agent listing with Operations' review on each one — Admin approves (publishes) or raises a query
 * to Operations here. Builder projects are listed in their own tab, view only: BRM reviews them in the BRM portal.
 */
class PropertyController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('item')) {   // older links
            return redirect()->route('properties.show', (string) $request->query('item'));
        }
        if ($request->query('source') === 'builder') {   // older links: builder projects have their own tab now
            return redirect()->route('properties.index', array_filter(['status' => 'builder', 'q' => $request->query('q')]));
        }
        $filters = [
            'tab' => array_key_exists((string) $request->query('status'), PropertyIndex::TABS) ? (string) $request->query('status') : 'all',
            'source' => array_key_exists((string) $request->query('source'), PropertyIndex::SOURCES) ? (string) $request->query('source') : '',
            'type' => trim((string) $request->query('property_type', '')),
            'q' => trim((string) $request->query('q', '')),
            'brm' => array_key_exists((string) $request->query('brm'), PropertyIndex::PROJECT_STATES) ? (string) $request->query('brm') : '',
        ];
        $builder = $filters['tab'] === 'builder';
        if ($builder) {
            $filters['source'] = '';
        }
        $comparisons = PropertyIndex::comparisons(['source' => $filters['source'], 'type' => '', 'q' => '']);

        return view('pages.properties', [
            'properties' => PropertyIndex::paginate($filters),
            'comparisons' => $comparisons,
            'counts' => array_map(fn (array $c) => $c['total'], $comparisons),
            'filters' => $filters,
            'propertyTypes' => PropertyIndex::types(projects: $builder),
            'projectStates' => $builder ? PropertyIndex::projectStateCounts() : [],
        ]);
    }

    /** The full property view: details, Operations' review, comments, and Admin's decision. */
    public function show(string $item): View
    {
        $selected = PropertyIndex::find($item);
        abort_unless($selected, 404);

        $tab = str_starts_with($item, 'project-') ? 'builder' : 'all';
        $ids = PropertyIndex::rows(['tab' => $tab, 'source' => '', 'type' => '', 'q' => ''])->map(fn ($r) => $r[0].'-'.$r[1])->values();
        $at = $ids->search($item);

        return view('pages.property-show', [
            'it' => $selected,
            'prev' => $at !== false && $at > 0 ? $ids[$at - 1] : null,
            'next' => $at !== false ? $ids[$at + 1] ?? null : null,
            // Real website page views of this listing (null when the website's property_views table is absent).
            'websiteViews' => $selected['kind'] === 'listing' && WebsiteViews::available() ? [
                'total' => WebsiteViews::count([(int) $selected['model']->id]),
                'last_30' => WebsiteViews::count([(int) $selected['model']->id], now()->subDays(30)),
            ] : null,
        ]);
    }

    /** Admin adds a listing on behalf of an owner — it goes to Operations for review like any other. */
    public function store(Request $request): RedirectResponse
    {
        $specRules = collect(array_keys(PostProperty::SPEC_LABELS))
            ->mapWithKeys(fn (string $key): array => [$key => ['nullable', 'string', 'max:255']])
            ->all();

        $validated = $request->validate([
            'property_title' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', 'max:255'],
            'user_name' => ['required', 'string', 'max:255'],
            'user_mobile' => ['nullable', 'string', 'max:20'],
            'user_email' => ['nullable', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_text' => ['required', Rule::in(['Lac', 'Cr', 'K'])],
            'about_property' => ['nullable', 'string'],
            ...$specRules,
            'property_for' => ['required', Rule::in(['Sale', 'Rent'])],
        ]);

        $property = PostProperty::query()->create([
            ...$validated,
            'property_id' => 'ADM-'.strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'posted_as' => 'Owner',
            'user_role' => 'owner',
            'post_status' => 'pending',
            'show_on_website' => 'no',
            'count' => 0,
            // live website: a finished form (drafts are never reviewed)
            ...(WebsiteSchema::has('post_properties', 'form_status') ? ['form_status' => 'Published'] : []),
        ]);

        AuditLog::record('property.created', "Property {$property->code()} created by Admin and sent to Operations for review.", null,
            ['post_property_id' => $property->id]);

        return redirect()
            ->route('properties.show', 'listing-'.$property->id)
            ->with('success', "Property {$property->code()} created. Operations will review it before it comes to you for approval.");
    }
}
