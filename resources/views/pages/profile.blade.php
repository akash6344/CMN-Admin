<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - CMNHousing Admin Portal</title>
  <meta name="description" content="Manage your CMNHousing admin profile details and password.">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')

      <main class="page-content">
        <div class="page-header">
          <h1 class="page-title">My Profile</h1>
          <p class="page-subtitle">Update your personal details, photo, and password</p>
        </div>

        @if (session('success'))
          <div class="card" role="status" style="padding:14px 18px; margin-bottom:16px; color:var(--accent-emerald);">{{ session('success') }}</div>
        @endif

        <div class="profile-layout">
          <section class="settings-card profile-photo-card">
            <div class="settings-card-header">
              <div class="settings-icon-circle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </div>
              <div>
                <h2 class="settings-card-title">Profile Photo</h2>
                <span class="settings-card-subtitle">Shown in the top header and profile page</span>
              </div>
            </div>

            <div class="profile-photo-row">
              <div class="profile-photo-frame" id="profilePhotoFrame">
                <div class="profile-photo-fallback" id="profilePhotoFallback" @if($user->profilePhotoUrl()) hidden @endif>{{ $user->initials() }}</div>
                <img
                  src="{{ $user->profilePhotoUrl() ?: '' }}"
                  alt="{{ $user->name }}"
                  class="profile-photo-preview"
                  id="profilePhotoPreview"
                  @if(! $user->profilePhotoUrl()) hidden @endif
                >
              </div>

              <div class="profile-photo-actions">
                <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" id="profilePhotoForm">
                  @csrf
                  <input type="file" id="profilePhotoInput" name="photo" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" hidden>
                  <div class="profile-photo-buttons">
                    <label class="btn-secondary profile-upload-btn" for="profilePhotoInput">Choose Photo</label>
                    <button type="submit" class="btn-primary" id="savePhotoBtn" hidden>Save Photo</button>
                  </div>
                </form>
                @if ($user->profile_photo_path)
                  <form method="POST" action="{{ route('profile.photo.destroy') }}" id="removePhotoForm" data-confirm="Remove your profile photo?" data-confirm-title="Remove photo" data-confirm-label="Remove">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-ghost">Remove Photo</button>
                  </form>
                @endif
                <p class="setting-desc" id="photoHelpText">JPG, PNG or WebP. Max 2 MB. You can crop the photo after choosing it.</p>
                @error('photo')<small class="field-error">{{ $message }}</small>@enderror
              </div>
            </div>
          </section>

          <section class="settings-card">
            <div class="settings-card-header">
              <div class="settings-icon-circle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 20h9"></path>
                  <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                </svg>
              </div>
              <div>
                <h2 class="settings-card-title">Personal Details</h2>
                <span class="settings-card-subtitle">Name, email and mobile number</span>
              </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="profile-form">
              @csrf
              @method('PUT')
              <div class="form-group">
                <label class="form-label" for="profileName">Full Name</label>
                <input type="text" class="form-control" id="profileName" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>
                @error('name')<small class="field-error">{{ $message }}</small>@enderror
              </div>
              <div class="form-group">
                <label class="form-label" for="profileEmail">Email Address</label>
                <input type="email" class="form-control" id="profileEmail" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required>
                @error('email')<small class="field-error">{{ $message }}</small>@enderror
              </div>
              <div class="form-group">
                <label class="form-label" for="profileMobile">Mobile</label>
                <input type="tel" class="form-control" id="profileMobile" name="mobile" value="{{ old('mobile', $user->mobile) }}" maxlength="32" autocomplete="tel">
                @error('mobile')<small class="field-error">{{ $message }}</small>@enderror
              </div>
              <div class="profile-form-actions">
                <button type="submit" class="btn-primary">Save Details</button>
              </div>
            </form>
          </section>

          <section class="settings-card">
            <div class="settings-card-header">
              <div class="settings-icon-circle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
              </div>
              <div>
                <h2 class="settings-card-title">Change Password</h2>
                <span class="settings-card-subtitle">Use a strong password you do not reuse elsewhere</span>
              </div>
            </div>

            <form method="POST" action="{{ route('profile.password.update') }}" class="profile-form">
              @csrf
              @method('PUT')
              <div class="form-group">
                <label class="form-label" for="currentPassword">Current Password</label>
                <input type="password" class="form-control" id="currentPassword" name="current_password" autocomplete="current-password" required>
                @error('current_password')<small class="field-error">{{ $message }}</small>@enderror
              </div>
              <div class="form-group">
                <label class="form-label" for="newPassword">New Password</label>
                <input type="password" class="form-control" id="newPassword" name="password" autocomplete="new-password" minlength="8" required>
                @error('password')<small class="field-error">{{ $message }}</small>@enderror
              </div>
              <div class="form-group">
                <label class="form-label" for="confirmPassword">Confirm New Password</label>
                <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" autocomplete="new-password" minlength="8" required>
              </div>
              <div class="profile-form-actions">
                <button type="submit" class="btn-primary">Update Password</button>
              </div>
            </form>
          </section>
        </div>
      </main>
    </div>
  </div>

  <div class="modal-backdrop" id="photoCropModal">
    <div class="modal-box photo-crop-modal">
      <div class="modal-header">
        <h3 class="modal-title">Crop Profile Photo</h3>
        <button type="button" class="modal-close-btn" id="closePhotoCropModal" aria-label="Close">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body photo-crop-body">
        <p class="setting-desc" style="margin-bottom:12px;">Drag to move and use the corners to select the part you want.</p>
        <div class="photo-crop-stage">
          <img id="photoCropImage" alt="Crop preview">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="cancelPhotoCrop">Cancel</button>
        <button type="button" class="btn-primary" id="applyPhotoCrop">Use This Crop</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
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

    const photoInput = document.getElementById('profilePhotoInput');
    const savePhotoBtn = document.getElementById('savePhotoBtn');
    const photoPreview = document.getElementById('profilePhotoPreview');
    const photoFallback = document.getElementById('profilePhotoFallback');
    const photoHelpText = document.getElementById('photoHelpText');
    const photoForm = document.getElementById('profilePhotoForm');
    const cropModal = document.getElementById('photoCropModal');
    const cropImage = document.getElementById('photoCropImage');
    const closeCropModalBtn = document.getElementById('closePhotoCropModal');
    const cancelCropBtn = document.getElementById('cancelPhotoCrop');
    const applyCropBtn = document.getElementById('applyPhotoCrop');

    let previewObjectUrl = null;
    let sourceObjectUrl = null;
    let cropper = null;
    let selectedFileName = 'profile.jpg';

    function showPhotoPreview(url) {
      if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
      }
      previewObjectUrl = url.startsWith('blob:') ? url : null;
      photoPreview.src = url;
      photoPreview.hidden = false;
      photoFallback.hidden = true;
    }

    function destroyCropper() {
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
      if (sourceObjectUrl) {
        URL.revokeObjectURL(sourceObjectUrl);
        sourceObjectUrl = null;
      }
      cropImage.removeAttribute('src');
    }

    function closeCropModal() {
      cropModal.classList.remove('open');
      destroyCropper();
      photoInput.value = '';
    }

    function openCropModal(file) {
      selectedFileName = (file.name || 'profile').replace(/\.[^.]+$/, '') + '.jpg';
      destroyCropper();
      sourceObjectUrl = URL.createObjectURL(file);
      cropImage.src = sourceObjectUrl;
      cropModal.classList.add('open');

      cropImage.onload = () => {
        cropper = new Cropper(cropImage, {
          aspectRatio: 1,
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 0.85,
          responsive: true,
          background: false,
          guides: true,
          center: true,
          highlight: false,
          cropBoxMovable: true,
          cropBoxResizable: true,
          toggleDragModeOnDblclick: false,
        });
      };
    }

    function applyCrop() {
      if (!cropper) {
        return;
      }

      const canvas = cropper.getCroppedCanvas({
        width: 512,
        height: 512,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
      });

      if (!canvas) {
        return;
      }

      canvas.toBlob((blob) => {
        if (!blob) {
          return;
        }

        const croppedFile = new File([blob], selectedFileName, { type: 'image/jpeg' });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(croppedFile);
        photoInput.files = dataTransfer.files;

        const croppedPreviewUrl = URL.createObjectURL(blob);
        showPhotoPreview(croppedPreviewUrl);
        savePhotoBtn.hidden = false;
        if (photoHelpText) {
          photoHelpText.textContent = 'Cropped photo ready. Click Save Photo to upload.';
        }

        cropModal.classList.remove('open');
        destroyCropper();
      }, 'image/jpeg', 0.92);
    }

    if (photoInput && savePhotoBtn && photoPreview && photoFallback) {
      photoInput.addEventListener('change', () => {
        const file = photoInput.files[0];
        if (!file) {
          savePhotoBtn.hidden = true;
          return;
        }
        openCropModal(file);
      });
    }

    closeCropModalBtn?.addEventListener('click', closeCropModal);
    cancelCropBtn?.addEventListener('click', closeCropModal);
    applyCropBtn?.addEventListener('click', applyCrop);
    cropModal?.addEventListener('click', (event) => {
      if (event.target === cropModal) {
        closeCropModal();
      }
    });

    if (photoForm && savePhotoBtn && photoInput) {
      photoForm.addEventListener('submit', (event) => {
        if (!photoInput.files.length) {
          event.preventDefault();
          savePhotoBtn.hidden = true;
        }
      });
    }
  </script>
  @include('partials.confirm-modal')
</body>
</html>
