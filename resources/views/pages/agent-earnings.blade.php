@php
  $inr = fn ($v) => ($v < 0 ? '-' : '').'₹'.number_format(abs((int) $v));
  $cBadge = ['pending' => 'badge-pending', 'received' => 'badge-approved'];
  $pBadge = ['requested' => 'badge-pending', 'processing' => 'badge-negotiating', 'paid' => 'badge-approved', 'rejected' => 'badge-rejected'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agent Commissions - CMNHousing Admin Portal</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .ae-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 8px 0 24px; }
    .ae-stats .stat-card { min-height: 124px; }
    .stat-note { margin-top: 10px; color: var(--text-muted); font-size: 12.5px; font-weight: 600; }
    .ae-tabs { display: flex; gap: 8px; margin-bottom: 16px; }
    .ae-tab { padding: 9px 16px; border-radius: 999px; background: #eef2f6; color: #5c6b80; text-decoration: none; font-size: 13.5px; font-weight: 600; }
    .ae-tab.active { background: #2563eb; color: #fff; box-shadow: 0 8px 16px rgba(37,99,235,.22); }
    .ae-filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
    .ae-filters .form-control { width: auto; min-width: 170px; height: 42px; }
    .ae-sub { display: block; color: var(--text-muted); font-size: 12px; margin-top: 2px; }
    .ae-amount { font-weight: 800; color: #122033; white-space: nowrap; }
    .ae-amount.neg { color: #e11d48; }
    .ae-actions { display: flex; gap: 6px; justify-content: flex-end; }
    .ae-actions .btn-ghost, .ae-actions .btn-secondary { height: 34px; padding: 0 12px; font-size: 13px; white-space: nowrap; }
    .ae-form { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .ae-form .full { grid-column: 1 / -1; }
    .ae-notice { padding: 14px 18px; margin-bottom: 16px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; font-size: 13.5px; }
    .ae-error { padding: 14px 18px; margin: 0 0 16px; color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; }
    @media (max-width: 1100px) { .ae-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 640px) { .ae-stats, .ae-form { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<div class="app-container">
  @include('layouts.navigation')
  <main class="page-content">
    <div class="page-header-actions">
      <div>
        <h1 class="page-title">Agent Commissions</h1>
        <p class="page-subtitle">Record agent commissions, confirm received payments and pay out agent payout requests.</p>
      </div>
      @if ($available)<button class="btn-primary" type="button" id="openCommission">+ Add Commission</button>@endif
    </div>

    @if (session('success'))<div class="card" role="status" style="padding:14px 18px;margin:16px 0;color:var(--accent-emerald)">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="ae-error">{{ $errors->first() }}</div>@endif

    @unless ($available)
      <div class="ae-notice">The Agent database isn't reachable. Run <code>php artisan migrate</code> in CMN-Agent and set <code>AGENT_DB_DATABASE</code> (or <code>AGENT_DB_HOST</code> for MySQL) in <code>.env</code>.</div>
    @endunless

    <section class="stats-grid ae-stats">
      <div class="stat-card">
        <div class="stat-header"><span class="stat-title">COMMISSION PENDING</span></div>
        <div class="stat-value">{{ $inr($stats['pending']) }}</div>
        <div class="stat-note">Waiting for payment from builder / owner</div>
      </div>
      <div class="stat-card">
        <div class="stat-header"><span class="stat-title">COMMISSION RECEIVED</span></div>
        <div class="stat-value">{{ $inr($stats['received']) }}</div>
        <div class="stat-note">Counts towards agent payout balance</div>
      </div>
      <div class="stat-card">
        <div class="stat-header"><span class="stat-title">PAYOUTS TO PROCESS</span></div>
        <div class="stat-value">{{ $inr($stats['requested']) }}</div>
        <div class="stat-note">{{ $stats['requested_count'] }} open request{{ $stats['requested_count'] === 1 ? '' : 's' }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-header"><span class="stat-title">PAID TO AGENTS</span></div>
        <div class="stat-value">{{ $inr($stats['paid']) }}</div>
        <div class="stat-note">All completed payouts</div>
      </div>
    </section>

    <nav class="ae-tabs">
      <a class="ae-tab {{ $tab === 'commissions' ? 'active' : '' }}" href="{{ route('agent-earnings.index') }}">Commissions</a>
      <a class="ae-tab {{ $tab === 'payouts' ? 'active' : '' }}" href="{{ route('agent-earnings.index', ['tab' => 'payouts']) }}">Payout Requests @if ($stats['requested_count'])({{ $stats['requested_count'] }})@endif</a>
    </nav>

    <div class="card" style="padding:20px;">
      <form method="GET" action="{{ route('agent-earnings.index') }}" class="ae-filters">
        @if ($tab === 'payouts')<input type="hidden" name="tab" value="payouts">@endif
        <select class="form-control" name="agent" onchange="this.form.submit()">
          <option value="0">All agents</option>
          @foreach ($agents as $a)<option value="{{ $a->id }}" @selected($filters['agent'] === $a->id)>{{ $a->name }}</option>@endforeach
        </select>
        <select class="form-control" name="status" onchange="this.form.submit()">
          <option value="">All status</option>
          @foreach (($tab === 'payouts' ? ['requested', 'processing', 'paid', 'rejected'] : ['pending', 'received']) as $s)
            <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
        @if ($tab === 'commissions')
          <select class="form-control" name="type" onchange="this.form.submit()">
            <option value="">All deal types</option>
            @foreach ($dealTypes as $k => $l)<option value="{{ $k }}" @selected($filters['type'] === $k)>{{ $l }}</option>@endforeach
          </select>
        @endif
      </form>

      <div class="table-responsive">
        @if ($tab === 'commissions')
          <table class="custom-table">
            <thead><tr><th>DEAL</th><th>AGENT</th><th>PROPERTY</th><th>CLIENT</th><th>TYPE</th><th>AMOUNT</th><th>STATUS</th><th>DATE</th><th></th></tr></thead>
            <tbody>
              @forelse ($commissions ?? [] as $c)
                <tr>
                  <td><strong>#{{ ltrim($c->deal_code, '#') }}</strong></td>
                  <td>{{ $c->agent?->name ?? 'Agent #'.$c->agent_id }}</td>
                  <td>{{ $c->property_title }}<span class="ae-sub">{{ $c->property_location }}</span></td>
                  <td>{{ $c->client_name }}</td>
                  <td>{{ $c->entry_type === 'adjustment' ? 'Adjustment' : ($dealTypes[$c->deal_type] ?? ucfirst($c->deal_type)) }}@if ($c->deal_value)<span class="ae-sub">Deal {{ $inr($c->deal_value) }}</span>@endif</td>
                  <td><span class="ae-amount {{ $c->amount < 0 ? 'neg' : '' }}">{{ $inr($c->amount) }}</span></td>
                  <td><span class="badge {{ $cBadge[$c->status] ?? 'badge-pending' }}">{{ ucfirst($c->status) }}</span>@if ($c->payout_id)<span class="ae-sub">Paid out</span>@endif</td>
                  <td style="white-space:nowrap">{{ $c->earned_at?->format('d M Y') }}@if ($c->received_at)<span class="ae-sub">Recv {{ $c->received_at->format('d M') }}</span>@endif</td>
                  <td>
                    <div class="ae-actions">
                      @if ($c->status === 'pending')
                        <form method="POST" action="{{ route('agent-earnings.commissions.received', $c->id) }}" data-confirm="Mark commission #{{ $c->deal_code }} as received?" data-confirm-title="Mark received" data-confirm-label="Mark received">@csrf @method('PATCH')
                          <button class="btn-ghost">Mark Received</button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text-muted)">No commissions yet. Use “+ Add Commission”, or close a Smart Bargain deal in the Agent portal.</td></tr>
              @endforelse
            </tbody>
          </table>
          @if ($commissions)@include('partials.pagination', ['paginator' => $commissions])@endif
        @else
          <table class="custom-table">
            <thead><tr><th>PAYOUT</th><th>AGENT</th><th>AMOUNT</th><th>REQUESTED</th><th>STATUS</th><th>BANK REF</th><th></th></tr></thead>
            <tbody>
              @forelse ($payouts ?? [] as $p)
                <tr>
                  <td><strong>{{ $p->payout_code }}</strong></td>
                  <td>{{ $p->agent?->name ?? 'Agent #'.$p->agent_id }}<span class="ae-sub">{{ $p->agent?->email }}</span></td>
                  <td><span class="ae-amount">{{ $inr($p->amount) }}</span></td>
                  <td style="white-space:nowrap">{{ $p->requested_at?->format('d M Y, g:i A') }}</td>
                  <td><span class="badge {{ $pBadge[$p->status] ?? 'badge-pending' }}">{{ ucfirst($p->status) }}</span>@if ($p->paid_at)<span class="ae-sub">Paid {{ $p->paid_at->format('d M Y') }}</span>@endif</td>
                  <td>{{ $p->bank_reference ?: '—' }}</td>
                  <td>
                    <div class="ae-actions">
                      @if ($p->status === 'requested')
                        <form method="POST" action="{{ route('agent-earnings.payouts.process', $p->id) }}">@csrf @method('PATCH')<button class="btn-ghost">Processing</button></form>
                      @endif
                      @if (in_array($p->status, ['requested', 'processing'], true))
                        <button type="button" class="btn-secondary pay-btn" data-id="{{ $p->id }}" data-code="{{ $p->payout_code }}" data-amount="{{ $inr($p->amount) }}" data-agent="{{ $p->agent?->name }}">Mark Paid</button>
                        <form method="POST" action="{{ route('agent-earnings.payouts.reject', $p->id) }}" data-confirm="Reject payout {{ $p->payout_code }}? The amount goes back to the agent's balance." data-confirm-title="Reject payout" data-confirm-label="Reject">@csrf @method('PATCH')<button class="btn-ghost">Reject</button></form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted)">No payout requests. Agents request payouts from their Earnings page.</td></tr>
              @endforelse
            </tbody>
          </table>
          @if ($payouts)@include('partials.pagination', ['paginator' => $payouts])@endif
        @endif
      </div>
    </div>
  </main>
</div>

@if ($available)
<div class="modal-backdrop" id="commissionModal">
  <div class="modal-box" style="max-width:720px">
    <div class="modal-header"><h3 class="modal-title">Add Commission</h3><button type="button" class="modal-close-btn" data-close>×</button></div>
    <form method="POST" action="{{ route('agent-earnings.commissions.store') }}">
      @csrf
      <div class="modal-body">
        <div class="ae-form">
          <div><label class="form-label">Agent *</label>
            <select class="form-control" name="agent_id" required>
              <option value="">Select agent</option>
              @foreach ($agents as $a)<option value="{{ $a->id }}" @selected(old('agent_id') == $a->id)>{{ $a->name }}</option>@endforeach
            </select></div>
          <div><label class="form-label">Entry *</label>
            <select class="form-control" name="entry_type"><option value="commission">Commission</option><option value="adjustment">Adjustment (bonus / deduction)</option></select></div>
          <div><label class="form-label">Deal type *</label>
            <select class="form-control" name="deal_type"><option value="sale">Sale</option><option value="rent">Rent</option><option value="other">Other service</option></select></div>
          <div><label class="form-label">Deal ID *</label><input class="form-control" name="deal_code" maxlength="20" required placeholder="e.g. D016" value="{{ old('deal_code') }}"></div>
          <div class="full"><label class="form-label">Property *</label><input class="form-control" name="property_title" required value="{{ old('property_title') }}"></div>
          <div><label class="form-label">Location</label><input class="form-control" name="property_location" placeholder="Area, City" value="{{ old('property_location') }}"></div>
          <div><label class="form-label">Client *</label><input class="form-control" name="client_name" required value="{{ old('client_name') }}"></div>
          <div><label class="form-label">Deal value (₹)</label><input class="form-control" type="number" name="deal_value" min="0" value="{{ old('deal_value') }}"></div>
          <div><label class="form-label">Commission (₹) *</label><input class="form-control" type="number" name="amount" required value="{{ old('amount') }}" placeholder="Negative for a deduction"></div>
          <div><label class="form-label">Status *</label>
            <select class="form-control" name="status"><option value="pending">Pending (not yet paid to CMNHousing)</option><option value="received">Received</option></select></div>
          <div><label class="form-label">Earned on *</label><input class="form-control" type="date" name="earned_at" required value="{{ old('earned_at', now()->toDateString()) }}"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn-secondary" data-close>Cancel</button><button class="btn-primary">Save Commission</button></div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="payModal">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-header"><h3 class="modal-title">Mark payout paid</h3><button type="button" class="modal-close-btn" data-close>×</button></div>
    <form method="POST" id="payForm">
      @csrf @method('PATCH')
      <div class="modal-body">
        <p class="page-subtitle" id="payText" style="margin-top:0"></p>
        <label class="form-label">Bank reference (UTR) *</label>
        <input class="form-control" name="bank_reference" required maxlength="80" placeholder="e.g. UTR2026111412345">
      </div>
      <div class="modal-footer"><button type="button" class="btn-secondary" data-close>Cancel</button><button class="btn-primary">Mark Paid</button></div>
    </form>
  </div>
</div>
<script>
  const open = (id) => document.getElementById(id).classList.add('open');
  document.querySelectorAll('.modal-backdrop').forEach((m) => {
    m.addEventListener('click', (e) => { if (e.target === m || e.target.closest('[data-close]')) m.classList.remove('open'); });
  });
  document.getElementById('openCommission').onclick = () => open('commissionModal');
  document.querySelectorAll('.pay-btn').forEach((b) => b.onclick = () => {
    document.getElementById('payForm').action = `{{ url('agent-earnings/payouts') }}/${b.dataset.id}/paid`;
    document.getElementById('payText').textContent = `${b.dataset.code} · ${b.dataset.amount} to ${b.dataset.agent || 'agent'}`;
    open('payModal');
  });
  @if ($errors->any() && old('deal_code')) open('commissionModal'); @endif
</script>
@endif
@include('partials.confirm-modal')
</body>
</html>
