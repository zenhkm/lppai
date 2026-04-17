// LPPAI Corner - Main JavaScript

/* =============================================
   TOAST NOTIFICATION
   ============================================= */
function showToast(message, type, duration) {
    type     = type || 'success';
    duration = duration || 4500;
    var icons = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };

    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML =
        '<span class="toast-icon">' + (icons[type] || '💬') + '</span>' +
        '<span class="toast-body">' + message + '</span>' +
        '<button class="toast-close" aria-label="Tutup">&times;</button>' +
        '<span class="toast-progress" style="animation-duration:' + duration + 'ms"></span>';

    container.appendChild(toast);

    function dismiss() {
        toast.classList.add('hide');
        setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 360);
    }
    toast.querySelector('.toast-close').addEventListener('click', dismiss);
    setTimeout(dismiss, duration);
}

/* =============================================
   CONFIRM DIALOG
   ============================================= */
function showConfirm(message, onConfirm, type) {
    type = type || 'danger';
    var icons     = { danger: '🗑️', warning: '⚠️', info: '❓', success: '✅' };
    var btnColors = { danger: 'background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff',
                      warning:'background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff',
                      info:   'background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff',
                      success:'background:linear-gradient(135deg,#1a5632,#2d7a4a);color:#fff' };

    var backdrop = document.getElementById('confirm-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'confirm-backdrop';
        backdrop.innerHTML =
            '<div id="confirm-dialog">' +
            '<div class="confirm-icon" id="confirm-icon"></div>' +
            '<h4 id="confirm-title">Konfirmasi</h4>' +
            '<p id="confirm-message"></p>' +
            '<div class="confirm-actions">' +
            '<button id="confirm-cancel" class="btn btn-secondary btn-sm" style="min-width:100px;">Batal</button>' +
            '<button id="confirm-ok" class="btn btn-danger btn-sm" style="min-width:100px;">Ya, Lanjutkan</button>' +
            '</div></div>';
        document.body.appendChild(backdrop);
    }

    document.getElementById('confirm-icon').textContent    = icons[type] || '❓';
    document.getElementById('confirm-message').textContent = message;
    var okBtn = document.getElementById('confirm-ok');
    okBtn.setAttribute('style', (btnColors[type] || btnColors.danger) + ';min-width:100px;');
    backdrop.classList.add('show');

    function close() { backdrop.classList.remove('show'); }

    var newOk     = okBtn.cloneNode(true);
    var cancelBtn = document.getElementById('confirm-cancel');
    var newCancel = cancelBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOk, okBtn);
    cancelBtn.parentNode.replaceChild(newCancel, cancelBtn);

    newOk.addEventListener('click', function() { close(); if (onConfirm) onConfirm(); });
    newCancel.addEventListener('click', close);
    backdrop.addEventListener('click', function(e) { if (e.target === backdrop) close(); });
}

/* =============================================
   AJAX DELETE — removes row without page reload
   Requires data-table and data-id on the button,
   and a hidden input[name=csrf_token] in same form
   ============================================= */
function ajaxDelete(table, id, csrfToken, rowEl, successMsg) {
    var baseUrl = document.querySelector('meta[name="base-url"]')
                    ? document.querySelector('meta[name="base-url"]').content
                    : '';
    var formData = new FormData();
    formData.append('table',      table);
    formData.append('id',         id);
    formData.append('csrf_token', csrfToken);

    fetch(baseUrl + '/api/ajax-delete.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (rowEl) {
                rowEl.classList.add('removing');
                setTimeout(function() {
                    if (rowEl.parentNode) rowEl.parentNode.removeChild(rowEl);
                }, 420);
            }
            showToast(successMsg || data.message, 'success');
        } else {
            showToast(data.message || 'Gagal menghapus data.', 'danger');
        }
    })
    .catch(function(err) {
        showToast('Network error: ' + err.message, 'danger');
    });
}

/* =============================================
   DOM READY
   ============================================= */
document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    var hamburger = document.querySelector('.hamburger');
    var sidebar   = document.querySelector('.sidebar');
    var overlay   = document.querySelector('.sidebar-overlay');

    if (hamburger) {
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }

    // Convert PHP .alert blocks to toast
    document.querySelectorAll('.alert').forEach(function(el) {
        var type = 'info';
        if (el.classList.contains('alert-success')) type = 'success';
        else if (el.classList.contains('alert-danger'))  type = 'danger';
        else if (el.classList.contains('alert-warning')) type = 'warning';
        showToast(el.innerHTML, type, 6000);
        el.remove();
    });

    // Page transition: add class to re-trigger animation on load
    var ca = document.querySelector('.content-area');
    if (ca) {
        ca.style.animation = 'none';
        ca.offsetHeight; // reflow
        ca.style.animation = '';
    }
});

/* =============================================
   EVENT DELEGATION — data-confirm + AJAX delete
   Works for all pages including DataTables
   ============================================= */
document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    var msg   = el.dataset.confirm;
    var table = el.dataset.table || '';
    var id    = el.dataset.id    || '';
    var isAjaxDelete = (table && id);
    var type  = el.classList.contains('btn-warning') ? 'warning' : 'danger';
    var form  = el.closest('form');

    showConfirm(msg, function() {
        if (isAjaxDelete) {
            // AJAX delete — no page reload
            var csrf = form ? (form.querySelector('[name=csrf_token]') || {}).value : '';
            // Fallback: grab any csrf_token on page
            if (!csrf) {
                var csrfEl = document.querySelector('input[name=csrf_token]');
                csrf = csrfEl ? csrfEl.value : '';
            }
            var row = el.closest('tr');
            ajaxDelete(table, id, csrf, row);
        } else if (form) {
            form.submit();
        } else if (el.tagName === 'A') {
            window.location.href = el.href;
        }
    }, type);

}, true);

/* =============================================
   MODAL HELPERS
   ============================================= */
function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('show');
}
function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('show');
}

