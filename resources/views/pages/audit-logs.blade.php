<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Audit Logs - CMNHousing Admin Portal</title>
  <meta name="description" content="View audit logs for admin actions across the CMNHousing portal.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')
    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Audit Logs</h1>
          <p class="page-subtitle">Track approvals, send-backs for edits, deletion request reviews, and other admin actions</p>
        </div>
      </div>

      <div class="card" style="padding:20px;">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="filter-bar" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
          <select class="form-control" name="action" style="max-width:240px;" onchange="this.form.submit()">
            <option value="">All actions</option>
            @foreach ($actions as $action)
              <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
            @endforeach
          </select>
          <div class="search-container" style="flex:1;min-width:220px;">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" class="search-input" name="q" value="{{ $filters['q'] }}" placeholder="Search description, action, or admin...">
          </div>
          <button class="btn-secondary" type="submit">Search</button>
          @if ($filters['q'] !== '' || $filters['action'] !== '')
            <a class="btn-ghost" href="{{ route('audit-logs.index') }}" style="text-decoration:none;display:inline-flex;align-items:center;">Clear</a>
          @endif
          <a class="btn-dark" href="{{ route('audit-logs.export', array_filter($filters)) }}" style="text-decoration:none;display:inline-flex;align-items:center;" download>Export CSV</a>
        </form>

        <div class="table-responsive">
          <table class="custom-table">
            <thead>
              <tr>
                <th>WHEN</th>
                <th>ADMIN</th>
                <th>ACTION</th>
                <th>DESCRIPTION</th>
                <th>DETAILS</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($logs as $log)
                <tr>
                  <td>
                    <div>{{ optional($log->created_at)?->format('d M Y, h:i A') }}</div>
                    <div style="font-size:12px;color:var(--text-muted);">{{ optional($log->created_at)?->diffForHumans() }}</div>
                  </td>
                  <td>{{ $log->user?->name ?: 'System' }}</td>
                  <td><span class="badge badge-pending">{{ $log->action }}</span></td>
                  <td>{{ $log->description }}</td>
                  <td style="max-width:260px;font-size:13px;color:var(--text-muted);">
                    @if (! empty($log->meta))
                      @foreach ($log->meta as $key => $value)
                        @if (is_scalar($value) || $value === null)
                          <div><strong>{{ str_replace('_', ' ', $key) }}:</strong> {{ $value === null || $value === '' ? '—' : $value }}</div>
                        @endif
                      @endforeach
                    @else
                      —
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" style="text-align:center;padding:28px;color:var(--text-muted);">{{ $filters['q'] !== '' || $filters['action'] !== '' ? 'No audit logs match these filters.' : 'No audit logs yet.' }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @include('partials.pagination', ['paginator' => $logs])
      </div>
    </main>
  </div>
</body>
</html>
