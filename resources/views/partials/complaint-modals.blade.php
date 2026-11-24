{{-- Complaints: Respond (reply to builder or internal note) and Take Action (status / priority). Used on the list and the detail page. --}}
<div class="modal-backdrop" id="respondModal">
  <div class="modal-box" style="max-width:520px">
    <div class="modal-header">
      <h3 class="modal-title" id="respondTitle">Respond to Complaint</h3>
      <button class="modal-close-btn" type="button" data-close aria-label="Close">×</button>
    </div>
    <form method="POST" id="respondForm">
      @csrf
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Recipient</label>
          <input type="text" class="form-control" id="respondRecipient" readonly>
        </div>
        <div class="form-group">
          <label class="form-label" for="respondType">Type</label>
          <select class="form-control" name="type" id="respondType">
            <option value="reply">Reply to builder (they see it in their portal)</option>
            <option value="note">Internal note (only CMNHousing team sees it)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="respondMessage">Message *</label>
          <textarea class="form-control" name="body" id="respondMessage" rows="4" required minlength="2" maxlength="5000" placeholder="Type your response..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="respondThen">Then</label>
          <select class="form-control" name="then" id="respondThen">
            <option value="">Keep status</option>
            <option value="resolved">Mark resolved</option>
            <option value="escalated">Escalate</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close>Cancel</button>
        <button type="submit" class="btn-primary">Send</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="actionModal">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-header">
      <h3 class="modal-title" id="actionTitle">Take Action</h3>
      <button class="modal-close-btn" type="button" data-close aria-label="Close">×</button>
    </div>
    <form method="POST" id="actionForm">
      @csrf
      @method('PATCH')
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="actionStatus">Status</label>
          <select class="form-control" name="status" id="actionStatus">
            @foreach (\App\Models\Housing\SupportTicket::STATUSES as $key => $label)
              <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="actionPriority">Priority</label>
          <select class="form-control" name="priority" id="actionPriority">
            @foreach (\App\Models\Housing\SupportTicket::PRIORITIES as $key => [$label, $hours])
              <option value="{{ $key }}">{{ $label }} — respond within {{ $hours }}h</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="actionNotes">Internal note</label>
          <textarea class="form-control" name="note" id="actionNotes" rows="3" maxlength="2000" placeholder="Why? (only the CMNHousing team sees this)"></textarea>
        </div>
        <p class="reply-hint" style="font-size:12.5px;color:var(--text-muted);margin:0">The builder is notified when a ticket is resolved, escalated or reopened. Escalations also alert BRM.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" data-close>Cancel</button>
        <button type="submit" class="btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    const respond = document.getElementById('respondModal');
    const action = document.getElementById('actionModal');
    const close = () => { respond.classList.remove('open'); action.classList.remove('open'); };

    document.querySelectorAll('[data-respond]').forEach((b) => b.addEventListener('click', () => {
      document.getElementById('respondForm').action = b.dataset.respond;
      document.getElementById('respondTitle').textContent = 'Respond: ' + b.dataset.title;
      document.getElementById('respondRecipient').value = b.dataset.recipient || '—';
      if (b.dataset.type) document.getElementById('respondType').value = b.dataset.type;
      respond.classList.add('open');
      document.getElementById('respondMessage').focus();
    }));
    document.querySelectorAll('[data-action]').forEach((b) => b.addEventListener('click', () => {
      document.getElementById('actionForm').action = b.dataset.action;
      document.getElementById('actionTitle').textContent = 'Action: ' + b.dataset.title;
      document.getElementById('actionStatus').value = b.dataset.status;
      document.getElementById('actionPriority').value = b.dataset.priority;
      action.classList.add('open');
    }));
    document.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', close));
    [respond, action].forEach((m) => m.addEventListener('click', (e) => { if (e.target === m) close(); }));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  })();
</script>
