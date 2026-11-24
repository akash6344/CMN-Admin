@php
  $inr = fn ($v) => '₹'.number_format((float) $v, ((float) $v == floor((float) $v)) ? 0 : 2);
  $statusBadge = ['paid' => 'badge-approved', 'pending' => 'badge-pending', 'failed' => 'badge-rejected', 'refunded' => 'badge-negotiating'];
  $trendMax = max(1, ...array_column($trend, 'amount'));
  $planMax = max(1, ...array_column($byPlan ?: [['amount' => 0]], 'amount'));
  $delta = $stats['prev_month'] > 0 ? round(($stats['this_month'] - $stats['prev_month']) / $stats['prev_month'] * 100) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transactions - CMNHousing Admin Portal</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .tx-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 8px 0 24px; }
    .tx-stats .stat-card { min-height: 128px; }
    .stat-note { margin-top: 10px; color: var(--text-muted); font-size: 12.5px; font-weight: 600; }
    .stat-note.up { color: var(--accent-emerald); }
    .stat-note.down { color: #e11d48; }
    .tx-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .tx-card { padding: 20px; }
    .tx-card h3 { margin: 0 0 4px; font-size: 15.5px; }
    .tx-card p.sub { margin: 0 0 16px; color: var(--text-muted); font-size: 12.5px; }
    .tx-bars { display: flex; align-items: flex-end; gap: 14px; height: 150px; padding-top: 18px; }
    .tx-bar { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end; }
    .tx-bar i { display: block; width: 100%; max-width: 44px; border-radius: 8px 8px 4px 4px; background: linear-gradient(180deg, #60a5fa, #2563eb); min-height: 4px; }
    .tx-bar b { font-size: 11.5px; color: #122033; white-space: nowrap; }
    .tx-bar span { font-size: 12px; color: var(--text-muted); }
    .tx-rows { display: grid; gap: 12px; }
    .tx-row-label { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px; gap: 8px; }
    .tx-row-label b { white-space: nowrap; }
    .tx-track { height: 8px; border-radius: 999px; background: #eef2f6; overflow: hidden; }
    .tx-track i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #34d399, #059669); }
    .tx-empty { color: var(--text-muted); font-size: 13px; padding: 20px 0; text-align: center; }
    .tx-filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
    .tx-filters .form-control { width: auto; min-width: 140px; height: 42px; }
    .tx-filters .search-container { flex: 1; min-width: 220px; }
    .tx-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; color: #526277; }
    .tx-sub { display: block; color: var(--text-muted); font-size: 12px; margin-top: 2px; }
    .tx-amount { font-weight: 800; color: #122033; white-space: nowrap; }
    .tx-dl { display: grid; grid-template-columns: 150px 1fr; gap: 10px 16px; font-size: 13.5px; }
    .tx-dl dt { color: var(--text-muted); }
    .tx-dl dd { margin: 0; font-weight: 600; word-break: break-all; }
    .tx-notice { padding: 14px 18px; margin-bottom: 16px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; font-size: 13.5px; }
    @media (max-width: 1100px) { .tx-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .tx-grid { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .tx-stats { grid-template-columns: 1fr; } .tx-dl { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<div class="app-container">
  @include('layouts.navigation')
  <main class="page-content">
    <div class="page-header-actions">
      <div>
        <h1 class="page-title">Transactions</h1>
        <p class="page-subtitle">Plan payments made on CMNHousing through Razorpay.</p>
      </div>
      <a class="btn-secondary" href="{{ route('payment-transactions.export', array_filter($filters)) }}" style="text-decoration:none">Export CSV</a>
    </div>

    @unless ($available)
      <div class="tx-notice">The <code>payment_transactions</code> table isn't reachable. Set <code>LIVE_DB_*</code> in <code>.env</code>, or load a dump with <code>php artisan live:import-dump ../payment_transactions.sql</code>.</div>
    @endunless

    <section class="stats-grid tx-stats">
      <div class="stat-card">
        <div class="stat-header"><span class="stat-title">TOTAL COLLECTED</span><span class="stat-icon-wrapper" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3M9 13c6.67 0 6.67-10 0-10"/></svg></span></div>
        <div class="stat-value">{{ $inr($stats['collected']) }}</div>
        <div class="stat-note">{{ $inr($stats['net']) }} net + {{ $inr($stats['gst']) }} GST</div>
      </div>
      <div class="stat-card">
        <div class="stat-header"><span class="stat-title">THIS MONTH</span><span class="stat-icon-wrapper" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span></div>
        <div class="stat-value">{{ $inr($stats['this_month']) }}</div>
        <div class="stat-note {{ $delta === null ? '' : ($delta >= 0 ? 'up' : 'down') }}">{{ $delta === null ? 'No payments last month' : ($delta >= 0 ? '+' : '').$delta.'% vs last month' }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-header"><span class="stat-title">PAID ORDERS</span><span class="stat-icon-wrapper" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg></span></div>
        <div class="stat-value">{{ $stats['paid_count'] }}</div>
        <div class="stat-note">{{ $stats['subscribers'] }} paying {{ $stats['subscribers'] === 1 ? 'user' : 'users' }} · avg {{ $inr($stats['avg_order']) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-header"><span class="stat-title">PENDING / FAILED</span><span class="stat-icon-wrapper" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span></div>
        <div class="stat-value">{{ $stats['pending_count'] }} / {{ $stats['failed_count'] }}</div>
        <div class="stat-note">Checkouts not completed</div>
      </div>
    </section>

    <section class="tx-grid">
      <div class="card tx-card">
        <h3>Monthly Collections</h3>
        <p class="sub">Paid amount incl. GST · last 6 months</p>
        <div class="tx-bars">
          @foreach ($trend as $m)
            <div class="tx-bar">
              <b>{{ $m['amount'] > 0 ? $inr($m['amount']) : '' }}</b>
              <i style="height: {{ $m['amount'] > 0 ? max(4, round($m['amount'] / $trendMax * 100)) : 2 }}%"></i>
              <span>{{ $m['label'] }}</span>
            </div>
          @endforeach
        </div>
      </div>
      <div class="card tx-card">
        <h3>Revenue by Plan</h3>
        <p class="sub">Paid orders per plan</p>
        <div class="tx-rows">
          @forelse ($byPlan as $p)
            <div>
              <div class="tx-row-label"><span>{{ $p['label'] }} <span class="tx-sub" style="display:inline">· {{ $p['count'] }}×</span></span><b>{{ $inr($p['amount']) }}</b></div>
              <div class="tx-track"><i style="width: {{ round($p['amount'] / $planMax * 100) }}%"></i></div>
            </div>
          @empty
            <div class="tx-empty">No paid orders yet.</div>
          @endforelse
        </div>
      </div>
      <div class="card tx-card">
        <h3>Revenue by Audience</h3>
        <p class="sub">Who is paying</p>
        <div class="tx-rows">
          @php $audMax = max(1, ...array_column($byAudience ?: [['amount' => 0]], 'amount')); @endphp
          @forelse ($byAudience as $a)
            <div>
              <div class="tx-row-label"><span>{{ $a['label'] }}</span><b>{{ $inr($a['amount']) }}</b></div>
              <div class="tx-track"><i style="width: {{ round($a['amount'] / $audMax * 100) }}%; background: linear-gradient(90deg,#93c5fd,#2563eb)"></i></div>
            </div>
          @empty
            <div class="tx-empty">No paid orders yet.</div>
          @endforelse
        </div>
      </div>
    </section>

    <div class="card" style="padding:20px;">
      <form method="GET" action="{{ route('payment-transactions.index') }}" class="tx-filters">
        <div class="search-container">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input class="search-input" name="search" value="{{ $filters['search'] }}" placeholder="Order ID, payment ID, invoice, user...">
        </div>
        <select class="form-control" name="status" onchange="this.form.submit()">
          <option value="">Status: All</option>
          @foreach ($statuses as $s)<option value="{{ $s }}" @selected($filters['status'] === $s)>Status: {{ ucfirst($s) }}</option>@endforeach
        </select>
        <select class="form-control" name="plan" onchange="this.form.submit()">
          <option value="">Plan: All</option>
          @foreach ($planKeys as $key => $name)<option value="{{ $key }}" @selected($filters['plan'] === $key)>{{ $name }}</option>@endforeach
        </select>
        <select class="form-control" name="billing" onchange="this.form.submit()">
          <option value="">Billing: All</option>
          <option value="monthly" @selected($filters['billing'] === 'monthly')>Monthly</option>
          <option value="yearly" @selected($filters['billing'] === 'yearly')>Yearly</option>
        </select>
        <select class="form-control" name="audience" onchange="this.form.submit()">
          <option value="">Audience: All</option>
          @foreach ($audiences as $k => $label)<option value="{{ $k }}" @selected($filters['audience'] === $k)>{{ $label }}</option>@endforeach
        </select>
        <input class="form-control" type="date" name="from" value="{{ $filters['from'] }}" title="From">
        <input class="form-control" type="date" name="to" value="{{ $filters['to'] }}" title="To">
        <button class="btn-secondary">Apply</button>
        @if (array_filter($filters))<a href="{{ route('payment-transactions.index') }}" class="btn-ghost" style="text-decoration:none">Clear</a>@endif
      </form>

      <div class="table-responsive">
        <table class="custom-table">
          <thead>
            <tr><th>ID</th><th>DATE</th><th>PAYER</th><th>PLAN</th><th>AMOUNT</th><th>STATUS</th><th>INVOICE</th><th></th></tr>
          </thead>
          <tbody>
            @forelse ($rows as $t)
              @php $payer = $t->payer(); $plan = $t->plan(); @endphp
              <tr>
                <td><strong>#{{ $t->id }}</strong></td>
                <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d M Y') }}<span class="tx-sub">{{ \Carbon\Carbon::parse($t->created_at)->format('g:i A') }}</span></td>
                <td>{{ $payer['name'] }}@if ($payer['email'])<span class="tx-sub">{{ $payer['email'] }}</span>@endif</td>
                <td>{{ $t->plan_name }}<span class="tx-sub">{{ ucfirst($t->billing_period) }}{{ $plan ? ' · '.$plan->audienceLabel() : '' }}</span></td>
                <td><span class="tx-amount">{{ $inr($t->rupees()) }}</span><span class="tx-sub">{{ $inr($t->rupees('base_amount')) }} + {{ $inr($t->rupees('tax_amount')) }} GST</span></td>
                <td><span class="badge {{ $statusBadge[$t->status] ?? 'badge-pending' }}">{{ ucfirst($t->status) }}</span>@if ($t->failure_reason)<span class="tx-sub">{{ $t->failure_reason }}</span>@endif</td>
                <td>
                  @if ($t->invoice_number)
                    @if ($t->invoiceUrl())<a href="{{ $t->invoiceUrl() }}" target="_blank" rel="noopener">{{ $t->invoice_number }}</a>@else{{ $t->invoice_number }}@endif
                  @else
                    <span class="tx-sub">—</span>
                  @endif
                </td>
                <td>
                  <button type="button" class="btn-ghost tx-view" style="height:34px;padding:0 12px"
                    data-tx="{{ json_encode([
                      'ID' => '#'.$t->id, 'Status' => ucfirst($t->status), 'Payer' => $payer['name'].($payer['email'] ? ' ('.$payer['email'].')' : '').' · user #'.$t->user_id,
                      'Plan' => $t->plan_name.' · '.ucfirst($t->billing_period).($plan ? ' · '.$plan->audienceLabel() : ''),
                      'Base amount' => $inr($t->rupees('base_amount')), 'GST' => $inr($t->rupees('tax_amount')), 'Total' => $inr($t->rupees()).' '.$t->currency,
                      'Gateway' => ucfirst($t->gateway), 'Order ID' => $t->gateway_order_id ?: '—', 'Payment ID' => $t->gateway_payment_id ?: '—',
                      'Created' => \Carbon\Carbon::parse($t->created_at)->format('d M Y, g:i A'), 'Paid at' => $t->paid_at?->format('d M Y, g:i A') ?? '—',
                      'Invoice' => $t->invoice_number ?: '—', 'Invoice file' => $t->invoice ?: '—', 'Failure reason' => $t->failure_reason ?: '—',
                    ]) }}">View</button>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text-muted)">No transactions match these filters.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @include('partials.pagination', ['paginator' => $rows])
    </div>
  </main>
</div>

<div class="modal-backdrop" id="txModal">
  <div class="modal-box" style="max-width:620px">
    <div class="modal-header"><h3 class="modal-title">Transaction details</h3><button type="button" class="modal-close-btn" id="txClose">×</button></div>
    <div class="modal-body"><dl class="tx-dl" id="txBody"></dl></div>
    <div class="modal-footer"><button type="button" class="btn-secondary" id="txOk">Close</button></div>
  </div>
</div>
<script>
  const txModal = document.getElementById('txModal'), txBody = document.getElementById('txBody');
  document.querySelectorAll('.tx-view').forEach((btn) => btn.addEventListener('click', () => {
    const data = JSON.parse(btn.dataset.tx);
    txBody.innerHTML = '';
    Object.entries(data).forEach(([k, v]) => {
      const dt = document.createElement('dt'); dt.textContent = k;
      const dd = document.createElement('dd'); dd.textContent = v;
      txBody.append(dt, dd);
    });
    txModal.classList.add('open');
  }));
  const close = () => txModal.classList.remove('open');
  document.getElementById('txClose').onclick = close;
  document.getElementById('txOk').onclick = close;
  txModal.onclick = (e) => { if (e.target === txModal) close(); };
</script>
</body>
</html>
