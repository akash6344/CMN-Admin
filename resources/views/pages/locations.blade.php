<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Location Management - CMNHousing</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .location-tabs{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
    .location-panel{padding:20px}
    .location-search{max-width:420px;margin-bottom:20px}
    .location-actions{display:flex;gap:8px;align-items:center}
    .location-modal .form-group{margin-bottom:14px}
    .location-empty{text-align:center;padding:28px;color:var(--text-muted)}
  </style>
</head>
<body>
<div class="app-container">
  @include('layouts.navigation')
  <main class="page-content">
    <div class="page-header-actions">
      <div>
        <h1 class="page-title">Location Management</h1>
        <p class="page-subtitle">Manage the states, cities, and localities available across the platform.</p>
      </div>
      <a href="{{ route('settings.index') }}" class="btn-secondary" style="text-decoration:none">Back to Settings</a>
    </div>

    @if(session('success'))
      <div class="card" role="status" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-emerald)">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="card" role="alert" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-red)">{{ $errors->first() }}</div>
    @endif

    <div class="location-tabs" role="tablist">
      <button class="btn-primary" id="statesTab" type="button" role="tab" aria-selected="true">States</button>
      <button class="btn-secondary" id="citiesTab" type="button" role="tab" aria-selected="false">Cities</button>
      <button class="btn-secondary" id="localitiesTab" type="button" role="tab" aria-selected="false">Localities</button>
    </div>

    <section class="card location-panel" id="statesPanel" role="tabpanel">
      <div class="page-header-actions" style="margin-bottom:16px">
        <div>
          <h2 style="margin:0">States</h2>
          <p class="page-subtitle">Create, search, and update platform states.</p>
        </div>
        <button class="btn-primary" type="button" onclick="openLocationModal('state')">Add State</button>
      </div>
      <div class="location-search">
        <div class="search-container" style="width:100%">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input class="search-input" data-table-search="statesTable" placeholder="Search states...">
        </div>
      </div>
      <div class="table-responsive">
        <table class="custom-table" id="statesTable">
          <thead><tr><th>STATE</th><th>CITIES</th><th>STATUS</th><th>ACTIONS</th></tr></thead>
          <tbody>
            @forelse($states as $state)
              <tr>
                <td><strong>{{ $state->state }}</strong></td>
                <td>{{ $state->cities_count }}</td>
                <td><span class="badge {{ $state->status === 'Active' ? 'badge-approved' : 'badge-rejected' }}">{{ $state->status }}</span></td>
                <td class="location-actions">
                  <button class="btn-secondary" type="button" data-location='@json($state, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)' onclick="editLocation('state', this)">Edit</button>
                  <form method="POST" action="{{ route('locations.states.destroy', $state) }}" data-confirm="Delete this state and its cities/localities?" data-confirm-title="Delete state" data-confirm-label="Delete">@csrf @method('DELETE')
                    <button class="btn-ghost" style="color:var(--accent-red)" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="location-empty">No states added yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @include('partials.pagination', ['paginator' => $states])
    </section>

    <section class="card location-panel" id="citiesPanel" role="tabpanel" style="display:none">
      <div class="page-header-actions" style="margin-bottom:16px">
        <div>
          <h2 style="margin:0">Cities</h2>
          <p class="page-subtitle">Assign each city to a state and control its availability.</p>
        </div>
        <button class="btn-primary" type="button" onclick="openLocationModal('city')">Add City</button>
      </div>
      <div class="location-search">
        <div class="search-container" style="width:100%">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input class="search-input" data-table-search="citiesTable" placeholder="Search cities or states...">
        </div>
      </div>
      <div class="table-responsive">
        <table class="custom-table" id="citiesTable">
          <thead><tr><th>CITY</th><th>STATE</th><th>LOCALITIES</th><th>STATUS</th><th>ACTIONS</th></tr></thead>
          <tbody>
            @forelse($cities as $city)
              <tr>
                <td><strong>{{ $city->city }}</strong></td>
                <td>{{ $city->state->state }}</td>
                <td>{{ $city->localities_count }}</td>
                <td><span class="badge {{ $city->status === 'Active' ? 'badge-approved' : 'badge-rejected' }}">{{ $city->status }}</span></td>
                <td class="location-actions">
                  <button class="btn-secondary" type="button" data-location='@json($city, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)' onclick="editLocation('city', this)">Edit</button>
                  <form method="POST" action="{{ route('locations.cities.destroy', $city) }}" data-confirm="Delete this city and its localities?" data-confirm-title="Delete city" data-confirm-label="Delete">@csrf @method('DELETE')
                    <button class="btn-ghost" style="color:var(--accent-red)" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="location-empty">No cities added yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @include('partials.pagination', ['paginator' => $cities])
    </section>

    <section class="card location-panel" id="localitiesPanel" role="tabpanel" style="display:none">
      <div class="page-header-actions" style="margin-bottom:16px">
        <div>
          <h2 style="margin:0">Localities</h2>
          <p class="page-subtitle">Assign each locality to a city under a state.</p>
        </div>
        <button class="btn-primary" type="button" onclick="openLocationModal('locality')">Add Locality</button>
      </div>
      <div class="location-search">
        <div class="search-container" style="width:100%">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input class="search-input" data-table-search="localitiesTable" placeholder="Search localities, cities, or states...">
        </div>
      </div>
      <div class="table-responsive">
        <table class="custom-table" id="localitiesTable">
          <thead><tr><th>LOCALITY</th><th>CITY</th><th>STATE</th><th>STATUS</th><th>ACTIONS</th></tr></thead>
          <tbody>
            @forelse($localities as $locality)
              <tr>
                <td><strong>{{ $locality->locality }}</strong></td>
                <td>{{ $locality->city?->city }}</td>
                <td>{{ $locality->state?->state }}</td>
                <td><span class="badge {{ $locality->status === 'Active' ? 'badge-approved' : 'badge-rejected' }}">{{ $locality->status }}</span></td>
                <td class="location-actions">
                  <button class="btn-secondary" type="button" data-location='@json($locality, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)' onclick="editLocation('locality', this)">Edit</button>
                  <form method="POST" action="{{ route('locations.localities.destroy', $locality) }}" data-confirm="Delete this locality?" data-confirm-title="Delete locality" data-confirm-label="Delete">@csrf @method('DELETE')
                    <button class="btn-ghost" style="color:var(--accent-red)" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="location-empty">No localities added yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @include('partials.pagination', ['paginator' => $localities])
    </section>
  </main>
</div>

<div class="modal-backdrop" id="locationModal">
  <div class="modal-box location-modal">
    <div class="modal-header">
      <h3 class="modal-title" id="locationTitle">Add State</h3>
      <button class="modal-close-btn" type="button" onclick="closeLocationModal()">×</button>
    </div>
    <form method="POST" id="locationForm">
      @csrf
      <div class="modal-body">
        <div class="form-group" id="stateSelectGroup" style="display:none">
          <label class="form-label">State</label>
          <select class="form-control" name="state_id" id="stateSelect">
            @foreach($allStates as $state)
              <option value="{{ $state->id }}">{{ $state->state }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" id="citySelectGroup" style="display:none">
          <label class="form-label">City</label>
          <select class="form-control" name="city_id" id="citySelect"></select>
        </div>
        <div class="form-group">
          <label class="form-label" id="locationNameLabel">State name</label>
          <input class="form-control" id="locationName" required maxlength="255">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            <option>Active</option>
            <option>Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="closeLocationModal()">Cancel</button>
        <button class="btn-primary" id="locationSubmit">Add State</button>
      </div>
    </form>
  </div>
</div>

<script>
  const citiesByState = @json($allCities->groupBy('state_id')->map->values());
  const panels = {
    state: document.getElementById('statesPanel'),
    city: document.getElementById('citiesPanel'),
    locality: document.getElementById('localitiesPanel'),
  };
  const tabs = {
    state: document.getElementById('statesTab'),
    city: document.getElementById('citiesTab'),
    locality: document.getElementById('localitiesTab'),
  };
  const locationModal = document.getElementById('locationModal');
  const locationForm = document.getElementById('locationForm');
  const nameInput = document.getElementById('locationName');
  const stateSelect = document.getElementById('stateSelect');
  const citySelect = document.getElementById('citySelect');

  function showLocations(type) {
    Object.entries(panels).forEach(([key, panel]) => {
      panel.style.display = key === type ? '' : 'none';
      tabs[key].className = key === type ? 'btn-primary' : 'btn-secondary';
      tabs[key].setAttribute('aria-selected', key === type ? 'true' : 'false');
    });
  }

  tabs.state.onclick = () => showLocations('state');
  tabs.city.onclick = () => showLocations('city');
  tabs.locality.onclick = () => showLocations('locality');

  @if (request()->has('cities_page'))
    showLocations('city');
  @elseif (request()->has('localities_page'))
    showLocations('locality');
  @elseif (request()->has('states_page'))
    showLocations('state');
  @endif

  document.querySelectorAll('[data-table-search]').forEach((input) => {
    input.oninput = () => {
      document.querySelectorAll(`#${input.dataset.tableSearch} tbody tr`).forEach((row) => {
        row.style.display = row.textContent.toLowerCase().includes(input.value.toLowerCase()) ? '' : 'none';
      });
    };
  });

  function populateCities(stateId, selectedCityId = null) {
    const cities = citiesByState[String(stateId)] || [];
    citySelect.innerHTML = cities.map((city) => `<option value="${city.id}">${city.city}</option>`).join('');
    if (selectedCityId) {
      citySelect.value = String(selectedCityId);
    }
  }

  stateSelect.addEventListener('change', () => {
    if (document.getElementById('citySelectGroup').style.display !== 'none') {
      populateCities(stateSelect.value);
    }
  });

  function openLocationModal(type, record = null) {
    locationForm.reset();
    locationForm.querySelector('[name=_method]')?.remove();

    const isCity = type === 'city';
    const isLocality = type === 'locality';
    const label = isLocality ? 'Locality' : (isCity ? 'City' : 'State');

    document.getElementById('stateSelectGroup').style.display = (isCity || isLocality) ? '' : 'none';
    document.getElementById('citySelectGroup').style.display = isLocality ? '' : 'none';
    nameInput.name = isLocality ? 'locality' : (isCity ? 'city' : 'state');
    document.getElementById('locationNameLabel').textContent = `${label} name`;
    document.getElementById('locationTitle').textContent = record ? `Edit ${label}` : `Add ${label}`;
    document.getElementById('locationSubmit').textContent = record ? 'Save changes' : `Add ${label}`;

    const resource = isLocality ? 'localities' : (isCity ? 'cities' : 'states');
    locationForm.action = record
      ? `{{ url('locations') }}/${resource}/${record.id}`
      : `{{ url('locations') }}/${resource}`;

    if (record) {
      locationForm.insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">');
      nameInput.value = record[isLocality ? 'locality' : (isCity ? 'city' : 'state')] || '';
      locationForm.elements.status.value = record.status || 'Active';
      if (isCity || isLocality) {
        stateSelect.value = String(record.state_id);
      }
      if (isLocality) {
        populateCities(record.state_id, record.city_id);
      }
    } else if (isLocality && stateSelect.value) {
      populateCities(stateSelect.value);
    }

    locationModal.classList.add('open');
  }

  function editLocation(type, button) {
    openLocationModal(type, JSON.parse(button.dataset.location));
  }

  function closeLocationModal() {
    locationModal.classList.remove('open');
  }

  locationModal.onclick = (e) => {
    if (e.target === locationModal) closeLocationModal();
  };
</script>
@include('partials.confirm-modal')
</body>
</html>
