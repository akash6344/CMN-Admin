{{-- Properties modals: Reject (back to Operations with a comment) and Add Property. --}}
<div class="modal-backdrop" id="rejectPropertyModal">
  <div class="modal-box" style="max-width:480px;">
    <div class="modal-header">
      <h3 class="modal-title">Reject <span id="rejectPropertyCode">property</span></h3>
      <button class="modal-close-btn" type="button" id="closeRejectPropertyModal">×</button>
    </div>
    <form method="POST" id="rejectPropertyForm">
      @csrf
      @method('PATCH')
      <div class="modal-body">
        <p style="margin:0 0 12px;color:var(--text-muted);">This goes back to Operations with your comment. They fix it or ask the owner / builder for edits, then send it back to you. It doesn’t delete the property.</p>
        <div class="form-group">
          <label class="form-label" for="rejectionReasonInput">What needs fixing? *</label>
          <textarea class="form-control" name="admin_note" id="rejectionReasonInput" rows="4" required minlength="5" maxlength="1000" placeholder="e.g. The RERA number doesn't match the certificate — please confirm.">{{ old('admin_note') }}</textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="cancelRejectProperty">Cancel</button>
        <button class="btn-primary" type="submit" style="background:var(--accent-red);border-color:var(--accent-red);">Send back to Operations</button>
      </div>
    </form>
  </div>
</div>

@if ($withAdd ?? true)
  <div class="modal-backdrop" id="addPropertyModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title">Add Property</h3>
        <button class="modal-close-btn" type="button" id="closeAddPropertyModal">×</button>
      </div>
      <form method="POST" action="{{ route('properties.store') }}">
        @csrf
        <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <p style="grid-column:1 / -1;margin:0;color:var(--text-muted);font-size:13px;">Added on behalf of an owner. Operations reviews it first, then it comes back here for your approval.</p>
          <div class="form-group" style="grid-column:1 / -1;"><label class="form-label">Title *</label><input class="form-control" name="property_title" value="{{ old('property_title') }}" required maxlength="255"></div>
          <div class="form-group"><label class="form-label">Type *</label><input class="form-control" name="property_type" value="{{ old('property_type') }}" required maxlength="255" list="propertyTypeOptions"></div>
          <datalist id="propertyTypeOptions">@foreach ($propertyTypes ?? [] as $type)<option value="{{ $type }}"></option>@endforeach</datalist>
          <div class="form-group"><label class="form-label">For *</label><select class="form-control" name="property_for"><option>Sale</option><option @selected(old('property_for') === 'Rent')>Rent</option></select></div>
          <div class="form-group"><label class="form-label">Owner name *</label><input class="form-control" name="user_name" value="{{ old('user_name') }}" required maxlength="255"></div>
          <div class="form-group"><label class="form-label">Owner mobile</label><input class="form-control" name="user_mobile" value="{{ old('user_mobile') }}" maxlength="20" inputmode="tel"></div>
          <div class="form-group"><label class="form-label">Owner email</label><input class="form-control" type="email" name="user_email" value="{{ old('user_email') }}" maxlength="255"></div>
          <div class="form-group"><label class="form-label">City *</label><input class="form-control" name="city" value="{{ old('city') }}" required maxlength="255"></div>
          <div class="form-group"><label class="form-label">Locality</label><input class="form-control" name="location" value="{{ old('location') }}" maxlength="255"></div>
          <div class="form-group"><label class="form-label">Price *</label>
            <div style="display:flex;gap:8px"><input class="form-control" name="price" value="{{ old('price') }}" required inputmode="decimal" placeholder="e.g. 75">
              <select class="form-control" name="price_text" style="max-width:96px">@foreach (['Lac', 'Cr', 'K'] as $u)<option @selected(old('price_text', 'Lac') === $u)>{{ $u }}</option>@endforeach</select></div></div>
          @foreach (\App\Models\PostProperty::SPEC_LABELS as $key => $label)
            @continue($key === 'property_for')
            <div class="form-group">
              <label class="form-label" for="prop_{{ $key }}">{{ $label }}</label>
              <input class="form-control" name="{{ $key }}" id="prop_{{ $key }}" value="{{ old($key) }}" maxlength="255">
            </div>
          @endforeach
          <div class="form-group" style="grid-column:1 / -1;">
            <label class="form-label" for="propAboutInput">About Property</label>
            <textarea class="form-control" name="about_property" id="propAboutInput" rows="4">{{ old('about_property') }}</textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" id="cancelAddProperty">Cancel</button>
          <button class="btn-primary" type="submit">Save Property</button>
        </div>
      </form>
    </div>
  </div>
@endif

<script>
  (function () {
    const reject = document.getElementById('rejectPropertyModal');
    const closeReject = () => reject.classList.remove('open');
    window.openRejectProperty = function (button) {
      document.getElementById('rejectPropertyForm').action = button.dataset.rejectUrl;
      document.getElementById('rejectPropertyCode').textContent = button.dataset.rejectCode || 'property';
      reject.classList.add('open');
      document.getElementById('rejectionReasonInput').focus();
    };
    document.getElementById('closeRejectPropertyModal').onclick = closeReject;
    document.getElementById('cancelRejectProperty').onclick = closeReject;
    reject.onclick = (e) => { if (e.target === reject) closeReject(); };

    const add = document.getElementById('addPropertyModal');
    const openAdd = () => add?.classList.add('open');
    const closeAdd = () => add?.classList.remove('open');
    document.getElementById('openAddPropertyModal')?.addEventListener('click', openAdd);
    document.getElementById('closeAddPropertyModal')?.addEventListener('click', closeAdd);
    document.getElementById('cancelAddProperty')?.addEventListener('click', closeAdd);
    if (add) add.onclick = (e) => { if (e.target === add) closeAdd(); };
    @if ($errors->hasAny(['property_title', 'property_type', 'user_name', 'city', 'price', 'price_text', 'property_for', 'user_email'])) openAdd(); @endif

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeReject(); closeAdd(); } });
  })();
</script>
