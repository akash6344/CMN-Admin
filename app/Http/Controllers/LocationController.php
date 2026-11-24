<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Locality;
use App\Models\State;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('pages.locations', [
            'states' => State::query()->withCount('cities')->orderBy('state')->paginate(10, ['*'], 'states_page')->withQueryString(),
            'cities' => City::query()->with('state')->withCount('localities')->orderBy('city')->paginate(10, ['*'], 'cities_page')->withQueryString(),
            'localities' => Locality::query()->with(['state', 'city'])->orderBy('locality')->paginate(10, ['*'], 'localities_page')->withQueryString(),
            'allStates' => State::query()->orderBy('state')->get(['id', 'state']),
            'allCities' => City::query()->orderBy('city')->get(['id', 'state_id', 'city']),
        ]);
    }

    public function storeState(Request $request): RedirectResponse
    {
        State::create($request->validate([
            'state' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]));

        return back()->with('success', 'State added.');
    }

    public function updateState(Request $request, State $state): RedirectResponse
    {
        $state->update($request->validate([
            'state' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]));

        return back()->with('success', 'State updated.');
    }

    public function destroyState(State $state): RedirectResponse
    {
        $state->delete();

        return back()->with('success', 'State and its cities and localities deleted.');
    }

    public function storeCity(Request $request): RedirectResponse
    {
        City::create($request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'city' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]));

        return back()->with('success', 'City added.');
    }

    public function updateCity(Request $request, City $city): RedirectResponse
    {
        $validated = $request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'city' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        DB::transaction(function () use ($city, $validated): void {
            $city->update($validated);
            $city->localities()->update(['state_id' => $validated['state_id']]);
        });

        return back()->with('success', 'City updated.');
    }

    public function destroyCity(City $city): RedirectResponse
    {
        $city->delete();

        return back()->with('success', 'City and its localities deleted.');
    }

    public function storeLocality(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where(fn (Builder $query) => $query->where('state_id', $request->input('state_id')))],
            'locality' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        Locality::create($validated);

        return back()->with('success', 'Locality added.');
    }

    public function updateLocality(Request $request, Locality $locality): RedirectResponse
    {
        $validated = $request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where(fn (Builder $query) => $query->where('state_id', $request->input('state_id')))],
            'locality' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $locality->update($validated);

        return back()->with('success', 'Locality updated.');
    }

    public function destroyLocality(Locality $locality): RedirectResponse
    {
        $locality->delete();

        return back()->with('success', 'Locality deleted.');
    }
}
