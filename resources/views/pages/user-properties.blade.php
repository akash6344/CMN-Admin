<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Properties · {{ $user->name }} - CMNHousing Admin Portal</title>
  <meta name="description" content="Properties listed by {{ $user->name }}.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')

    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Properties · {{ $user->name }}</h1>
          <p class="page-subtitle">{{ $user->email }}{{ $user->mobile ? ' · '.$user->mobile : '' }}</p>
        </div>
        <a class="btn-secondary" href="{{ route('users.index', ['users_page' => 1]) }}">Back to Users</a>
      </div>

      @if ($properties->isEmpty())
        <div class="card" style="padding:28px; text-align:center; color:var(--text-muted);">No properties for this user.</div>
      @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;">
          @foreach ($properties as $property)
            <button
              type="button"
              class="card"
              style="padding:0;overflow:hidden;text-align:left;cursor:pointer;border:1px solid #e2e8f0;background:#fff;"
              data-property-id="{{ $property->id }}"
              onclick="openUserPropertyDetail(this.dataset.propertyId)"
            >
              <div style="height:120px;background:#e2e8f0;background-size:cover;background-position:center;{{ $property->primaryImageUrl() ? "background-image:url('".$property->primaryImageUrl()."')" : '' }}"></div>
              <div style="padding:12px;">
                <div style="display:flex;justify-content:space-between;gap:8px;margin-bottom:6px;">
                  <strong style="font-size:14px;">{{ $property->property_title ?: 'Untitled property' }}</strong>
                  <span class="badge {{ $property->statusBadgeClass() }}">{{ $property->displayStatus() }}</span>
                </div>
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;">{{ $property->property_type ?: '—' }}</div>
                <div style="font-size:13px;">{{ collect([$property->city, $property->location, $property->state])->filter()->implode(', ') ?: '—' }}</div>
                <div style="font-weight:700;margin-top:8px;">{{ $property->formattedPrice() }}</div>
              </div>
            </button>
          @endforeach
        </div>
      @endif
    </main>
  </div>

  <div class="modal-backdrop" id="userPropDetailModal">
    <div class="modal-box" style="max-width:720px;">
      <div class="modal-header">
        <h3 class="modal-title">Property Details</h3>
        <button class="modal-close-btn" type="button" onclick="document.getElementById('userPropDetailModal').classList.remove('open')">×</button>
      </div>
      <div class="modal-body" id="userPropDetailBody"></div>
    </div>
  </div>

  <script>
    const propertyShowBase = @json(url('/properties'));

    async function openUserPropertyDetail(id) {
      const response = await fetch(`${propertyShowBase}/${id}`, { headers: { 'Accept': 'application/json' } });
      if (!response.ok) return;
      const data = await response.json();
      const specsHtml = (data.specs || []).map((spec) => `
        <div class="card" style="padding:12px;"><div style="font-size:12px;color:var(--text-muted);">${spec.label}</div><strong>${spec.value}</strong></div>
      `).join('');
      const about = (data.about_property || '').trim();
      document.getElementById('userPropDetailBody').innerHTML = `
        ${data.primary_image ? `<img src="${data.primary_image}" alt="" style="width:100%;max-height:260px;object-fit:cover;border-radius:12px;margin-bottom:14px;">` : ''}
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:start;margin-bottom:12px;">
          <div>
            <div class="table-id">${data.property_id || '#' + data.id}</div>
            <h2 style="margin:4px 0 0;font-size:22px;">${data.property_title || 'Untitled property'}</h2>
          </div>
          <span class="badge ${data.badge_class}">${data.status}</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="card" style="padding:12px;"><div style="font-size:12px;color:var(--text-muted);">Type</div><strong>${data.property_type || '—'}</strong></div>
          <div class="card" style="padding:12px;"><div style="font-size:12px;color:var(--text-muted);">Price</div><strong>${data.price || '—'}</strong></div>
          <div class="card" style="padding:12px;"><div style="font-size:12px;color:var(--text-muted);">City</div><strong>${data.city || '—'}</strong></div>
          <div class="card" style="padding:12px;"><div style="font-size:12px;color:var(--text-muted);">Location</div><strong>${data.location || '—'}</strong></div>
          <div class="card" style="padding:12px;"><div style="font-size:12px;color:var(--text-muted);">State</div><strong>${data.state || '—'}</strong></div>
          <div class="card" style="padding:12px;"><div style="font-size:12px;color:var(--text-muted);">Website</div><strong>${data.show_on_website || '—'}</strong></div>
          ${specsHtml}
        </div>
        ${about ? `<div class="card" style="padding:14px;margin-top:12px;"><div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;">About Property</div><p style="margin:0;white-space:pre-wrap;">${about}</p></div>` : ''}
      `;
      document.getElementById('userPropDetailModal').classList.add('open');
    }

    document.getElementById('userPropDetailModal').addEventListener('click', (e) => {
      if (e.target.id === 'userPropDetailModal') {
        document.getElementById('userPropDetailModal').classList.remove('open');
      }
    });
  </script>
</body>
</html>
