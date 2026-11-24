<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Property Management - CMNHousing Admin Portal</title>
  <meta name="description" content="Review, approve, and manage all property listings in CMNHousing.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .badge-draft{background:#e2e8f0;color:#475569}
    .prop-thumb{width:44px;height:44px;border-radius:8px;object-fit:cover;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#64748b;flex:none}
    .prop-title-cell{display:flex;align-items:center;gap:12px}
    .prop-title-meta{display:flex;flex-direction:column;gap:2px}
    .prop-type-label{font-size:12px;color:var(--text-muted)}
    .filter-bar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;margin-bottom:16px}
    .src-chip{display:inline-block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.02em;padding:1px 7px;border-radius:999px;margin-top:3px}
    .src-chip.owner{background:#ecfdf5;color:#047857}.src-chip.agent{background:#eef2ff;color:#4338ca}.src-chip.builder{background:#fff7ed;color:#c2410c}
    .owner-cell{display:flex;flex-direction:column;align-items:flex-start}
    #propertiesTable .prop-action-group{flex-wrap:nowrap;justify-content:flex-end}
    #propertiesTable .prop-action-group form{margin:0}
    #propertiesTable td:last-child{white-space:nowrap}
    .brm-note{margin:0 0 14px;padding:10px 14px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:13px}
    .stage-note{display:block;font-size:11.5px;color:var(--text-muted);margin-top:4px;max-width:180px;white-space:normal}
    #addPropertyModal .modal-box{max-width:860px}
    #addPropertyModal .modal-body{max-height:70vh;overflow:auto}
  </style>
</head>
<body>
  @php
    $qs = fn (array $over = []) => array_filter(array_merge([
        'status' => $filters['tab'] === 'all' ? null : $filters['tab'], 'source' => $filters['source'] ?: null,
        'property_type' => $filters['type'] ?: null, 'q' => $filters['q'] ?: null, 'brm' => $filters['brm'] ?: null,
    ], $over), fn ($v) => $v !== null && $v !== '');
    $builderTab = $filters['tab'] === 'builder';
  @endphp
  <div class="app-container">
    @include('layouts.navigation')
    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Property Management</h1>
          <p class="page-subtitle">Approve owner and agent listings Operations has reviewed, or reject them back to Operations with a comment. Builder projects are reviewed by BRM — view only here.</p>
        </div>
        {{-- Add Property hidden for now — listings come from owners / agents / builders via Operations.
        <button class="btn-primary" id="openAddPropertyModal" type="button">Add Property</button>
        --}}
      </div>

      @if (session('success'))
        <div class="card" role="status" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-emerald)">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="card" role="alert" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-red)">{{ $errors->first() }}</div>
      @endif

      <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
        @foreach (['all' => 'Total Listings', 'pending' => 'Pending Approval', 'approved' => 'Approved Listings', 'rejected' => 'Rejected Listings'] as $key => $label)
          <div class="stat-card">
            <div class="stat-header"><span class="stat-title">{{ $label }}</span></div>
            <div class="stat-value">{{ $counts[$key] }}</div>
            <div class="stat-trend {{ $comparisons[$key]['trend'] }}"><span>{{ $comparisons[$key]['label'] }}</span></div>
          </div>
        @endforeach
      </div>

      <div class="card" style="padding:20px;">
        <form method="GET" action="{{ route('properties.index') }}" class="filter-bar" id="propertyFilters">
          <div class="filter-tabs">
            @foreach (\App\Support\PropertyIndex::TABS as $key => [$label, $hint])
              <a class="filter-tab {{ $filters['tab'] === $key ? 'active' : '' }}" title="{{ $hint }}" href="{{ route('properties.index', $qs(['status' => $key === 'all' ? null : $key, 'brm' => null, 'property_type' => null, 'source' => $key === 'builder' ? null : ($filters['source'] ?: null)])) }}">
                {{ $label }}@if ($key !== 'all') ({{ $counts[$key] }})@endif
              </a>
            @endforeach
          </div>
          <div style="display:flex;gap:12px;flex:1;max-width:680px;align-items:center;">
            @if ($builderTab)
              <select class="form-control" name="brm" onchange="this.form.submit()" style="max-width:220px;" title="BRM review state">
                <option value="">All states</option>
                @foreach (\App\Support\PropertyIndex::PROJECT_STATES as $key => [$label])
                  <option value="{{ $key }}" @selected($filters['brm'] === $key)>{{ $label }} ({{ $projectStates[$key] ?? 0 }})</option>
                @endforeach
              </select>
            @else
              <select class="form-control" name="source" onchange="this.form.submit()" style="max-width:150px;" title="Posted by">
                @foreach (\App\Support\PropertyIndex::SOURCES as $key => $label)
                  <option value="{{ $key }}" @selected($filters['source'] === $key)>{{ $key === '' ? 'Posted by: All' : $label }}</option>
                @endforeach
              </select>
            @endif
            <select class="form-control" name="property_type" onchange="this.form.submit()" style="max-width:200px;">
              <option value="">All types</option>
              @foreach ($propertyTypes as $type)
                <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>
              @endforeach
            </select>
            <input type="hidden" name="status" value="{{ $filters['tab'] === 'all' ? '' : $filters['tab'] }}">
            <div class="search-container" style="flex:1;">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input type="text" class="search-input" name="q" value="{{ $filters['q'] }}" placeholder="{{ $builderTab ? 'Search project, builder, location, RERA...' : 'Search title, owner, city, type...' }}">
            </div>
          </div>
        </form>
        @if ($builderTab)
          <p class="brm-note">Builder projects are reviewed by the BRM team in the BRM portal (Project Approvals) — publish, request changes, reject or take down happen there. This list is view only.</p>
        @endif

        <div class="table-responsive">
          <table class="custom-table" id="propertiesTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>PROPERTY</th>
                <th>{{ $builderTab ? 'BUILDER' : 'OWNER' }}</th>
                <th>CITY</th>
                <th>PRICE</th>
                <th>STATUS</th>
                <th style="text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($properties as $row)
                @php $rv = $row['review']; @endphp
                <tr data-status="{{ $row['stage'] }}">
                  <td class="table-id">{{ $row['code'] }}</td>
                  <td>
                    <div class="prop-title-cell">
                      @if ($row['cover'])
                        <img class="prop-thumb" src="{{ $row['cover'] }}" alt="">
                      @else
                        <div class="prop-thumb">{{ $row['kind'] === 'project' ? 'PR' : 'P' }}</div>
                      @endif
                      <div class="prop-title-meta">
                        <span class="table-property-title">{{ $row['title'] }}</span>
                        <span class="prop-type-label">{{ $row['type'] ?: '—' }}</span>
                      </div>
                    </div>
                  </td>
                  <td><div class="owner-cell">{{ $row['from'] ?: '—' }}<span class="src-chip {{ strtolower($row['role']) }}">{{ $row['role'] }}</span></div></td>
                  <td>{{ $row['city'] ?: '—' }}</td>
                  <td class="table-price">{{ $row['price'] }}</td>
                  <td>
                    <span class="badge {{ $row['stageBadge'] }}">{{ $row['stageLabel'] }}</span>
                    @if ($rv?->status === 'admin_query')<span class="stage-note" title="{{ $rv->admin_note }}">{{ \Illuminate\Support\Str::limit($rv->admin_note, 50) }}</span>@endif
                    @if ($row['kind'] === 'project' && ($row['brm']['by'] || $row['brm']['at']))
                      <span class="stage-note">{{ $row['stage'] === 'with_brm' ? 'Last decision' : 'By' }} {{ $row['brm']['by'] ?: 'BRM' }}{{ $row['brm']['at'] ? ' · '.$row['brm']['at']->format('j M Y') : '' }}</span>
                    @endif
                  </td>
                  <td style="text-align:right;">
                    <div class="prop-action-group">
                      <a class="prop-btn prop-btn-muted" href="{{ route('properties.show', $row['key']) }}" title="View details">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <span>View</span>
                      </a>
                      @if ($row['kind'] === 'project')
                        <a class="prop-btn prop-btn-muted" href="{{ $row['brm']['url'] }}" target="_blank" rel="noopener" title="Open in the BRM portal (Project Approvals)"><span>Open in BRM ↗</span></a>
                      @elseif ($row['stage'] === 'awaiting' && $rv)
                        <form method="POST" action="{{ route('properties.reviews.approve', $rv) }}" data-confirm="{{ $row['title'] }} goes live on the website and the {{ strtolower($row['role']) }} is notified." data-confirm-title="Approve {{ $row['code'] }}?" data-confirm-label="Approve" data-confirm-danger="false">@csrf @method('PATCH')
                          <button class="prop-btn prop-btn-success" type="submit" title="Approve and publish">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Approve</span>
                          </button>
                        </form>
                        <button class="prop-btn prop-btn-danger-soft" type="button" title="Send back to Operations with a comment"
                          data-reject-url="{{ route('properties.reviews.query', $rv) }}" data-reject-code="{{ $row['code'] }}" onclick="openRejectProperty(this)">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                          <span>Reject</span>
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" style="text-align:center;padding:28px;color:var(--text-muted);">{{ $filters['tab'] === 'pending' ? 'Nothing pending. Listings appear here when Operations sends them for approval.' : ($builderTab ? 'No builder projects found.' : 'No properties found.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @include('partials.pagination', ['paginator' => $properties])
      </div>
    </main>
  </div>

  @include('partials.property-modals')
  @include('partials.confirm-modal')
</body>
</html>
