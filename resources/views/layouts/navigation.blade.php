    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
      <div class="brand-wrapper">
        <img class="brand-logo" src="{{ asset('images/cmn-housing-logo.png') }}" alt="CMN Housing">
      </div>

      <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" id="nav-dashboard">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"></rect>
            <rect x="14" y="3" width="7" height="7" rx="1"></rect>
            <rect x="14" y="14" width="7" height="7" rx="1"></rect>
            <rect x="3" y="14" width="7" height="7" rx="1"></rect>
          </svg>
          Dashboard
        </a>
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" id="nav-users">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
          Users
        </a>
        <a href="{{ route('properties.index') }}" class="nav-link {{ request()->routeIs('properties.*') ? 'active' : '' }}" id="nav-properties">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
            <path d="M9 22v-4h6v4"></path>
            <path d="M8 6h.01"></path>
            <path d="M16 6h.01"></path>
            <path d="M8 10h.01"></path>
            <path d="M16 10h.01"></path>
            <path d="M8 14h.01"></path>
            <path d="M16 14h.01"></path>
          </svg>
          Properties
          @php $opsReady = \App\Support\PropertyIndex::awaitingCount(); @endphp
          @if ($opsReady)<span title="{{ $opsReady }} awaiting your approval" style="margin-left:auto;background:var(--accent-red, #ef4444);color:#fff;border-radius:999px;font-size:11px;font-weight:700;padding:1px 7px;">{{ $opsReady }}</span>@endif
        </a>
        <a href="{{ route('deletion-requests.index') }}" class="nav-link {{ request()->routeIs('deletion-requests.*') ? 'active' : '' }}" id="nav-deletion-requests">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
            <path d="M10 11v6"></path>
            <path d="M14 11v6"></path>
            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
          </svg>
          Delete Requests
        </a>
        <a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" id="nav-audit-logs">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
          Audit Logs
        </a>
        <a href="{{ route('payments-plans.index') }}" class="nav-link {{ request()->routeIs('payments-plans.*') ? 'active' : '' }}" id="nav-payments-plans">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 10h18"></path><path d="M7 15h2"></path></svg>
          Payments &amp; Plans
        </a>
        <a href="{{ route('payment-transactions.index') }}" class="nav-link {{ request()->routeIs('payment-transactions.*') ? 'active' : '' }}" id="nav-payment-transactions">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4 4 4"></path><path d="M17 8v12m0 0 4-4m-4 4-4-4"></path></svg>
          Transactions
        </a>
        <a href="{{ route('agent-earnings.index') }}" class="nav-link {{ request()->routeIs('agent-earnings.*') ? 'active' : '' }}" id="nav-agent-earnings">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"></circle><path d="M3 21v-2a4 4 0 0 1 4-4h4"></path><path d="M17 12v8"></path><path d="M14 17l3 3 3-3"></path></svg>
          Agent Commissions
        </a>
        <a href="{{ route('leads.index') }}" class="nav-link {{ request()->routeIs('leads.index') ? 'active' : '' }}" id="nav-leads">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
            <polyline points="17 6 23 6 23 12"></polyline>
          </svg>
          Leads & Bargains
        </a>
        <a href="{{ route('complaints.index') }}" class="nav-link {{ request()->routeIs('complaints.*') ? 'active' : '' }}" id="nav-complaints">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          Complaints
        </a>
        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" id="nav-reports">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
          </svg>
          Reports
        </a>
        <a href="{{ route('blogs.index') }}" class="nav-link {{ request()->routeIs('blogs.*') ? 'active' : '' }}" id="nav-blogs">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            <line x1="8" y1="7" x2="16" y2="7"></line>
            <line x1="8" y1="11" x2="16" y2="11"></line>
          </svg>
          Blogs
        </a>
      </nav>

      <div class="sidebar-footer">
        <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}" id="nav-settings">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
          </svg>
          Settings
        </a>
      </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
      <!-- Top Header Navigation -->
      <header class="top-header">
        <div style="display: flex; align-items: center; gap: 12px;">
          <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle navigation menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>
          
          <form class="search-container" method="GET" action="{{ route('search') }}" role="search">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="search" class="search-input" id="globalSearch" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Search properties or users..." aria-label="Search properties or users">
          </form>
        </div>

        <div class="header-actions">
          @php
            // what needs Admin right now — each line links to where it's handled; Settings → Notifications switches lines off
            $me = auth()->user();
            $awaitingLine = $me->wantsAlert('property_approvals') && ($n = \App\Support\PropertyIndex::awaitingCount()) ? [$n.' '.\Illuminate\Support\Str::plural('property', $n).' waiting for your approval', route('properties.index', ['status' => 'pending'])] : null;
            $adminAlerts = array_values(array_filter([
                $awaitingLine,
                $me->wantsAlert('deletion_requests') && ($n = rescue(fn () => \App\Models\PropertyDeletionRequest::query()->where('status', \App\Models\PropertyDeletionRequest::STATUS_PENDING)->count(), 0, false)) ? [$n.' delete '.\Illuminate\Support\Str::plural('request', $n).' to review', route('deletion-requests.index')] : null,
                $me->wantsAlert('agent_payouts') && ($n = rescue(fn () => \App\Models\Agent\AgentPayout::query()->whereIn('status', ['requested', 'processing'])->count(), 0, false)) ? [$n.' agent '.\Illuminate\Support\Str::plural('payout', $n).' to process', route('agent-earnings.index')] : null,
                $me->wantsAlert('complaints') && ($n = rescue(fn () => \App\Models\Housing\SupportTicket::available() ? \App\Models\Housing\SupportTicket::query()->where('status', 'escalated')->count() : 0, 0, false)) ? [$n.' escalated '.\Illuminate\Support\Str::plural('complaint', $n), route('complaints.index', ['status' => 'escalated'])] : null,
            ]));
            // the "N properties waiting" line already covers Operations' hand-overs — don't list each one again
            $inbox = \App\Support\AdminInbox::bellFor($me, hideHandovers: $awaitingLine !== null);
            $bellCount = count($adminAlerts) + $inbox['unread'];
          @endphp
          <div class="notify-wrapper" id="notifyWrapper">
            <button class="action-btn" type="button" id="notifyTrigger" aria-label="Notifications{{ $bellCount ? ', '.$bellCount.' need attention' : '' }}" title="Notifications" aria-expanded="false">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
              @if ($bellCount)<span class="notification-badge-dot"></span>@endif
            </button>
            <div class="notify-menu" id="notifyMenu" role="menu">
              <div class="profile-dropdown-section">Needs your attention</div>
              @foreach ($adminAlerts as [$text, $href])
                <a class="notify-item" href="{{ $href }}" role="menuitem">{{ $text }}</a>
              @endforeach
              @foreach ($inbox['items'] as $n)
                <a class="notify-item notify-unread" href="{{ route('notifications.open', $n->id) }}" role="menuitem">
                  <strong>{{ $n->title }}</strong>
                  @if ($n->body)<span class="notify-body">{{ \Illuminate\Support\Str::limit($n->body, 90) }}</span>@endif
                  <span class="notify-when">{{ $n->when() }}</span>
                </a>
              @endforeach
              @if (! $adminAlerts && $inbox['items']->isEmpty())
                <p class="notify-empty">You’re all caught up.</p>
              @endif
              <div class="notify-foot">
                <a href="{{ route('notifications.index') }}">All notifications{{ $inbox['unread'] ? ' ('.$inbox['unread'].' unread)' : '' }}</a>
                @if ($inbox['unread'])
                  <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button type="submit">Mark all read</button></form>
                @endif
              </div>
              <a class="notify-item notify-settings" href="{{ route('settings.index') }}#notifications" role="menuitem">Choose which alerts you see</a>
            </div>
          </div>

          <div class="profile-dropdown-wrapper" id="profileDropdownWrapper">
            <div class="user-profile-btn" style="cursor: pointer;" id="profileDropdownTrigger" role="button" tabindex="0" aria-haspopup="menu" aria-expanded="false">
              @if (auth()->user()->profilePhotoUrl())
                <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" class="user-avatar user-avatar-image">
              @else
                <div class="user-avatar">{{ auth()->user()->initials() }}</div>
              @endif
              <div class="user-info">
                <div class="user-name">
                  {{ auth()->user()->name }}
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </div>
                <div class="user-role">{{ auth()->user()->admin_role ? ucwords(auth()->user()->admin_role) : 'Admin' }}</div>
              </div>
            </div>
            <div class="profile-dropdown-menu" id="profileDropdownMenu">
              <div class="profile-dropdown-section">My Account</div>
              <a class="profile-dropdown-item" href="{{ route('profile.edit') }}" style="text-decoration:none">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Profile
              </a>
              <a class="profile-dropdown-item" href="{{ route('settings.index') }}" style="text-decoration:none">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Settings
              </a>
              <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="profile-dropdown-item logout">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Log out
              </button></form>
            </div>
          </div>
        </div>
      </header>
      <style>
        .notify-wrapper{position:relative}
        .notify-menu{display:none;position:absolute;right:0;top:calc(100% + 8px);width:300px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 16px 40px rgba(15,23,42,.14);padding:8px;z-index:60}
        .notify-menu.open{display:block}
        .notify-item{display:block;padding:10px 12px;border-radius:8px;font-size:13.5px;color:var(--text-primary,#0f172a);text-decoration:none}
        .notify-item:hover{background:#f1f5f9}
        .notify-settings{border-top:1px solid #e2e8f0;border-radius:0 0 8px 8px;margin-top:4px;font-size:12.5px;color:var(--text-muted,#64748b)}
        .notify-unread strong{display:block;font-weight:700}
        .notify-body{display:block;color:var(--text-muted,#64748b);font-size:12.5px;margin-top:2px}
        .notify-when{display:block;color:var(--text-muted,#64748b);font-size:11.5px;margin-top:2px}
        .notify-menu{max-height:70vh;overflow:auto}
        .notify-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 12px 4px;border-top:1px solid #e2e8f0;margin-top:4px;font-size:12.5px}
        .notify-foot a{color:var(--text-primary,#0f172a);font-weight:600;text-decoration:none}
        .notify-foot button{background:none;border:0;padding:0;color:#0284c7;font-weight:600;cursor:pointer;font-size:12.5px}
        .notify-empty{margin:0;padding:10px 12px;font-size:13.5px;color:var(--text-muted,#64748b)}
      </style>
      <script>
        // Top bar for every Admin page: profile menu, notifications, mobile sidebar.
        // Runs first and stops older per-page copies of the same handlers so nothing toggles twice.
        (function () {
          const pairs = [['profileDropdownTrigger', 'profileDropdownMenu', '.profile-dropdown-wrapper'], ['notifyTrigger', 'notifyMenu', '.notify-wrapper']];
          const closeAll = (except) => pairs.forEach(([, m]) => { if (m !== except) document.getElementById(m)?.classList.remove('open'); });
          pairs.forEach(([t, m]) => {
            const trigger = document.getElementById(t), menu = document.getElementById(m);
            if (!trigger || !menu) return;
            trigger.addEventListener('click', (e) => {
              e.stopImmediatePropagation(); e.stopPropagation();
              closeAll(m);
              menu.classList.toggle('open');
              trigger.setAttribute('aria-expanded', menu.classList.contains('open'));
            });
          });
          document.addEventListener('click', (e) => { if (!e.target.closest('.profile-dropdown-wrapper, .notify-wrapper')) closeAll(); });
          document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });
          document.getElementById('profileDropdownTrigger')?.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); e.currentTarget.click(); } });

          const btn = document.getElementById('mobileMenuBtn'), sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay');
          if (btn && sidebar && overlay) {
            btn.addEventListener('click', (e) => { e.stopImmediatePropagation(); sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
            overlay.addEventListener('click', (e) => { e.stopImmediatePropagation(); sidebar.classList.remove('open'); overlay.classList.remove('active'); });
          }
        })();
      </script>

