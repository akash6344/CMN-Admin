<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delete Requests - CMNHousing Admin Portal</title>
  <meta name="description" content="Review property deletion requests before permanent removal.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')
    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Delete Requests</h1>
          <p class="page-subtitle">Users and builders raise deletion requests — approve or reject them here. Admins never delete listings from Property Management.</p>
        </div>
      </div>

      @if (session('success'))
        <div class="card" role="status" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-emerald)">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="card" role="alert" style="padding:14px 18px;margin-bottom:16px;color:var(--accent-red)">{{ $errors->first() }}</div>
      @endif

      <div class="card" style="padding:20px;">
        <div class="filter-tabs" style="margin-bottom:16px;">
          @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
            <a class="filter-tab {{ ($filters['status'] ?? 'pending') === $key ? 'active' : '' }}" href="{{ route('deletion-requests.index', ['status' => $key]) }}">
              {{ $label }}@if($key !== 'all') ({{ $counts[$key] }})@endif
            </a>
          @endforeach
        </div>

        <div class="table-responsive">
          <table class="custom-table">
            <thead>
              <tr>
                <th>PROPERTY</th>
                <th>REQUESTED BY</th>
                <th>REASON</th>
                <th>STATUS</th>
                <th>REQUESTED</th>
                <th style="text-align:right;">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($requests as $deletionRequest)
                <tr>
                  <td>
                    <div class="prop-title-meta">
                      <span class="table-id">{{ $deletionRequest->property_code ?: ('#'.$deletionRequest->post_property_id) }}</span>
                      <span class="table-property-title">{{ $deletionRequest->property_title ?: ($deletionRequest->property?->property_title ?: 'Untitled property') }}</span>
                    </div>
                  </td>
                  <td>{{ $deletionRequest->requester?->name ?: '—' }}</td>
                  <td style="max-width:280px;">{{ $deletionRequest->reason }}</td>
                  <td><span class="badge {{ $deletionRequest->statusBadgeClass() }}">{{ ucfirst($deletionRequest->status) }}</span></td>
                  <td>{{ optional($deletionRequest->created_at)?->diffForHumans() }}</td>
                  <td style="text-align:right;">
                    @if ($deletionRequest->isPending() && $deletionRequest->property)
                      <div class="prop-action-group" style="justify-content:flex-end;">
                        <form method="POST" action="{{ route('deletion-requests.approve', $deletionRequest) }}" data-confirm="Approve this deletion? The property will be permanently removed." data-confirm-title="Approve deletion" data-confirm-label="Delete property">
                          @csrf
                          @method('PATCH')
                          <button class="prop-btn prop-btn-danger" type="submit">Approve delete</button>
                        </form>
                        <button class="prop-btn prop-btn-outline" type="button"
                          data-reject-request-id="{{ $deletionRequest->id }}"
                          data-reject-request-code="{{ $deletionRequest->property_code }}"
                          onclick="openRejectDeletion(this)">
                          Reject request
                        </button>
                      </div>
                    @elseif ($deletionRequest->review_note)
                      <span style="color:var(--text-muted);font-size:13px;">{{ $deletionRequest->review_note }}</span>
                    @else
                      <span style="color:var(--text-muted);">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" style="text-align:center;padding:28px;color:var(--text-muted);">No deletion requests found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @include('partials.pagination', ['paginator' => $requests])
      </div>
    </main>
  </div>

  <div class="modal-backdrop" id="rejectDeletionModal">
    <div class="modal-box" style="max-width:480px;">
      <div class="modal-header">
        <h3 class="modal-title">Reject deletion request</h3>
        <button class="modal-close-btn" type="button" id="closeRejectDeletionModal">×</button>
      </div>
      <form method="POST" id="rejectDeletionForm">
        @csrf
        @method('PATCH')
        <div class="modal-body">
          <p style="margin:0 0 12px;color:var(--text-muted);">Explain why the deletion request for <strong id="rejectDeletionCode">this property</strong> is being declined.</p>
          <div class="form-group">
            <label class="form-label" for="reviewNoteInput">Rejection comment *</label>
            <textarea class="form-control" name="review_note" id="reviewNoteInput" rows="4" required minlength="5" maxlength="2000" placeholder="Why is this deletion request rejected?"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" id="cancelRejectDeletion">Cancel</button>
          <button class="btn-primary" type="submit">Reject request</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const deletionRejectBase = @json(url('/deletion-requests'));
    document.getElementById('closeRejectDeletionModal').onclick = closeRejectDeletion;
    document.getElementById('cancelRejectDeletion').onclick = closeRejectDeletion;
    document.getElementById('rejectDeletionModal').onclick = (e) => {
      if (e.target.id === 'rejectDeletionModal') closeRejectDeletion();
    };

    function openRejectDeletion(button) {
      document.getElementById('rejectDeletionForm').action = `${deletionRejectBase}/${button.dataset.rejectRequestId}/reject`;
      document.getElementById('rejectDeletionCode').textContent = button.dataset.rejectRequestCode || 'this property';
      document.getElementById('reviewNoteInput').value = '';
      document.getElementById('rejectDeletionModal').classList.add('open');
    }

    function closeRejectDeletion() {
      document.getElementById('rejectDeletionModal').classList.remove('open');
    }
  </script>
  @include('partials.confirm-modal')
</body>
</html>
