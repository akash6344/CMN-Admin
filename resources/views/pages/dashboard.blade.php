<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - CMNHousing Admin Portal</title>
  <meta name="description" content="CMNHousing Admin Portal Dashboard for property listings, leads, bargains, and analytics.">
  <!-- Unified Stylesheet -->
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .brm-strip{display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px;padding:14px 20px;margin:-4px 0 24px;text-decoration:none;color:inherit}
    .brm-strip-label{font-weight:600;color:var(--text-secondary)}
    .brm-strip-value{font-size:20px}
    .brm-strip-note{font-size:12.5px;color:var(--text-muted)}
  </style>
  <!-- Chart.js for beautiful canvas charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
  <div class="app-container">
    <!-- Sidebar Overlay for Mobile -->
    @include('layouts.navigation')

      <!-- Main Page Content -->
      <main class="page-content">
        <div class="page-header">
          <h1 class="page-title">Dashboard</h1>
          <p class="page-subtitle">Weekly property pipeline overview, compared with the previous week.</p>
        </div>

        <!-- Metric cards — same stages as the Properties tabs; each opens the matching list -->
        <section class="stats-grid">
          <a class="stat-card" href="{{ route('properties.index', ['status' => 'with_ops']) }}" style="text-decoration:none;color:inherit" title="Submitted — Operations is still reviewing">
            <div class="stat-header">
              <span class="stat-title">With Operations</span>
              <div class="stat-icon-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15 14"></polyline></svg>
              </div>
            </div>
            <div class="stat-value">{{ number_format($weeklyMetrics['with_ops']['total']) }}</div>
            <div class="stat-trend {{ $weeklyMetrics['with_ops']['trend'] }}">
              <span>{{ $weeklyMetrics['with_ops']['label'] }}</span>
            </div>
          </a>

          <a class="stat-card" href="{{ route('properties.index', ['status' => 'pending']) }}" style="text-decoration:none;color:inherit" title="Reviewed by Operations — needs your decision">
            <div class="stat-header">
              <span class="stat-title">Awaiting Your Approval</span>
              <div class="stat-icon-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
              </div>
            </div>
            <div class="stat-value">{{ number_format($weeklyMetrics['awaiting']['total']) }}</div>
            <div class="stat-trend {{ $weeklyMetrics['awaiting']['trend'] }}">
              <span>{{ $weeklyMetrics['awaiting']['label'] }}</span>
            </div>
          </a>

          <a class="stat-card" href="{{ route('properties.index', ['status' => 'approved']) }}" style="text-decoration:none;color:inherit" title="Approved owner and agent listings">
            <div class="stat-header">
              <span class="stat-title">Approved</span>
              <div class="stat-icon-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
            </div>
            <div class="stat-value">{{ number_format($weeklyMetrics['approved']['total']) }}</div>
            <div class="stat-trend {{ $weeklyMetrics['approved']['trend'] }}">
              <span>{{ $weeklyMetrics['approved']['label'] }}</span>
            </div>
          </a>

          <a class="stat-card" href="{{ route('properties.index', ['status' => 'rejected']) }}" style="text-decoration:none;color:inherit" title="Queried to Operations, or edits requested from the owner / agent">
            <div class="stat-header">
              <span class="stat-title">Sent Back</span>
              <div class="stat-icon-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20v-7a4 4 0 0 0-4-4H4"></path></svg>
              </div>
            </div>
            <div class="stat-value">{{ number_format($weeklyMetrics['sent_back']['total']) }}</div>
            <div class="stat-trend {{ $weeklyMetrics['sent_back']['trend'] }}">
              <span>{{ $weeklyMetrics['sent_back']['label'] }}</span>
            </div>
          </a>

          <a class="stat-card" href="{{ route('properties.index', ['status' => 'approved']) }}" style="text-decoration:none;color:inherit" title="Website listings approved and shown">
            <div class="stat-header">
              <span class="stat-title">Live Listings</span>
              <div class="stat-icon-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </div>
            </div>
            <div class="stat-value">{{ number_format($weeklyMetrics['active']['total']) }}</div>
            <div class="stat-trend {{ $weeklyMetrics['active']['trend'] }}">
              <span>{{ $weeklyMetrics['active']['label'] }}</span>
            </div>
          </a>

          <a class="stat-card" href="{{ route('reports.index') }}" style="text-decoration:none;color:inherit" title="Total views across website listings">
            <div class="stat-header">
              <span class="stat-title">Listing Views</span>
              <div class="stat-icon-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
              </div>
            </div>
            <div class="stat-value">{{ number_format($weeklyMetrics['views']['total']) }}</div>
            <div class="stat-trend {{ $weeklyMetrics['views']['trend'] }}">
              <span>{{ $weeklyMetrics['views']['label'] }}</span>
            </div>
          </a>

        </section>

        <!-- Builder projects are reviewed by BRM, not Operations / Admin — a read-only count -->
        <a class="card brm-strip" href="{{ route('properties.index', ['status' => 'builder', 'brm' => 'with_brm']) }}" title="Builder projects are reviewed by the BRM team — view only">
          <span class="brm-strip-label">Builder projects with BRM</span>
          <strong class="brm-strip-value">{{ number_format($withBrm) }}</strong>
          <span class="brm-strip-note">Under review by the BRM team · view only</span>
        </a>

        <!-- Charts & Recent Activity Section -->
        <section class="dashboard-grid">
          <!-- Live property type breakdown -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">Listings by Property Type</h2>
            </div>
            <div class="chart-container">
              <canvas id="weeklyActivityChart"></canvas>
            </div>
          </div>

          <!-- Pending Properties Table -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">In the Pipeline</h2>
              <a href="{{ route('properties.index', ['status' => 'pending']) }}" class="card-action-link">View all</a>
            </div>
            <div class="table-responsive">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>PROPERTY</th>
                    <th>SUBMITTED BY</th>
                    <th>CITY</th>
                    <th>PRICE</th>
                    <th>STATUS</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($pendingProperties as $it)
                    <tr>
                      <td class="table-id">{{ $it['code'] }}</td>
                      <td><a class="table-property-title" style="color:inherit" href="{{ route('properties.show', $it['key']) }}">{{ $it['title'] ?: 'Untitled property' }}</a></td>
                      <td>{{ $it['from'] ?: '—' }}</td>
                      <td>{{ $it['city'] ?: '—' }}</td>
                      <td class="table-price">{{ $it['price'] }}</td>
                      <td><span class="badge {{ $it['stage'] === 'awaiting' ? 'badge-pending' : 'badge-active' }}">{{ $it['stage'] === 'awaiting' ? 'Awaiting you' : $it['stageLabel'] }}</span></td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">
                        {{ $hasPropertiesTable ? 'No listings in the pipeline right now.' : 'Connect the database to load property listings.' }}
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- Dashboard JavaScript Logic -->
  <script>
    // Mobile Sidebar Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (mobileMenuBtn && sidebar && sidebarOverlay) {
      mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        sidebarOverlay.classList.toggle('active');
      });

      sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
      });
    }

    // ── Profile Dropdown ──────────────────────────────────────────────────────
    const profileTrigger = document.getElementById('profileDropdownTrigger');
    const profileMenu    = document.getElementById('profileDropdownMenu');

    profileTrigger.addEventListener('click', (e) => {
      e.stopPropagation();
      profileMenu.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.profile-dropdown-wrapper')) {
        profileMenu.classList.remove('open');
      }
    });

    const propertyTypeCanvas = document.getElementById('weeklyActivityChart');
    if (propertyTypeCanvas && typeof Chart !== 'undefined') {
      new Chart(propertyTypeCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: {{ \Illuminate\Support\Js::from($propertyTypeLabels) }},
          datasets: [{
            label: 'Listings',
            data: {{ \Illuminate\Support\Js::from($propertyTypeValues) }},
            backgroundColor: ['#0f172a', '#f59e0b', '#2563eb', '#06b6d4', '#8b5cf6', '#10b981'],
            borderWidth: 0,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
          },
        },
      });
    }
  </script>
</body>
</html>
