<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management - CMNHousing Admin Portal</title>
  <meta name="description" content="Manage users, roles, and permissions in CMNHousing.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
  <div class="app-container">
    <!-- Sidebar Overlay for Mobile -->
    @include('layouts.navigation')

      <!-- Main Page Content -->
      <main class="page-content">
        <!-- Page Header with Action Button -->
        <div class="page-header-actions">
          <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Manage users, roles, and permissions</p>
          </div>
          <button class="btn-primary" id="openAddUserModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <line x1="19" y1="8" x2="19" y2="14"></line>
              <line x1="22" y1="11" x2="16" y2="11"></line>
            </svg>
            Add User
          </button>
        </div>

        @if (session('success'))
          <div class="card" role="status" style="padding:14px 18px; margin-bottom:16px; color:var(--accent-emerald);">{{ session('success') }}</div>
        @endif

        <div style="display:flex; gap:10px; margin-bottom:16px;" role="tablist" aria-label="User categories">
          <button type="button" class="btn-primary" id="employeesTab" role="tab" aria-selected="true">Employees &amp; Admins</button>
          <button type="button" class="btn-secondary" id="appUsersTab" role="tab" aria-selected="false">App Users</button>
        </div>

        <div class="card user-category" id="employeesPanel" role="tabpanel" style="padding:20px;">
          <h2 style="margin:0 0 16px;">Organizational users</h2>
          <!-- Filter Search Input -->
          <div style="margin-bottom: 20px; max-width: 420px;">
            <div class="search-container" style="width: 100%;">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" class="search-input" data-table-search="employeesTable" placeholder="Search employees and admins...">
            </div>
          </div>

          <!-- Responsive Table -->
          <div class="table-responsive">
            <table class="custom-table" id="employeesTable">
              <thead>
                <tr>
                  <th>USER</th>
                  <th>MOBILE</th>
                  <th>CITY</th>
                  <th>ADMIN ROLE</th>
                  <th>STATUS</th>
                  <th style="text-align:right;">ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($employees as $user)
                  <tr>
                    <td>
                      <div class="user-cell">
                        <div class="user-cell-avatar">{{ strtoupper(collect(explode(' ', $user->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('')) }}</div>
                        <div class="user-cell-details">
                          <span class="user-cell-name">{{ $user->name }}</span>
                          <span class="user-cell-email">{{ $user->email }}</span>
                        </div>
                      </div>
                    </td>
                    <td>{{ $user->mobile ?: '—' }}</td>
                    <td>{{ $user->city ?: '—' }}</td>
                    <td>{{ ucfirst($user->admin_role) }}</td>
                    <td><span class="badge {{ $user->isActive() ? 'badge-approved' : 'badge-rejected' }}">{{ $user->status ?: 'Active' }}</span></td>
                    <td style="text-align:right;">
                      <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                        <button class="btn-secondary" type="button" data-edit-user="{{ json_encode($user->only(['id','name','email','mobile','city','location','state','role','admin_role','status'])) }}" onclick="openEditUser(this)">Edit</button>
                        <form method="POST" action="{{ route('users.toggle-status', $user) }}">@csrf @method('PATCH')
                          <button class="btn-ghost" type="submit">{{ $user->isActive() ? 'Block' : 'Activate' }}</button>
                        </form>
                        @if ($user->id !== auth()->id())
                          <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="Delete this user permanently?" data-confirm-title="Delete user" data-confirm-label="Delete">@csrf @method('DELETE')
                            <button class="btn-ghost" style="color:var(--accent-red)" type="submit">Delete</button>
                          </form>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" style="text-align:center; padding:28px; color:var(--text-muted);">No organizational users found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @include('partials.pagination', ['paginator' => $employees])
        </div>

        <div class="card user-category" id="appUsersPanel" role="tabpanel" style="padding:20px; display:none;">
          <h2 style="margin:0 0 16px;">App users</h2>
          <div style="margin-bottom:20px; max-width:420px;">
            <div class="search-container" style="width:100%;">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input type="text" class="search-input" data-table-search="appUsersTable" placeholder="Search app users...">
            </div>
          </div>
          <div class="table-responsive">
            <table class="custom-table" id="appUsersTable">
              <thead>
                <tr>
                  <th>USER</th>
                  <th>MOBILE</th>
                  <th>CITY</th>
                  <th>ROLE</th>
                  <th>STATUS</th>
                  <th style="text-align:right;">ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($appUsers as $user)
                  <tr>
                    <td>
                      <div class="user-cell">
                        <div class="user-cell-avatar">{{ strtoupper(collect(explode(' ', $user->name))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('')) }}</div>
                        <div class="user-cell-details">
                          <span class="user-cell-name">{{ $user->name }}</span>
                          <span class="user-cell-email">{{ $user->email }}</span>
                        </div>
                      </div>
                    </td>
                    <td>{{ $user->mobile ?: '—' }}</td>
                    <td>{{ $user->city ?: '—' }}</td>
                    <td>{{ ucfirst($user->role) }}</td>
                    <td><span class="badge {{ $user->isActive() ? 'badge-approved' : 'badge-rejected' }}">{{ $user->status ?: 'Active' }}</span></td>
                    <td style="text-align:right;">
                      <div style="display:flex;gap:8px;justify-content:flex-end;align-items:center;flex-wrap:wrap;">
                        @if ($user->post_properties_count > 0)
                          <a class="btn-secondary" href="{{ route('users.properties', $user) }}">
                            View Properties ({{ $user->post_properties_count }})
                          </a>
                        @else
                          <span style="color:var(--text-muted);line-height:1;">-</span>
                        @endif
                        <button class="btn-secondary" type="button" data-edit-user="{{ json_encode($user->only(['id','name','email','mobile','city','location','state','role','admin_role','status'])) }}" onclick="openEditUser(this)">Edit</button>
                        <form method="POST" action="{{ route('users.toggle-status', $user) }}" style="margin:0;">@csrf @method('PATCH')
                          <button class="btn-ghost" type="submit">{{ $user->isActive() ? 'Block' : 'Activate' }}</button>
                        </form>
                        <form method="POST" action="{{ route('users.destroy', $user) }}" style="margin:0;" data-confirm="Delete this user permanently?" data-confirm-title="Delete user" data-confirm-label="Delete">@csrf @method('DELETE')
                          <button class="btn-ghost" style="color:var(--accent-red)" type="submit">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" style="text-align:center; padding:28px; color:var(--text-muted);">No app users found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @include('partials.pagination', ['paginator' => $appUsers])
        </div>
      </main>
    </div>
  </div>

  <!-- Add User Modal Dialog -->
  <div class="modal-backdrop" id="addUserModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title" id="userFormTitle">Add New User</h3>
        <button class="modal-close-btn" id="closeAddUserModal" aria-label="Close modal" type="button">×</button>
      </div>
      <form id="addUserForm" method="POST" action="{{ route('users.store') }}">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label" for="userNameInput">Full Name</label>
            <input type="text" class="form-control" id="userNameInput" name="name" value="{{ old('name') }}" placeholder="e.g. Ananya Sharma" maxlength="255" required>
            @error('name')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userEmailInput">Email Address</label>
            <input type="email" class="form-control" id="userEmailInput" name="email" value="{{ old('email') }}" placeholder="e.g. ananya@example.com" maxlength="255" required>
            @error('email')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userMobileInput">Mobile</label>
            <input type="tel" class="form-control" id="userMobileInput" name="mobile" value="{{ old('mobile') }}" maxlength="255" autocomplete="tel" required>
            @error('mobile')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userCityInput">City</label>
            <input type="text" class="form-control" id="userCityInput" name="city" value="{{ old('city') }}" maxlength="255">
            @error('city')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userLocationInput">Location</label>
            <input type="text" class="form-control" id="userLocationInput" name="location" value="{{ old('location') }}" maxlength="255">
            @error('location')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userStateInput">State</label>
            <input type="text" class="form-control" id="userStateInput" name="state" value="{{ old('state') }}" maxlength="255">
            @error('state')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userAdminRoleInput">Organizational role</label>
            <select class="form-control" id="userAdminRoleInput" name="admin_role">
              <option value="">No organizational role</option>
              @foreach (['admin' => 'Admin', 'hr' => 'HR', 'employee' => 'Employee', 'super admin' => 'Super Admin'] as $value => $label)
                <option value="{{ $value }}" @selected(old('admin_role') === $value)>{{ $label }}</option>
              @endforeach
            </select>
            @error('admin_role')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userRoleInput">App user role</label>
            <select class="form-control" id="userRoleInput" name="role">
              <option value="">No app user role</option>
              @foreach (['owner' => 'Owner', 'agent' => 'Agent', 'builder' => 'Builder', 'buyer' => 'Buyer', 'seller' => 'Seller'] as $value => $label)
                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
              @endforeach
            </select>
            @error('role')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userStatusInput">Status</label>
            <select class="form-control" id="userStatusInput" name="status">
              <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
              <option value="Inactive" @selected(old('status') === 'Inactive')>Inactive (Blocked)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="userPasswordInput">Password</label>
            <input type="password" class="form-control" id="userPasswordInput" name="password" autocomplete="new-password" minlength="8">
            <small class="setting-desc" id="passwordHint">Required for new users. Leave blank to keep current password when editing.</small>
            @error('password')<small class="field-error">{{ $message }}</small>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" for="userPasswordConfirmInput">Confirm Password</label>
            <input type="password" class="form-control" id="userPasswordConfirmInput" name="password_confirmation" autocomplete="new-password" minlength="8">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" id="cancelAddUser">Cancel</button>
          <button type="submit" class="btn-primary" id="userFormSubmit">Create User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Interactive Logic -->
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

    // Close the profile menu when the user clicks elsewhere.
    function closeAllDropdowns() {
      profileMenu.classList.remove('open');
    }

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.profile-dropdown-wrapper')) {
        closeAllDropdowns();
      }
    });

    // Modal Controls
    const addUserModal = document.getElementById('addUserModal');
    const openAddUserModal = document.getElementById('openAddUserModal');
    const closeAddUserModal = document.getElementById('closeAddUserModal');
    const cancelAddUser = document.getElementById('cancelAddUser');
    const addUserForm = document.getElementById('addUserForm');

    function openModal(record = null) {
      addUserForm.reset();
      addUserForm.querySelector('[name=_method]')?.remove();
      document.getElementById('userFormTitle').textContent = record ? 'Edit User' : 'Add New User';
      document.getElementById('userFormSubmit').textContent = record ? 'Save changes' : 'Create User';
      document.getElementById('userPasswordInput').required = !record;
      document.getElementById('userPasswordConfirmInput').required = !record;
      document.getElementById('passwordHint').textContent = record
        ? 'Leave blank to keep the current password.'
        : 'Required for new users.';

      if (record) {
        addUserForm.action = `{{ url('/users') }}/${record.id}`;
        addUserForm.insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">');
        document.getElementById('userNameInput').value = record.name || '';
        document.getElementById('userEmailInput').value = record.email || '';
        document.getElementById('userMobileInput').value = record.mobile || '';
        document.getElementById('userCityInput').value = record.city || '';
        document.getElementById('userLocationInput').value = record.location || '';
        document.getElementById('userStateInput').value = record.state || '';
        document.getElementById('userAdminRoleInput').value = record.admin_role || '';
        document.getElementById('userRoleInput').value = record.role || '';
        document.getElementById('userStatusInput').value = record.status || 'Active';
      } else {
        addUserForm.action = @json(route('users.store'));
        document.getElementById('userStatusInput').value = 'Active';
      }

      addUserModal.classList.add('open');
      document.getElementById('userNameInput').focus();
    }

    function openEditUser(button) {
      try {
        openModal(JSON.parse(button.dataset.editUser || '{}'));
      } catch (error) {
        console.error('Unable to open user editor', error);
      }
    }

    function closeModal() {
      addUserModal.classList.remove('open');
      addUserForm.reset();
      addUserForm.querySelector('[name=_method]')?.remove();
    }

    openAddUserModal.addEventListener('click', () => openModal());
    closeAddUserModal.addEventListener('click', closeModal);
    cancelAddUser.addEventListener('click', closeModal);
    addUserModal.addEventListener('click', (e) => {
      if (e.target === addUserModal) closeModal();
    });

    @if ($errors->any())
      openModal();
    @endif

    // Switch between organizational and app user tables.
    const employeesTab = document.getElementById('employeesTab');
    const appUsersTab = document.getElementById('appUsersTab');
    const employeesPanel = document.getElementById('employeesPanel');
    const appUsersPanel = document.getElementById('appUsersPanel');

    function showUserCategory(category) {
      const showEmployees = category === 'employees';
      employeesPanel.style.display = showEmployees ? '' : 'none';
      appUsersPanel.style.display = showEmployees ? 'none' : '';
      employeesTab.className = showEmployees ? 'btn-primary' : 'btn-secondary';
      appUsersTab.className = showEmployees ? 'btn-secondary' : 'btn-primary';
      employeesTab.setAttribute('aria-selected', String(showEmployees));
      appUsersTab.setAttribute('aria-selected', String(!showEmployees));
    }

    employeesTab.addEventListener('click', () => showUserCategory('employees'));
    appUsersTab.addEventListener('click', () => showUserCategory('app-users'));

    @if (request()->has('users_page'))
      showUserCategory('app-users');
    @elseif (request()->has('employees_page'))
      showUserCategory('employees');
    @endif

    document.querySelectorAll('[data-table-search]').forEach(searchInput => {
      searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        const table = document.getElementById(searchInput.dataset.tableSearch);
        table.querySelectorAll('tbody tr').forEach(row => {
          row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
      });
    });

  </script>
  <style>.field-error { display:block; margin-top:5px; color:var(--accent-red); }</style>
  @include('partials.confirm-modal')
</body>
</html>
