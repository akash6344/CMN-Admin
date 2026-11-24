<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search - CMNHousing Admin Portal</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .sr-card{padding:18px 20px;margin-bottom:16px}
    .sr-card h2{font-size:15px;margin:0 0 12px;display:flex;justify-content:space-between;align-items:center}
    .sr-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit}
    .sr-row:last-child{border-bottom:0}.sr-row:hover strong{color:#0284c7}
    .sr-thumb{width:40px;height:40px;border-radius:8px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#64748b;flex:none;overflow:hidden}
    .sr-thumb img{width:100%;height:100%;object-fit:cover}
    .sr-meta{font-size:12.5px;color:var(--text-muted)}
    .sr-right{margin-left:auto;text-align:right;font-size:13px}
    .badge-draft{background:#e2e8f0;color:#475569}
  </style>
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')
    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Search</h1>
          <p class="page-subtitle">{{ $q === '' ? 'Type in the search bar to find properties and users.' : 'Results for “'.$q.'”' }}</p>
        </div>
      </div>

      @if ($q !== '')
        <section class="card sr-card">
          <h2>Properties <a class="card-action-link" href="{{ route('properties.index', ['q' => $q]) }}">See all in Properties →</a></h2>
          @forelse ($properties as $row)
            <a class="sr-row" href="{{ route('properties.show', $row['key']) }}">
              <span class="sr-thumb">@if ($row['cover'])<img src="{{ $row['cover'] }}" alt="">@else P @endif</span>
              <span><strong>{{ $row['code'] }} · {{ $row['title'] }}</strong><br><span class="sr-meta">{{ $row['from'] ?: '—' }} ({{ $row['role'] }}) · {{ $row['place'] ?: '—' }}</span></span>
              <span class="sr-right">{{ $row['price'] }}<br><span class="badge {{ $row['stageBadge'] }}">{{ $row['stageLabel'] }}</span></span>
            </a>
          @empty
            <p class="sr-meta" style="margin:0">No properties match.</p>
          @endforelse
        </section>

        @if (count($projects))
          <section class="card sr-card">
            <h2>Builder projects <a class="card-action-link" href="{{ route('properties.index', ['status' => 'builder', 'q' => $q]) }}">See all builder projects →</a></h2>
            @foreach ($projects as $row)
              <a class="sr-row" href="{{ route('properties.show', $row['key']) }}">
                <span class="sr-thumb">@if ($row['cover'])<img src="{{ $row['cover'] }}" alt="">@else PR @endif</span>
                <span><strong>{{ $row['code'] }} · {{ $row['title'] }}</strong><br><span class="sr-meta">{{ $row['from'] ?: '—' }} (Builder) · {{ $row['place'] ?: '—' }} · reviewed by BRM</span></span>
                <span class="sr-right">{{ $row['price'] }}<br><span class="badge {{ $row['stageBadge'] }}">{{ $row['stageLabel'] }}</span></span>
              </a>
            @endforeach
          </section>
        @endif

        <section class="card sr-card">
          <h2>Users <a class="card-action-link" href="{{ route('users.index') }}">Open Users →</a></h2>
          @forelse ($users as $user)
            <a class="sr-row" href="{{ route('users.properties', $user) }}">
              <span class="sr-thumb">{{ $user->initials() }}</span>
              <span><strong>{{ $user->name }}</strong><br><span class="sr-meta">{{ $user->email }}{{ $user->mobile ? ' · '.$user->mobile : '' }}</span></span>
              <span class="sr-right sr-meta">{{ $user->admin_role ? ucwords($user->admin_role) : ucfirst((string) $user->role) }}</span>
            </a>
          @empty
            <p class="sr-meta" style="margin:0">No users match.</p>
          @endforelse
        </section>
      @endif
    </main>
  </div>
</body>
</html>
