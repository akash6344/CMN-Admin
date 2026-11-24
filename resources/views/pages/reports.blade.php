<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports &amp; Analytics - CMNHousing Admin Portal</title>
  <meta name="description" content="Comprehensive insights and analytics into platform performance.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')

      <main class="page-content">
        <div class="page-header-actions">
          <div>
            <h1 class="page-title">Reports &amp; Analytics</h1>
            <p class="page-subtitle">Comprehensive insights into platform performance</p>
          </div>

          <div style="display: flex; align-items: center; gap: 12px;">
            <div style="position: relative;">
              <select class="btn-ghost" id="timeframeSelect" aria-label="Report period" style="padding-right: 32px; appearance: none; -webkit-appearance: none; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                @foreach ($ranges as $key => [$label])
                  <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
                @endforeach
              </select>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted);">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>

            <a class="btn-dark" id="exportReportBtn" href="{{ route('reports.export', ['range' => $range]) }}" style="text-decoration:none" download>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
              Export
            </a>
          </div>
        </div>

        <div class="reports-grid">
          <div class="report-card">
            <h3 class="report-card-title">{{ $bucketUnit === 'day' ? 'Daily' : 'Monthly' }} Listings <span style="font-weight:500;color:var(--text-muted);font-size:13px">· {{ $totals['listings'] }} new</span></h3>
            <div class="chart-wrapper-lg">
              <canvas id="monthlyBarChart"></canvas>
            </div>
            <div class="chart-legend" style="justify-content: center; margin-top: 16px;">
              <div class="legend-item">
                <span class="legend-dot" style="background-color: #0f172a;"></span>
                <span>Listings</span>
              </div>
            </div>
          </div>

          <div class="report-card">
            <h3 class="report-card-title">Property Type Distribution</h3>
            <div class="chart-wrapper-lg">
              <canvas id="donutChart"></canvas>
            </div>
            <div class="chart-legend" style="justify-content: center; gap: 14px; flex-wrap: wrap; margin-top: 16px;">
              @forelse ($propertyTypeLegend as $index => $item)
                @php
                  $colors = ['#0f172a', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#ef4444', '#84cc16'];
                  $color = $colors[$index % count($colors)];
                @endphp
                <div class="legend-item"><span class="legend-dot" style="background-color: {{ $color }};"></span><span>{{ $item['label'] }} ({{ $item['percent'] }}%)</span></div>
              @empty
                <div class="legend-item"><span>No property type data yet</span></div>
              @endforelse
            </div>
          </div>

          <div class="report-card">
            <h3 class="report-card-title">Revenue Trend (₹) <span style="font-weight:500;color:var(--text-muted);font-size:13px">· ₹{{ number_format($totals['revenue']) }} from {{ $totals['payments'] }} paid</span></h3>
            <div class="chart-wrapper-lg">
              <canvas id="revenueLineChart"></canvas>
            </div>
          </div>

          <div class="report-card">
            <h3 class="report-card-title">Properties by City</h3>
            <div class="chart-wrapper-lg">
              <canvas id="cityBarChart"></canvas>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <div class="toast-notification" id="toast">Analytics report exported</div>

  <script>
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

    const profileTrigger = document.getElementById('profileDropdownTrigger');
    const profileMenu = document.getElementById('profileDropdownMenu');

    if (profileTrigger && profileMenu) {
      profileTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        profileMenu.classList.toggle('open');
      });

      document.addEventListener('click', (e) => {
        if (!e.target.closest('.profile-dropdown-wrapper')) {
          profileMenu.classList.remove('open');
        }
      });
    }

    function showToast(msg) {
      const toast = document.getElementById('toast');
      toast.textContent = msg;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 3000);
    }

    document.getElementById('exportReportBtn').addEventListener('click', () => {
      showToast('Downloading the {{ strtolower($ranges[$range][0]) }} report as CSV…');
    });

    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

    const monthlyLabels = @json($monthlyLabels);
    const monthlyValues = @json($monthlyValues);
    const propertyTypeLabels = @json($propertyTypeLabels);
    const propertyTypeValues = @json($propertyTypeValues);
    const cityLabels = @json($cityLabels);
    const cityValues = @json($cityValues);
    const chartColors = ['#0f172a', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#ef4444', '#84cc16'];

    const monthlyBarChart = new Chart(document.getElementById('monthlyBarChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: monthlyLabels,
        datasets: [
          {
            label: 'Listings',
            data: monthlyValues,
            backgroundColor: '#0f172a',
            borderRadius: 4,
            barPercentage: 0.6,
            categoryPercentage: 0.5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
          y: { beginAtZero: true, grid: { borderDash: [3, 3], color: '#f1f5f9' }, ticks: { color: '#94a3b8', precision: 0 } }
        }
      }
    });

    new Chart(document.getElementById('donutChart').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: propertyTypeLabels.length ? propertyTypeLabels : ['No data'],
        datasets: [{
          data: propertyTypeValues.length ? propertyTypeValues : [1],
          backgroundColor: propertyTypeLabels.map((_, index) => chartColors[index % chartColors.length]),
          borderWidth: 3,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: { legend: { display: false } }
      }
    });

    const ctxRev = document.getElementById('revenueLineChart').getContext('2d');
    const greenGradient = ctxRev.createLinearGradient(0, 0, 0, 240);
    greenGradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
    greenGradient.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

    const revenueLineChart = new Chart(ctxRev, {
      type: 'line',
      data: {
        labels: monthlyLabels,
        datasets: [{
          label: 'Revenue',
          data: @json($revenueValues),
          borderColor: '#10b981',
          backgroundColor: greenGradient,
          fill: true,
          tension: 0.4,
          borderWidth: 2.5,
          pointRadius: 3,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: '#10b981'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (context) => ` Revenue: ₹${Number(context.parsed.y).toLocaleString('en-IN')}`
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
          y: { beginAtZero: true, ticks: { color: '#94a3b8', callback: (v) => '₹' + Number(v).toLocaleString('en-IN') }, grid: { borderDash: [3, 3], color: '#f1f5f9' } }
        }
      }
    });

    new Chart(document.getElementById('cityBarChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: cityLabels.length ? cityLabels : ['No data'],
        datasets: [{
          data: cityValues.length ? cityValues : [0],
          backgroundColor: '#0284c7',
          borderRadius: 4,
          barThickness: 16
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true, grid: { borderDash: [3, 3], color: '#f1f5f9' }, ticks: { color: '#94a3b8', precision: 0 } },
          y: { grid: { display: false }, ticks: { color: '#475569', font: { weight: '600' } } }
        }
      }
    });

    // period → reload the charts for that range
    document.getElementById('timeframeSelect').addEventListener('change', (e) => {
      const url = new URL(window.location.href);
      url.searchParams.set('range', e.target.value);
      window.location.href = url.toString();
    });
  </script>
</body>
</html>
