<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - CMNHousing Admin Portal</title>
  <meta name="description" content="Your admin account, password, alerts and team in CMNHousing.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .settings-nav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
    .settings-nav a{padding:7px 14px;border-radius:999px;border:1px solid var(--border-light,#e2e8f0);background:var(--bg-card,#fff);font-size:13px;font-weight:600;color:var(--text-secondary,#475569);text-decoration:none}
    .settings-nav a:hover{color:var(--text-primary,#0f172a);border-color:#cbd5e1}
    .settings-card{scroll-margin-top:90px}
    .settings-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0 16px}
    .settings-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:8px}
    .settings-note{font-size:12.5px;color:var(--text-muted);margin:12px 0 0}
    .team-you{font-size:11px;font-weight:700;color:var(--text-muted);margin-left:6px}
    .team-add{margin-top:20px;padding-top:20px;border-top:1px solid #f1f5f9}
    .team-add summary{cursor:pointer;font-weight:700;font-size:14px;color:var(--text-primary)}
  </style>
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')

      <main class="page-content">
        <div class="page-header">
          <h1 class="page-title">Settings</h1>
          <p class="page-subtitle">Your account, password, alerts and the admin team</p>
        </div>

        @if (session('success'))
          <div class="card" role="status" style="padding:14px 18px; margin-bottom:16px; color:var(--accent-emerald);">{{ session('success') }}</div>
        @endif
        @if ($errors->has('team') || $errors->has('alerts') || $errors->has('alerts.*'))
          <div class="card" role="alert" style="padding:14px 18px; margin-bottom:16px; color:var(--accent-red);">{{ $errors->first('team') ?: ($errors->first('alerts') ?: $errors->first('alerts.*')) }}</div>
        @endif

        <nav class="settings-nav" aria-label="Settings sections">
          <a href="#profile">Profile</a>
          <a href="#password">Password</a>
          <a href="#notifications">Notifications</a>
          <a href="#team">Team</a>
        </nav>

        <div class="settings-grid" style="padding-bottom:48px;">
          {{-- Profile: saved by ProfileController, which returns here --}}
          <section class="settings-card" id="profile">
            <div class="settings-card-header">
              <div class="settings-icon-circle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              </div>
              <div>
                <h2 class="settings-card-title">Profile</h2>
                <span class="settings-card-subtitle">Name, email and mobile you sign in with</span>
              </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="profile-form">
              @csrf
              @method('PUT')
              <div class="settings-form-grid">
                <div class="form-group">
                  <label class="form-label" for="settingsName">Full name</label>
                  <input type="text" class="form-control" id="settingsName" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>
                  @error('name')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                  <label class="form-label" for="settingsEmail">Email</label>
                  <input type="email" class="form-control" id="settingsEmail" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required>
                  @error('email')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                  <label class="form-label" for="settingsMobile">Mobile</label>
                  <input type="tel" class="form-control" id="settingsMobile" name="mobile" value="{{ old('mobile', $user->mobile) }}" maxlength="32" autocomplete="tel">
                  @error('mobile')<small class="field-error">{{ $message }}</small>@enderror
                </div>
              </div>
              <div class="settings-actions">
                <a href="{{ route('profile.edit') }}" class="btn-ghost" style="text-decoration:none;">Change photo</a>
                <button type="submit" class="btn-primary">Save profile</button>
              </div>
            </form>
          </section>

          {{-- Password --}}
          <section class="settings-card" id="password">
            <div class="settings-card-header">
              <div class="settings-icon-circle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              </div>
              <div>
                <h2 class="settings-card-title">Change password</h2>
                <span class="settings-card-subtitle">At least 8 characters</span>
              </div>
            </div>

            <form method="POST" action="{{ route('profile.password.update') }}" class="profile-form">
              @csrf
              @method('PUT')
              <div class="settings-form-grid">
                <div class="form-group">
                  <label class="form-label" for="settingsCurrentPassword">Current password</label>
                  <input type="password" class="form-control" id="settingsCurrentPassword" name="current_password" autocomplete="current-password" required>
                  @error('current_password')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                  <label class="form-label" for="settingsNewPassword">New password</label>
                  <input type="password" class="form-control" id="settingsNewPassword" name="password" autocomplete="new-password" minlength="8" required>
                  @error('password')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                  <label class="form-label" for="settingsConfirmPassword">Confirm new password</label>
                  <input type="password" class="form-control" id="settingsConfirmPassword" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                </div>
              </div>
              <div class="settings-actions">
                <button type="submit" class="btn-primary">Update password</button>
              </div>
            </form>
          </section>

          {{-- Notifications --}}
          <section class="settings-card" id="notifications">
            <div class="settings-card-header">
              <div class="settings-icon-circle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              </div>
              <div>
                <h2 class="settings-card-title">Notifications</h2>
                <span class="settings-card-subtitle">Choose what shows up under the bell in the top bar</span>
              </div>
            </div>

            <form method="POST" action="{{ route('settings.notifications.update') }}">
              @csrf
              @method('PUT')
              @foreach ($alerts as $key => [$label, $desc])
                <div class="setting-row">
                  <div class="setting-info">
                    <span class="setting-label" id="alert-{{ $key }}-label">{{ $label }}</span>
                    <span class="setting-desc">{{ $desc }}</span>
                  </div>
                  <label class="switch">
                    <input type="checkbox" name="alerts[]" value="{{ $key }}" aria-labelledby="alert-{{ $key }}-label" @checked($user->wantsAlert($key))>
                    <span class="slider"></span>
                  </label>
                </div>
              @endforeach
              <p class="settings-note">These are in-portal alerts. The Admin portal doesn’t email admins about these events yet.</p>
              <div class="settings-actions">
                <button type="submit" class="btn-primary">Save preferences</button>
              </div>
            </form>
          </section>

          {{-- Team: everyone with the "admin" or "super admin" organizational role can sign in here; super admins manage it --}}
          <section class="settings-card" id="team">
            <div class="settings-card-header">
              <div class="settings-icon-circle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
              </div>
              <div>
                <h2 class="settings-card-title">Team</h2>
                <span class="settings-card-subtitle">Admins who can sign in to this portal{{ $canManageTeam ? '' : ' · managed by super admins' }}</span>
              </div>
            </div>

            <div class="table-responsive">
              <table class="custom-table">
                <thead>
                  <tr><th>NAME</th><th>ROLE</th><th>EMAIL</th><th>MOBILE</th><th>STATUS</th><th style="text-align:right;">ACTION</th></tr>
                </thead>
                <tbody>
                  @foreach ($team as $member)
                    <tr>
                      <td>{{ $member->name }}@if ($member->is($user))<span class="team-you">(you)</span>@endif</td>
                      <td>{{ $member->isSuperAdmin() ? 'Super Admin' : 'Admin' }}</td>
                      <td>{{ $member->email }}</td>
                      <td>{{ $member->mobile ?: '—' }}</td>
                      <td><span class="badge {{ $member->isActive() ? 'badge-approved' : 'badge-rejected' }}">{{ $member->isActive() ? 'Active' : 'Deactivated' }}</span></td>
                      <td style="text-align:right;">
                        @unless ($member->is($user) || ! $canManageTeam)
                          <form method="POST" action="{{ route('settings.team.toggle', $member) }}" style="display:inline;"
                            data-confirm="{{ $member->isActive() ? "Deactivate {$member->name}? They will be signed out and can’t sign in until reactivated." : "Reactivate {$member->name}? They will be able to sign in again." }}"
                            data-confirm-title="{{ $member->isActive() ? 'Deactivate admin' : 'Reactivate admin' }}"
                            data-confirm-label="{{ $member->isActive() ? 'Deactivate' : 'Reactivate' }}"
                            data-confirm-danger="{{ $member->isActive() ? 'true' : 'false' }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-ghost" @if ($member->isActive()) style="color:var(--accent-red)" @endif>{{ $member->isActive() ? 'Deactivate' : 'Reactivate' }}</button>
                          </form>
                        @endunless
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            @if (! $canManageTeam)
              <p class="settings-note" style="margin-top:16px">Only a super admin can add, deactivate or reactivate admins.</p>
            @else
            <details class="team-add" @if ($errors->team->any()) open @endif>
              <summary>Add an admin</summary>
              <form method="POST" action="{{ route('settings.team.store') }}" style="margin-top:16px;">
                @csrf
                <div class="settings-form-grid">
                  <div class="form-group">
                    <label class="form-label" for="teamName">Full name</label>
                    <input type="text" class="form-control" id="teamName" name="name" value="{{ $errors->team->any() ? old('name') : '' }}" maxlength="255" required>
                    @error('name', 'team')<small class="field-error">{{ $message }}</small>@enderror
                  </div>
                  <div class="form-group">
                    <label class="form-label" for="teamEmail">Email</label>
                    <input type="email" class="form-control" id="teamEmail" name="email" value="{{ $errors->team->any() ? old('email') : '' }}" maxlength="255" required>
                    @error('email', 'team')<small class="field-error">{{ $message }}</small>@enderror
                  </div>
                  <div class="form-group">
                    <label class="form-label" for="teamMobile">Mobile (optional)</label>
                    <input type="tel" class="form-control" id="teamMobile" name="mobile" value="{{ $errors->team->any() ? old('mobile') : '' }}" maxlength="32">
                    @error('mobile', 'team')<small class="field-error">{{ $message }}</small>@enderror
                  </div>
                  <div class="form-group">
                    <label class="form-label" for="teamPassword">Temporary password</label>
                    <input type="password" class="form-control" id="teamPassword" name="password" autocomplete="new-password" minlength="8" required>
                    @error('password', 'team')<small class="field-error">{{ $message }}</small>@enderror
                  </div>
                  <div class="form-group">
                    <label class="form-label" for="teamPasswordConfirm">Confirm password</label>
                    <input type="password" class="form-control" id="teamPasswordConfirm" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                  </div>
                </div>
                <p class="settings-note">Share the password with them directly; they can change it here after signing in, or use “Forgot password” on the sign-in page. Other roles (HR, employees, app users) are managed in <a href="{{ route('users.index') }}">Users</a>.</p>
                <div class="settings-actions">
                  <button type="submit" class="btn-primary">Add admin</button>
                </div>
              </form>
            </details>
            @endif
          </section>
        </div>
      </main>
    </div>
  </div>

  @include('partials.confirm-modal')
</body>
</html>
