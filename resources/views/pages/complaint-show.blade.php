<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $ticket->code }} · Complaints - CMNHousing Admin Portal</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .cs-crumb{font-size:13px;color:var(--text-muted);margin-bottom:10px}.cs-crumb a{color:inherit;text-decoration:none}.cs-crumb a:hover{color:#0284c7}
    .cs-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:20px;align-items:start}
    @media (max-width:980px){.cs-grid{grid-template-columns:1fr}}
    .cs-card{padding:20px}
    .cs-card h2{font-size:15px;margin:0 0 14px}
    .cs-thread{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:12px}
    .cs-msg{border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;background:#fff}
    .cs-msg.builder{background:#f8fafc}
    .cs-msg.team{border-color:#bae6fd;background:#f0f9ff}
    .cs-msg.note{border-style:dashed;border-color:#fcd34d;background:#fffbeb}
    .cs-msg-head{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--text-muted);margin-bottom:6px}
    .cs-msg-head strong{color:var(--text-primary,#0f172a)}
    .cs-msg-body{white-space:pre-wrap;font-size:14px;line-height:1.55;margin:0}
    .cs-event{font-size:12.5px;color:var(--text-muted);text-align:center;padding:2px 0}
    .cs-tag{font-size:10.5px;font-weight:700;text-transform:uppercase;padding:1px 7px;border-radius:999px;margin-left:6px}
    .cs-tag.note{background:#fef3c7;color:#92400e}
    .cs-facts{display:grid;grid-template-columns:auto 1fr;gap:8px 14px;font-size:13.5px;margin:0}
    .cs-facts dt{color:var(--text-muted)}.cs-facts dd{margin:0;font-weight:600}
    .cs-actions{display:flex;flex-direction:column;gap:8px;margin-top:16px}
    .cs-actions button{width:100%;justify-content:center}
    .status-badge-text.escalated{color:#b91c1c;background:#fef2f2}
    .cs-reply textarea{width:100%}
    .cs-reply-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:10px}
  </style>
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')
    <main class="page-content">
      <div class="cs-crumb"><a href="{{ route('complaints.index') }}">Complaints</a> / {{ $ticket->code }}</div>
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">{{ $ticket->subject }}</h1>
          <p class="page-subtitle">{{ $ticket->code }} · {{ $ticket->builder_name }} · {{ $ticket->categoryLabel() }}</p>
        </div>
        <span class="status-badge-text {{ $ticket->statusClass() }}" style="font-size:13px">{{ $ticket->statusLabel() }}</span>
      </div>

      @if (session('success'))
        <div class="card" role="status" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-emerald)">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="card" role="alert" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-red)">{{ $errors->first() }}</div>
      @endif

      <div class="cs-grid">
        <div>
          <section class="card cs-card" id="thread">
            <h2>Conversation</h2>
            <ul class="cs-thread">
              @foreach ($ticket->messages as $m)
                @if ($m->type === 'event')
                  <li class="cs-event">{{ $m->author ?: 'System' }} · {{ $m->body }} · {{ $m->created_at?->format('j M, g:i a') }}</li>
                @else
                  <li class="cs-msg {{ $m->type === 'note' ? 'note' : ($m->fromBuilder() ? 'builder' : 'team') }}">
                    <div class="cs-msg-head">
                      <span><strong>{{ $m->fromBuilder() ? ($m->author ?: $ticket->builder_name) : 'CMNHousing · '.($m->author ?: 'Team') }}</strong>
                        @if ($m->type === 'note')<span class="cs-tag note">Internal note</span>@endif
                        @if ($m->type === 'description')<span class="cs-tag" style="background:#e2e8f0;color:#475569">Request</span>@endif
                      </span>
                      <span>{{ $m->created_at?->format('j M Y, g:i a') }}</span>
                    </div>
                    <p class="cs-msg-body">{{ $m->body }}</p>
                    @if ($m->attachment_path)
                      <p style="margin:8px 0 0;font-size:13px">📎 <a href="{{ $m->attachmentUrl() }}" target="_blank" rel="noopener">{{ $m->attachment_name ?: 'Attachment' }}</a></p>
                    @endif
                  </li>
                @endif
              @endforeach
            </ul>
          </section>

          <section class="card cs-card cs-reply" style="margin-top:16px">
            <h2>Reply</h2>
            <form method="POST" action="{{ route('complaints.reply', $ticket) }}">
              @csrf
              <textarea class="form-control" name="body" rows="4" required minlength="2" maxlength="5000" placeholder="Write to {{ $ticket->builder_name }}...">{{ old('body') }}</textarea>
              <div class="cs-reply-row">
                <select class="form-control" name="type" style="max-width:260px">
                  <option value="reply">Reply to builder</option>
                  <option value="note" @selected(old('type') === 'note')>Internal note (team only)</option>
                </select>
                <select class="form-control" name="then" style="max-width:180px">
                  <option value="">Keep status</option>
                  <option value="resolved">Then mark resolved</option>
                  <option value="escalated">Then escalate</option>
                </select>
                <button class="btn-primary" type="submit">Send</button>
              </div>
            </form>
          </section>
        </div>

        <aside class="card cs-card">
          <h2>Details</h2>
          <dl class="cs-facts">
            <dt>Ticket</dt><dd>{{ $ticket->code }}</dd>
            <dt>From</dt><dd>{{ $ticket->builder_name ?: '—' }}</dd>
            <dt>Source</dt><dd>{{ $ticket->sourceLabel() }}</dd>
            <dt>Project</dt><dd>{{ $projectName ?: '—' }}</dd>
            <dt>Category</dt><dd>{{ $ticket->categoryLabel() }}</dd>
            <dt>Priority</dt><dd>{{ $ticket->priorityLabel() }}</dd>
            <dt>Handled by</dt><dd>{{ $ticket->assigned_to ?: 'Unassigned' }}</dd>
            <dt>Opened</dt><dd>{{ $ticket->created_at?->format('j M Y, g:i a') }}</dd>
            <dt>Respond by</dt><dd style="{{ $ticket->isOverdue() ? 'color:#b91c1c' : '' }}">{{ $ticket->due_at?->format('j M, g:i a') ?? '—' }}{{ $ticket->isOverdue() ? ' (overdue)' : '' }}</dd>
            <dt>First reply</dt><dd>{{ $ticket->first_response_at?->format('j M, g:i a') ?? 'Not yet' }}</dd>
            @if ($ticket->resolved_at)<dt>Resolved</dt><dd>{{ $ticket->resolved_at->format('j M, g:i a') }}</dd>@endif
          </dl>
          <div class="cs-actions">
            <button class="btn-dark" type="button" data-action="{{ route('complaints.update', $ticket) }}" data-title="{{ $ticket->code }}" data-status="{{ $ticket->status }}" data-priority="{{ $ticket->priority }}">Change status / priority</button>
            @if ($ticket->isOpen())
              <form method="POST" action="{{ route('complaints.update', $ticket) }}">@csrf @method('PATCH')
                <input type="hidden" name="status" value="resolved"><input type="hidden" name="priority" value="{{ $ticket->priority }}">
                <button class="btn-secondary" type="submit" style="width:100%">Mark resolved</button>
              </form>
            @else
              <form method="POST" action="{{ route('complaints.update', $ticket) }}">@csrf @method('PATCH')
                <input type="hidden" name="status" value="open"><input type="hidden" name="priority" value="{{ $ticket->priority }}">
                <button class="btn-secondary" type="submit" style="width:100%">Reopen</button>
              </form>
            @endif
          </div>
        </aside>
      </div>
    </main>
  </div>

  @include('partials.complaint-modals')
</body>
</html>
