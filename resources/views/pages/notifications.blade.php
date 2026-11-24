<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - CMNHousing Admin Portal</title>
  <meta name="description" content="Hand-overs from Operations and escalations from builders.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .inbox-row{display:flex;gap:14px;align-items:flex-start;padding:14px 4px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit}
    .inbox-row:last-child{border-bottom:0}
    .inbox-row:hover{background:#f8fafc}
    .inbox-dot{width:8px;height:8px;border-radius:50%;margin-top:7px;flex-shrink:0;background:transparent}
    .inbox-row.unread .inbox-dot{background:#0284c7}
    .inbox-row.unread .inbox-title{font-weight:800}
    .inbox-title{font-weight:600;color:var(--text-primary)}
    .inbox-body{font-size:13px;color:var(--text-muted);margin-top:2px}
    .inbox-when{margin-left:auto;font-size:12px;color:var(--text-muted);white-space:nowrap}
    .inbox-empty{padding:28px;text-align:center;color:var(--text-muted)}
  </style>
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')

    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Notifications</h1>
          <p class="page-subtitle">Hand-overs from Operations and escalations from builders — shared by the Admin team</p>
        </div>
        @if ($unread)
          <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn-secondary" type="submit">Mark all read</button></form>
        @endif
      </div>

      @if (session('success'))
        <div class="card" role="status" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-emerald)">{{ session('success') }}</div>
      @endif

      <div class="filter-bar">
        <div class="filter-tabs">
          <a class="filter-tab {{ $tab === 'all' ? 'active' : '' }}" href="{{ route('notifications.index') }}">All</a>
          <a class="filter-tab {{ $tab === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['tab' => 'unread']) }}">Unread ({{ $unread }})</a>
        </div>
        <a href="{{ route('settings.index') }}#notifications" class="card-action-link">Bell preferences</a>
      </div>

      <div class="card" style="padding:8px 20px;">
        @if (! $available)
          <div class="inbox-empty">The notifications database isn’t reachable right now.</div>
        @else
          @forelse ($items as $n)
            <a class="inbox-row {{ $n->read_at ? '' : 'unread' }}" href="{{ route('notifications.open', $n->id) }}">
              <span class="inbox-dot" aria-hidden="true"></span>
              <span>
                <span class="inbox-title">{{ $n->title }}</span>
                @if ($n->body)<div class="inbox-body">{{ $n->body }}</div>@endif
              </span>
              <span class="inbox-when">{{ $n->when() }}</span>
            </a>
          @empty
            <div class="inbox-empty">{{ $tab === 'unread' ? 'No unread notifications.' : 'No notifications yet.' }}</div>
          @endforelse
        @endif
      </div>
      @if ($items)
        @include('partials.pagination', ['paginator' => $items])
      @endif
    </main>
  </div>
</body>
</html>
