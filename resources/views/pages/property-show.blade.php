<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $it['code'] }} · {{ $it['title'] }} - CMNHousing Admin Portal</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .badge-draft{background:#e2e8f0;color:#475569}
    .pv-crumbs{display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:14px;flex-wrap:wrap}
    .pv-crumbs a{color:var(--text-secondary);text-decoration:none;font-weight:600}.pv-crumbs a:hover{color:var(--text-primary)}
    .pv-crumbs .sep{color:#cbd5e1}.pv-crumbs strong{color:var(--text-primary)}
    .pv-nav{margin-left:auto;display:flex;gap:8px}
    .pv-nav a{display:inline-flex;align-items:center;gap:4px;padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:var(--text-primary);font-weight:600;font-size:13px;text-decoration:none}
    .pv-nav a.off{opacity:.4;pointer-events:none}
    .pv-head{display:flex;gap:18px;align-items:center;padding:18px 20px;margin-bottom:16px}
    .pv-head-thumb{width:84px;height:84px;border-radius:12px;overflow:hidden;background:#e2e8f0;flex:none;display:flex;align-items:center;justify-content:center;font-weight:800;color:#64748b}
    .pv-head-thumb img{width:100%;height:100%;object-fit:cover}
    .pv-head-main{flex:1;min-width:0}
    .pv-tags{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .pv-code{background:#0f172a;color:#fff;font-weight:700;font-size:12px;padding:3px 8px;border-radius:6px;font-family:ui-monospace,monospace}
    .src-chip{display:inline-block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.02em;padding:3px 8px;border-radius:999px}
    .src-chip.owner{background:#ecfdf5;color:#047857}.src-chip.agent{background:#eef2ff;color:#4338ca}.src-chip.builder{background:#fff7ed;color:#c2410c}
    .pv-head h1{margin:8px 0 4px;font-size:22px}
    .pv-meta{display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:var(--text-muted)}
    .pv-price{text-align:right}.pv-price strong{display:block;font-size:26px;color:#0284c7}.pv-price small{color:var(--text-muted);font-size:12.5px}
    .pv-banner{display:flex;gap:12px;align-items:flex-start;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:13.5px}
    .pv-banner.pending{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}
    .pv-banner.query{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412}
    .pv-banner.ops{background:#f8fafc;border:1px solid #e2e8f0;color:#475569}
    .pv-banner.live{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
    .pv-banner.edits{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
    .pv-banner p{margin:4px 0 0}
    .pv-grid{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:16px;align-items:start}
    .pv-main{display:flex;flex-direction:column;gap:16px;min-width:0}
    .pv-side{display:flex;flex-direction:column;gap:16px;position:sticky;top:16px}
    .pv-card{padding:18px 20px}
    .pv-card h2{font-size:15px;margin:0 0 14px;display:flex;align-items:center;gap:8px;justify-content:space-between}
    .pv-count{background:#f1f5f9;color:#475569;border-radius:999px;padding:1px 8px;font-size:12px}
    .pv-photo{width:100%;aspect-ratio:16/8;object-fit:cover;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px}
    .pv-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:0}
    .pv-facts div{background:#f8fafc;border-radius:10px;padding:10px 12px}.pv-facts dt{font-size:11.5px;color:var(--text-muted)}.pv-facts dd{margin:3px 0 0;font-weight:600;font-size:13.5px;word-break:break-word}
    .pv-sub{font-size:12px;font-weight:700;color:var(--text-muted);letter-spacing:.04em;text-transform:uppercase;margin:18px 0 8px}
    .pv-desc{font-size:13.5px;line-height:1.6;white-space:pre-line;margin:0;color:var(--text-secondary)}
    .pv-table{width:100%;border-collapse:collapse;font-size:13px}.pv-table th{text-align:left;font-size:11.5px;color:var(--text-muted);font-weight:700;padding:8px;border-bottom:1px solid #eef2f7}.pv-table td{padding:10px 8px;border-bottom:1px solid #f1f5f9}
    .pv-file{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13.5px}.pv-file:last-child{border-bottom:0}
    .pv-thread{list-style:none;margin:0;padding:0;display:grid;gap:14px}
    .pv-thread li{display:grid;grid-template-columns:32px 1fr;gap:10px;font-size:13px}
    .pv-who{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:#f1f5f9;color:#94a3b8}
    .pv-who.ops{background:#e0f2fe;color:#0369a1}.pv-who.admin{background:#ede9fe;color:#6d28d9}
    .pv-thread small{color:var(--text-muted);font-size:11.5px;margin-left:6px}
    .pv-thread p{margin:5px 0 0;background:#f8fafc;border-radius:8px;padding:8px 10px;white-space:pre-line;line-height:1.5}
    .pv-thread li.query p{background:#fff7ed}
    .pv-score{display:flex;gap:14px;align-items:center;margin-bottom:12px}
    .pv-ring{--v:0;width:64px;height:64px;border-radius:50%;background:conic-gradient(var(--c) calc(var(--v)*1%), #e2e8f0 0);display:flex;align-items:center;justify-content:center;flex:none}
    .pv-ring span{width:50px;height:50px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:var(--c)}
    .pv-checks{display:grid;gap:7px;font-size:13px;margin:0;padding:0;list-style:none}
    .pv-ok{color:#059669}.pv-bad{color:#dc2626}.pv-na{color:#94a3b8}
    .pv-label{font-size:11.5px;font-weight:700;color:var(--text-muted);letter-spacing:.04em;margin:14px 0 8px}
    .pv-note{border-radius:10px;padding:10px 12px;font-size:13px;line-height:1.5;margin:0 0 10px;background:#f0f9ff;border:1px solid #bae6fd}
    .pv-note.flag{background:#fef2f2;border-color:#fecaca;color:#991b1b}
    .pv-actions{display:grid;gap:8px}
    .pv-actions .row{display:grid;grid-template-columns:1fr;gap:8px}
    .pv-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px 14px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;border:1px solid transparent;font-family:inherit}
    .pv-btn.approve{background:#10b981;color:#fff}.pv-btn.approve:hover{background:#059669}
    .pv-btn.reject{background:#fff;color:#dc2626;border-color:#fca5a5}.pv-btn.reject:hover{background:#fef2f2}
    .pv-muted{font-size:13px;color:var(--text-muted);margin:0;line-height:1.5}
    .pv-person{display:flex;gap:10px;align-items:center}.pv-person .av{width:40px;height:40px;border-radius:50%;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex:none}
    .pv-contact{display:grid;gap:6px;margin-top:12px;font-size:13px}.pv-contact a{color:var(--text-secondary);text-decoration:none}
    @media (max-width:1100px){.pv-grid{grid-template-columns:1fr}.pv-side{position:static}.pv-facts{grid-template-columns:1fr 1fr}}
  </style>
</head>
<body>
  @php
    $r = $it['review']; $c = $r?->checks ?? []; $m = $r?->manual_checks ?? [];
    $tone = fn ($v) => $v === null ? '#94a3b8' : ($v >= 90 ? '#10b981' : ($v >= 70 ? '#f59e0b' : '#ef4444'));
    $who = strtolower($it['role']);
    $initials = collect(preg_split('/\s+/', trim($it['from'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
    $eventLabels = [
        'submitted' => 'Submitted', 'resubmitted' => 'Resubmitted with edits', 'checked' => 'Automated checks ran', 'in_review' => 'Operations started the review',
        'note' => 'Operations note', 'edited' => 'Operations corrected details', 'changes_requested' => 'Operations asked for edits', 'flagged' => 'Flagged by Operations',
        'unflagged' => 'Flag cleared', 'ready_for_admin' => 'Sent to Admin for approval', 'admin_query' => 'Rejected by Admin — sent back to Operations', 'returned' => 'Rejected by Admin — sent back to Operations',
        'approved' => 'Approved by Admin', 'rejected' => 'Rejected',
    ];
    $brm = $it['brm'];
    $brmBanner = ['with_brm' => 'ops', 'live' => 'live', 'changes' => 'edits', 'rejected' => 'query', 'taken_down' => 'query'];
  @endphp
  <div class="app-container">
    @include('layouts.navigation')
    <main class="page-content">
      <nav class="pv-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('properties.index') }}">Properties</a><span class="sep">/</span><strong>{{ $it['code'] }}</strong>
        <span class="pv-nav">
          <a class="{{ $prev ? '' : 'off' }}" href="{{ $prev ? route('properties.show', $prev) : '#' }}">‹ Previous</a>
          <a class="{{ $next ? '' : 'off' }}" href="{{ $next ? route('properties.show', $next) : '#' }}">Next ›</a>
        </span>
      </nav>

      @if (session('success'))
        <div class="card" role="status" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-emerald)">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="card" role="alert" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-red)">{{ $errors->first() }}</div>
      @endif

      <header class="card pv-head">
        <div class="pv-head-thumb">@if ($it['cover'])<img src="{{ $it['cover'] }}" alt="">@else{{ $it['kind'] === 'project' ? 'PR' : 'P' }}@endif</div>
        <div class="pv-head-main">
          <div class="pv-tags">
            <span class="pv-code">{{ $it['code'] }}</span>
            <span class="src-chip {{ $who }}">{{ $it['kindLabel'] }}</span>
            <span class="badge {{ $it['stageBadge'] }}">{{ $it['stageLabel'] }}</span>
            @if ($r?->flagged)<span class="badge badge-rejected" title="{{ $r->flag_reason }}">⚑ Flagged</span>@endif
          </div>
          <h1>{{ $it['title'] }}</h1>
          <div class="pv-meta"><span>📍 {{ $it['place'] ?: 'No location' }}</span><span>{{ $it['type'] ?: '—' }}</span><span>Submitted {{ $it['created']?->format('j M Y') }}</span></div>
        </div>
        <div class="pv-price"><strong>{{ $it['price'] }}</strong><small>by {{ $it['from'] ?: '—' }}</small></div>
      </header>

      {{-- where it is in the flow --}}
      @if ($brm)
        <div class="pv-banner {{ $brmBanner[$it['stage']] ?? 'ops' }}"><div>
          <strong>{{ $it['stageLabel'] }}{{ $it['stage'] !== 'with_brm' && $brm['at'] ? ' · '.$brm['at']->format('j M Y, g:i a') : '' }}</strong>
          @if ($it['stage'] === 'with_brm')
            <p>Submitted by the builder and waiting for the BRM team’s review. Builder projects are reviewed by BRM only — there is nothing for Admin to approve.</p>
          @else
            <p>{{ $brm['by'] ? 'Reviewed by '.$brm['by'] : 'Reviewed by the BRM team' }}{{ $brm['at'] ? ' on '.$brm['at']->format('j M Y') : '' }}.</p>
          @endif
          @if ($brm['reason'] && $it['stage'] !== 'live')<p>“{{ $brm['reason'] }}”</p>@endif
        </div></div>
      @else
      @switch ($it['stage'])
        @case('awaiting')
          <div class="pv-banner pending"><div><strong>Pending your approval</strong><p>Operations reviewed this and sent it {{ $r?->submitted_to_admin_at?->diffForHumans() }}{{ $r?->reviewer_name ? ' ('.$r->reviewer_name.')' : '' }}. Approve to publish, or reject it back to Operations with a comment.</p></div></div>
          @break
        @case('queries')
          <div class="pv-banner query"><div><strong>Rejected — with Operations{{ $r?->admin_query_at ? ' · '.$r->admin_query_at->diffForHumans() : '' }}</strong><p>“{{ $r?->admin_note }}”</p><p>It comes back here as Pending once Operations has fixed it.</p></div></div>
          @break
        @case('with_ops')
          <div class="pv-banner ops"><div><strong>With Operations</strong><p>Operations is still reviewing this{{ $r?->reviewer_name ? ' ('.$r->reviewer_name.')' : '' }}. It comes to you as Pending when they send it.</p></div></div>
          @break
        @case('edits')
          <div class="pv-banner edits"><div><strong>Edits requested from the {{ $who }}</strong>@if ($r?->change_request)<p>“{{ $r->change_request }}”</p>@endif<p>It goes back through Operations when they resubmit.</p></div></div>
          @break
        @case('live')
          <div class="pv-banner live"><div><strong>Approved — live on the website</strong><p>{{ $r?->decided_by ? 'Approved by '.$r->decided_by.($r->decided_at ? ' on '.$r->decided_at->format('j M Y') : '').'.' : 'Published on the website.' }}@if ($r?->admin_note) Note: {{ $r->admin_note }}@endif</p></div></div>
          @break
      @endswitch
      @endif

      <div class="pv-grid">
        <div class="pv-main">
          <section class="card pv-card">
            <h2>Photos</h2>
            @if ($it['cover'])<img class="pv-photo" src="{{ $it['cover'] }}" alt="Cover photo">@else<div class="pv-photo">No photos available</div>@endif
          </section>

          <section class="card pv-card">
            <h2>{{ $it['kind'] === 'project' ? 'Project' : 'Property' }} Details</h2>
            <dl class="pv-facts">
              <div><dt>Price</dt><dd>{{ $it['price'] }}</dd></div>
              @foreach ($it['facts'] as $k => $v)<div><dt>{{ $k }}</dt><dd>{{ filled($v) ? $v : '—' }}</dd></div>@endforeach
              <div><dt>RERA</dt><dd>{{ $it['rera'] ?: '—' }}</dd></div>
              @if ($it['kind'] === 'listing')
                <div><dt>Views</dt><dd>{{ $websiteViews ? number_format($websiteViews['total']) : '—' }}</dd></div>
                <div><dt>Views (last 30 days)</dt><dd>{{ $websiteViews ? number_format($websiteViews['last_30']) : '—' }}</dd></div>
              @endif
            </dl>
            <div class="pv-sub">Description</div>
            <p class="pv-desc">{{ $it['description'] ?: 'No description.' }}</p>
          </section>

          @if ($it['units'])
            <section class="card pv-card">
              <h2>Units &amp; Pricing <span class="pv-count">{{ count($it['units']) }}</span></h2>
              <table class="pv-table">
                <thead><tr><th>Unit</th><th>Built-up area</th><th>Price</th><th>₹/sq.ft</th></tr></thead>
                <tbody>
                  @foreach ($it['units'] as $u)
                    <tr><td><strong>{{ $u['type'] }}</strong></td><td>{{ number_format($u['area']) }} sq.ft</td><td>{{ \App\Models\PostProperty::money($u['price']) }}</td><td>{{ $u['area'] > 0 ? '₹'.number_format($u['price'] / $u['area']) : '—' }}</td></tr>
                  @endforeach
                </tbody>
              </table>
            </section>
          @endif

          <section class="card pv-card">
            <h2>Documents <span class="pv-count">{{ count($it['documents']) }}</span></h2>
            @forelse ($it['documents'] as $doc)
              <div class="pv-file">📄 <span>{{ $doc }}</span></div>
            @empty
              <p class="pv-muted">No documents attached.</p>
            @endforelse
          </section>

          @unless ($brm)
          <section class="card pv-card">
            <h2>Comments &amp; Activity</h2>
            @if ($r && $r->events->isNotEmpty())
              <ol class="pv-thread">
                @foreach ($r->events as $e)
                  @php
                    $isAdmin = in_array($e->event, ['admin_query', 'returned', 'approved'], true);
                    $isSys = in_array($e->event, ['submitted', 'resubmitted', 'checked'], true) || $e->actor === 'System';
                  @endphp
                  <li class="{{ in_array($e->event, ['admin_query', 'returned'], true) ? 'query' : '' }}">
                    <span class="pv-who {{ $isSys ? '' : ($isAdmin ? 'admin' : 'ops') }}">{{ $isSys ? '•' : mb_strtoupper(mb_substr((string) $e->actor, 0, 1)) }}</span>
                    <div>
                      <strong>{{ $eventLabels[$e->event] ?? ucfirst(str_replace('_', ' ', $e->event)) }}</strong><small>{{ $e->actor ? $e->actor.' · ' : '' }}{{ $e->created_at?->format('j M, g:i a') }}</small>
                      @if ($e->note)<p>{{ $e->note }}</p>@endif
                    </div>
                  </li>
                @endforeach
              </ol>
            @else
              <p class="pv-muted">No comments yet. Operations’ notes and your comments show here.</p>
            @endif
          </section>
          @endunless
        </div>

        <aside class="pv-side">
          @if ($brm)
          <section class="card pv-card">
            <h2>BRM Review</h2>
            <dl class="pv-facts" style="grid-template-columns:1fr">
              <div><dt>State</dt><dd><span class="badge {{ $it['stageBadge'] }}">{{ $it['stageLabel'] }}</span></dd></div>
              <div><dt>Reviewed by</dt><dd>{{ $brm['by'] ?: '—' }}</dd></div>
              <div><dt>Reviewed on</dt><dd>{{ $brm['at']?->format('j M Y, g:i a') ?: '—' }}</dd></div>
              @if ($brm['reason'])<div><dt>Reason</dt><dd>{{ $brm['reason'] }}</dd></div>@endif
            </dl>
            <p class="pv-muted" style="margin-top:12px">View only. Builder projects are published, sent back, rejected or taken down by the BRM team.</p>
            <a class="pv-btn reject" style="margin-top:12px;text-decoration:none;color:var(--text-secondary);border-color:#e2e8f0" href="{{ $brm['url'] }}" target="_blank" rel="noopener">Open in BRM Project Approvals ↗</a>
          </section>
          @else
          <section class="card pv-card">
            <h2>Your Decision</h2>
            @if ($it['stage'] === 'awaiting' && $r)
              <div class="pv-actions">
                <form method="POST" action="{{ route('properties.reviews.approve', $r) }}" data-confirm="{{ $it['title'] }} goes live on the website and the {{ $who }} is notified." data-confirm-title="Approve {{ $it['code'] }}?" data-confirm-label="Approve" data-confirm-danger="false">
                  @csrf @method('PATCH')
                  <button class="pv-btn approve" type="submit">✓ Approve &amp; Publish</button>
                </form>
                <button class="pv-btn reject" type="button" data-reject-url="{{ route('properties.reviews.query', $r) }}" data-reject-code="{{ $it['code'] }}" onclick="openRejectProperty(this)">✕ Reject</button>
                <p class="pv-muted">Reject sends it back to Operations with your comment.</p>
              </div>
            @else
              <p class="pv-muted">{{ match ($it['stage']) {
                  'queries' => 'Waiting for Operations to answer your comment.',
                  'with_ops' => 'Nothing to decide yet — Operations is reviewing it.',
                  'edits' => 'Waiting for the '.$who.'’s edits.',
                  'live' => 'Already approved and live.',
                  default => $it['stageLabel'].'.',
              } }}</p>
            @endif
          </section>

          <section class="card pv-card">
            <h2>Operations Review</h2>
            @if ($r)
              <div class="pv-score">
                <div class="pv-ring" style="--v:{{ $r->score ?? 0 }};--c:{{ $tone($r->score) }}"><span>{{ $r->score !== null ? $r->score.'%' : '—' }}</span></div>
                <div><strong>Automated score</strong><p class="pv-muted">Reviewed by {{ $r->reviewer_name ?: '—' }}</p></div>
              </div>
              @if ($r->flagged)<p class="pv-note flag">⚑ {{ $r->flag_reason }}</p>@endif
              @if ($r->notes)<p class="pv-note"><strong>Operations’ notes:</strong> {{ $r->notes }}</p>@endif
              <div class="pv-label">AUTOMATED CHECKS</div>
              <ul class="pv-checks">
                @foreach ($r->checkLabels() as $k => $label)
                  @php $v = $c[$k] ?? null; @endphp
                  <li class="{{ $v === true ? 'pv-ok' : ($v === false ? 'pv-bad' : 'pv-na') }}">{{ $v === true ? '✓' : ($v === false ? '✗' : '–') }} {{ $label }}</li>
                @endforeach
              </ul>
              <div class="pv-label">CHECKED BY OPERATIONS</div>
              <ul class="pv-checks">
                @foreach ($r->manualLabels() as $k => $label)
                  <li class="{{ ! empty($m[$k]) ? 'pv-ok' : 'pv-na' }}">{{ ! empty($m[$k]) ? '✓' : '–' }} {{ $label }}</li>
                @endforeach
              </ul>
            @else
              <p class="pv-muted">Not reviewed by Operations{{ $it['stage'] === 'live' ? ' — published before the review flow started.' : ' yet.' }}</p>
            @endif
          </section>
          @endif

          <section class="card pv-card">
            <h2>{{ $it['role'] }}</h2>
            <div class="pv-person"><span class="av">{{ $initials }}</span><div><strong>{{ $it['from'] ?: '—' }}</strong><div class="pv-muted" style="font-size:12px">{{ $it['kindLabel'] }}</div></div></div>
            <div class="pv-contact">
              @if ($it['phone'])<a href="tel:{{ $it['phone'] }}">📞 {{ $it['phone'] }}</a>@endif
              @if ($it['email'])<a href="mailto:{{ $it['email'] }}">✉️ {{ $it['email'] }}</a>@endif
              @if (! $it['phone'] && ! $it['email'])<span class="pv-muted">Contact through the {{ $it['kind'] === 'project' ? 'builder portal' : 'website' }}.</span>@endif
            </div>
          </section>
        </aside>
      </div>
    </main>
  </div>

  @include('partials.property-modals', ['withAdd' => false])
  @include('partials.confirm-modal')
</body>
</html>
