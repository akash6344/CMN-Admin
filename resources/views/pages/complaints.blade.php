<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complaints - CMNHousing Admin Portal</title>
  <meta name="description" content="Support tickets raised across the CMNHousing portals.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .status-badge-text.escalated{color:#b91c1c;background:#fef2f2}
    .priority-pill.critical{background:#7f1d1d;color:#fff}
    .complaint-title a{color:inherit;text-decoration:none}.complaint-title a:hover{color:#0284c7}
    .overdue-chip{font-size:11px;font-weight:700;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:999px;padding:1px 8px}
    .src-chip{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.02em;padding:1px 7px;border-radius:999px;background:#fff7ed;color:#c2410c}
    .complaints-empty{padding:36px;text-align:center;color:var(--text-muted)}
    .complaint-filters{display:flex;gap:10px;align-items:center;flex:1;max-width:520px}
    .reply-hint{font-size:12.5px;color:var(--text-muted);margin:6px 0 0}
  </style>
</head>
<body>
  @php
    $qs = fn (array $over = []) => array_filter(array_merge(['status' => $filters['tab'] === 'all' ? null : $filters['tab'], 'priority' => $filters['priority'] ?: null, 'q' => $filters['q'] ?: null], $over), fn ($v) => $v !== null && $v !== '');
  @endphp
  <div class="app-container">
    @include('layouts.navigation')

    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Complaints</h1>
          <p class="page-subtitle">Support tickets raised across the portals — reply, add notes, escalate or resolve</p>
        </div>
        @if ($openCount)
          <span class="header-badge alert">⚠️ {{ $openCount }} Open{{ $overdueCount ? ' · '.$overdueCount.' overdue' : '' }}</span>
        @endif
      </div>

      @if (session('success'))
        <div class="card" role="status" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-emerald)">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="card" role="alert" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-red)">{{ $errors->first() }}</div>
      @endif

      <form class="filter-bar" method="GET" action="{{ route('complaints.index') }}">
        <div class="filter-tabs">
          @foreach (\App\Http\Controllers\ComplaintController::TABS as $key => $label)
            <a class="filter-tab {{ $filters['tab'] === $key ? 'active' : '' }}" href="{{ route('complaints.index', $qs(['status' => $key === 'all' ? null : $key])) }}">
              {{ $label }}@if ($key !== 'all') ({{ $counts[$key] ?? 0 }})@endif
            </a>
          @endforeach
        </div>
        <div class="complaint-filters">
          <input type="hidden" name="status" value="{{ $filters['tab'] === 'all' ? '' : $filters['tab'] }}">
          <select class="form-control" name="priority" onchange="this.form.submit()" style="max-width:150px">
            <option value="">All priorities</option>
            @foreach (\App\Models\Housing\SupportTicket::PRIORITIES as $key => [$label])
              <option value="{{ $key }}" @selected($filters['priority'] === $key)>{{ $label }}</option>
            @endforeach
          </select>
          <div class="search-container" style="flex:1">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" class="search-input" name="q" value="{{ $filters['q'] }}" placeholder="Search ticket, subject, builder...">
          </div>
        </div>
      </form>

      <div class="complaints-list" id="complaintsList">
        @if (! $available)
          <div class="card complaints-empty">The support tickets database isn’t reachable right now.</div>
        @else
          @forelse ($tickets as $t)
            @php $desc = $t->messages->firstWhere('type', 'description'); @endphp
            <div class="complaint-card" data-status="{{ $t->status }}">
              <div class="complaint-icon-box {{ $t->level() }}">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
              </div>
              <div class="complaint-content">
                <div class="complaint-header">
                  <h3 class="complaint-title"><a href="{{ route('complaints.show', $t) }}">{{ $t->subject }}</a></h3>
                  <span class="priority-pill {{ $t->priority === 'critical' ? 'critical' : $t->level() }}">{{ $t->priorityLabel() }}</span>
                  @if ($t->isOverdue())<span class="overdue-chip">Overdue</span>@endif
                </div>
                <p class="complaint-desc">{{ \Illuminate\Support\Str::limit($desc?->body ?? '—', 180) }}</p>
                <div class="complaint-meta">
                  <span>{{ $t->code }}</span>
                  <span>By: <strong>{{ $t->builder_name ?: '—' }}</strong> <span class="src-chip">{{ $t->raised_by === 'builder' ? 'Builder' : strtoupper($t->raised_by) }}</span></span>
                  <span>Category: <strong>{{ $t->categoryLabel() }}</strong></span>
                  <span>Handled by: <strong>{{ $t->assigned_to ?: 'Unassigned' }}</strong></span>
                </div>
              </div>
              <div class="complaint-side-actions">
                <div class="complaint-status-row">
                  <span class="status-badge-text {{ $t->statusClass() }}">{{ $t->statusLabel() }}</span>
                  <span>• {{ $t->created_at?->diffForHumans() }}</span>
                </div>
                <div class="complaint-btn-group">
                  <button class="btn-ghost" type="button" data-respond="{{ route('complaints.reply', $t) }}" data-title="{{ $t->code }} · {{ $t->subject }}" data-recipient="{{ $t->builder_name }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Respond
                  </button>
                  <button class="btn-dark" type="button" data-action="{{ route('complaints.update', $t) }}" data-title="{{ $t->code }} · {{ $t->subject }}" data-status="{{ $t->status }}" data-priority="{{ $t->priority }}">Take Action</button>
                  <a class="btn-ghost" href="{{ route('complaints.show', $t) }}" style="text-decoration:none">View</a>
                </div>
              </div>
            </div>
          @empty
            <div class="card complaints-empty">
              {{ $filters['tab'] === 'all' && $filters['q'] === '' && $filters['priority'] === '' ? 'No complaints yet. Tickets builders raise in their portal show up here.' : 'No complaints match these filters.' }}
            </div>
          @endforelse
        @endif
      </div>
      @if ($tickets)
        @include('partials.pagination', ['paginator' => $tickets])
      @endif
    </main>
  </div>

  @include('partials.complaint-modals')
</body>
</html>
