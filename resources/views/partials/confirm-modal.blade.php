<div class="modal-backdrop" id="appConfirmModal" aria-hidden="true">
  <div class="modal-box" style="max-width:420px;">
    <div class="modal-header">
      <h3 class="modal-title" id="appConfirmTitle">Confirm</h3>
      <button class="modal-close-btn" type="button" id="appConfirmClose" aria-label="Close">×</button>
    </div>
    <div class="modal-body">
      <p id="appConfirmMessage" style="margin:0;line-height:1.5;color:var(--text-main);"></p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-secondary" id="appConfirmCancel">Cancel</button>
      <button type="button" class="btn-primary" id="appConfirmOk" style="background:var(--accent-red);border-color:var(--accent-red);">Confirm</button>
    </div>
  </div>
</div>
<script>
  (function () {
    if (window.openAppConfirm) return;

    const modal = document.getElementById('appConfirmModal');
    const titleEl = document.getElementById('appConfirmTitle');
    const messageEl = document.getElementById('appConfirmMessage');
    const okBtn = document.getElementById('appConfirmOk');
    const cancelBtn = document.getElementById('appConfirmCancel');
    const closeBtn = document.getElementById('appConfirmClose');
    let pendingAction = null;

    function closeAppConfirm() {
      modal.classList.remove('open');
      pendingAction = null;
    }

    window.openAppConfirm = function ({ title = 'Confirm', message = 'Are you sure?', confirmLabel = 'Confirm', danger = true, onConfirm = null } = {}) {
      titleEl.textContent = title;
      messageEl.textContent = message;
      okBtn.textContent = confirmLabel;
      okBtn.style.background = danger ? 'var(--accent-red)' : '';
      okBtn.style.borderColor = danger ? 'var(--accent-red)' : '';
      if (!danger) {
        okBtn.style.background = '';
        okBtn.style.borderColor = '';
      }
      pendingAction = onConfirm;
      modal.classList.add('open');
    };

    window.bindConfirmForms = function (selector = 'form[data-confirm]') {
      document.querySelectorAll(selector).forEach((form) => {
        form.addEventListener('submit', (event) => {
          event.preventDefault();
          openAppConfirm({
            title: form.dataset.confirmTitle || 'Confirm',
            message: form.dataset.confirm || 'Are you sure?',
            confirmLabel: form.dataset.confirmLabel || 'Confirm',
            danger: form.dataset.confirmDanger !== 'false',
            onConfirm: () => form.submit(),
          });
        });
      });
    };

    okBtn.addEventListener('click', () => {
      const action = pendingAction;
      closeAppConfirm();
      if (typeof action === 'function') action();
    });
    cancelBtn.addEventListener('click', closeAppConfirm);
    closeBtn.addEventListener('click', closeAppConfirm);
    modal.addEventListener('click', (event) => {
      if (event.target === modal) closeAppConfirm();
    });

    document.addEventListener('DOMContentLoaded', () => bindConfirmForms());
    bindConfirmForms();
  })();
</script>
