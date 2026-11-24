<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leads & Bargains - CMNHousing Admin Portal</title>
  <meta name="description" content="Buyer leads across CMNHousing, and a preview of Smart Bargain negotiations.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .leads-filters{display:flex;gap:10px;align-items:center;flex:1;max-width:520px}
    .lead-who{font-weight:700;color:var(--text-primary)}
    .lead-sub{font-size:12px;color:var(--text-muted)}
    .lead-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;color:var(--text-muted)}
    .leads-empty{padding:28px;text-align:center;color:var(--text-muted)}
    .preview-tag{display:inline-block;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;background:#eef2ff;color:#4338ca;border-radius:999px;padding:2px 10px;margin-left:8px;vertical-align:middle}
    .bargain-preview{margin-top:28px;padding:22px 24px;border:1px dashed #cbd5e1;background:#f8fafc}
    .bargain-preview ol{margin:12px 0 0 18px;padding:0;color:var(--text-secondary);font-size:13.5px;line-height:1.7}
    .bargain-preview .preview-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;align-items:center}
    .bargain-preview button[disabled]{opacity:.5;cursor:not-allowed}
    .section-title{font-size:16px;font-weight:700;color:var(--text-primary);margin:0 0 4px}
  </style>
</head>
<body>
  @php
    $qs = fn (array $over = []) => array_filter(array_merge(['status' => $filters['status'] ?: null, 'platform' => $filters['platform'] ?: null, 'q' => $filters['q'] ?: null], $over), fn ($v) => $v !== null && $v !== '');
  @endphp
  <div class="app-container">
    @include('layouts.navigation')

    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Leads &amp; Bargains</h1>
          <p class="page-subtitle">Buyer leads from the website, agent and builder portals — read-only here; builders and BRM work them in their portals</p>
        </div>
        @if ($available && $total)
          <span class="header-badge">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('lead', $total) }}</span>
        @endif
      </div>

      <form class="filter-bar" method="GET" action="{{ route('leads.index') }}">
        <div class="filter-tabs">
          <a class="filter-tab {{ $filters['status'] === '' ? 'active' : '' }}" href="{{ route('leads.index', $qs(['status' => null])) }}">All</a>
          @foreach (\App\Models\Housing\Lead::STATUSES as $key => $label)
            <a class="filter-tab {{ $filters['status'] === $key ? 'active' : '' }}" href="{{ route('leads.index', $qs(['status' => $key])) }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
          @endforeach
        </div>
        <div class="leads-filters">
          <input type="hidden" name="status" value="{{ $filters['status'] }}">
          <select class="form-control" name="platform" onchange="this.form.submit()" style="max-width:170px" aria-label="Source platform">
            <option value="">All sources</option>
            @foreach ($platforms as $p)
              <option value="{{ $p }}" @selected($filters['platform'] === $p)>{{ \App\Models\Housing\Lead::PLATFORMS[$p] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $p)) }}</option>
            @endforeach
          </select>
          <div class="search-container" style="flex:1">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="search" class="search-input" name="q" value="{{ $filters['q'] }}" placeholder="Search lead, buyer, phone, project..." aria-label="Search leads">
          </div>
          @if ($filters['status'] !== '' || $filters['platform'] !== '' || $filters['q'] !== '')
            <a class="btn-ghost" href="{{ route('leads.index') }}" style="text-decoration:none;white-space:nowrap">Clear</a>
          @endif
        </div>
      </form>

      <div class="card" style="padding:20px;">
        @if (! $available)
          <div class="leads-empty">The leads database isn’t reachable right now.</div>
        @else
          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr><th>LEAD</th><th>BUYER</th><th>INTERESTED IN</th><th>SOURCE</th><th>STATUS</th><th>HANDLED BY</th><th>LAST ACTIVITY</th></tr>
              </thead>
              <tbody>
                @forelse ($leads as $lead)
                  <tr>
                    <td><span class="lead-code">{{ $lead->lead_code }}</span></td>
                    <td>
                      <div class="lead-who">{{ $lead->name }}</div>
                      <div class="lead-sub">
                        @if ($lead->phone)<a href="tel:{{ $lead->phone }}" style="color:inherit">{{ $lead->phone }}</a>@endif
                        @if ($lead->phone && $lead->email) · @endif
                        @if ($lead->email)<a href="mailto:{{ $lead->email }}" style="color:inherit">{{ $lead->email }}</a>@endif
                        @if ($lead->city) · {{ $lead->city }}@endif
                      </div>
                    </td>
                    <td>
                      @php $title = $lead->project?->name ?: ($lead->interest_title ?: '—'); @endphp
                      @if ($lead->propertyKey())
                        <a href="{{ route('properties.show', $lead->propertyKey()) }}" class="table-property-title" style="color:inherit">{{ $title }}</a>
                      @else
                        {{ $title }}
                      @endif
                      <div class="lead-sub">{{ trim(($lead->unit_label ?: '').($lead->unit_label && $lead->builder_name ? ' · ' : '').($lead->builder_name ?: '')) }}</div>
                    </td>
                    <td>
                      <div>{{ $lead->platformLabel() }}</div>
                      <div class="lead-sub">{{ $lead->sourceLabel() }}</div>
                    </td>
                    <td><span class="badge {{ $lead->statusBadgeClass() }}">{{ $lead->statusLabel() }}</span></td>
                    <td>{{ $lead->assigned_to ?: 'Unassigned' }}</td>
                    <td>
                      @php $at = $lead->last_activity_at ?? $lead->created_at; @endphp
                      <div>{{ $at?->format('d M Y') ?? '—' }}</div>
                      <div class="lead-sub">{{ $at?->diffForHumans() }}</div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="leads-empty">{{ $filters['status'] === '' && $filters['platform'] === '' && $filters['q'] === '' ? 'No leads yet. Buyer enquiries from the website and portals show up here.' : 'No leads match these filters.' }}</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @include('partials.pagination', ['paginator' => $leads])
        @endif
      </div>

      <section class="card bargain-preview" aria-labelledby="bargainTitle">
        <h2 class="section-title" id="bargainTitle">Bargains <span class="preview-tag">Preview · not built yet</span></h2>
        <p class="setting-desc" style="margin:0;">Smart Bargain negotiations between buyers and sellers will be tracked here. Nothing on this card is live data — there are no bargains recorded yet.</p>
        <ol>
          <li>A buyer makes an offer on a listing or project that has Smart Bargain turned on.</li>
          <li>The seller or builder counters, accepts or declines; each step is logged.</li>
          <li>Admin will see every negotiation with its status (negotiating, accepted, rejected, expired) and the offer history.</li>
        </ol>
        <div class="preview-actions">
          <button type="button" class="btn-secondary" disabled aria-disabled="true">View negotiations</button>
          <button type="button" class="btn-secondary" disabled aria-disabled="true">Export bargains</button>
          <span class="setting-desc">Available once bargains are built.</span>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
