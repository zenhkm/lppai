// LPPAI Corner - Main JavaScript

/* =============================================
   TOAST NOTIFICATION
   Usage: showToast('Pesan berhasil!', 'success')
   Types: success | danger | warning | info
   ============================================= */
function showToast(message, type, duration) {
    type = type || 'success';
    duration = duration || 4000;

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

    var closeBtn = toast.querySelector('.toast-close');
    function dismiss() {
        toast.classList.add('hide');
        setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 350);
    }
    closeBtn.addEventListener('click', dismiss);
    setTimeout(dismiss, duration);
}

/* =============================================
   CONFIRM DIALOG
   Usage: showConfirm('Yakin hapus?', fn, 'danger')
   ============================================= */
function showConfirm(message, onConfirm, type) {
    type = type || 'danger';
    var icons = { danger: '🗑️', warning: '⚠️', info: '❓', success: '✅' };
    var btnColors = { danger: '#dc2626', warning: '#d97706', info: '#2563eb', success: '#1a5632' };

    var backdrop = document.getElementById('confirm-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'confirm-backdrop';
        backdrop.innerHTML =
            '<div id="confirm-dialog">' +
            '  <div class="confirm-icon" id="confirm-icon"></div>' +
            '  <h4 id="confirm-title">Konfirmasi</h4>' +
            '  <p id="confirm-message"></p>' +
            '  <div class="confirm-actions">' +
            '    <button id="confirm-cancel" class="btn btn-sm" style="background:#f3f4f6;color:#374151;min-width:90px;">Batal</button>' +
            '    <button id="confirm-ok" class="btn btn-sm" style="min-width:90px;">Ya, Lanjutkan</button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(backdrop);
    }

    document.getElementById('confirm-icon').textContent = icons[type] || '❓';
    document.getElementById('confirm-message').textContent = message;
    document.getElementById('confirm-ok').style.background = btnColors[type] || '#dc2626';
    document.getElementById('confirm-ok').style.color = '#fff';
    backdrop.classList.add('show');

    function close() { backdrop.classList.remove('show'); }

    var okBtn = document.getElementById('confirm-ok');
    var cancelBtn = document.getElementById('confirm-cancel');

    // Clone to remove old listeners
    var newOk = okBtn.cloneNode(true);
    var newCancel = cancelBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOk, okBtn);
    cancelBtn.parentNode.replaceChild(newCancel, cancelBtn);

    newOk.addEventListener('click', function() { close(); if (onConfirm) onConfirm(); });
    newCancel.addEventListener('click', close);
    backdrop.addEventListener('click', function(e) { if (e.target === backdrop) close(); });
}

/* =============================================
   DOM READY
   ============================================= */
document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    var hamburger = document.querySelector('.hamburger');
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');

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

    // Auto-convert PHP .alert blocks to toast (and remove from DOM)
    document.querySelectorAll('.alert').forEach(function(el) {
        var type = 'info';
        if (el.classList.contains('alert-success')) type = 'success';
        else if (el.classList.contains('alert-danger'))  type = 'danger';
        else if (el.classList.contains('alert-warning')) type = 'warning';
        showToast(el.innerHTML, type, 6000);
        el.remove();
    });
});

// Event delegation untuk data-confirm — bekerja untuk semua halaman
// termasuk DataTables (pagination, dll) tanpa perlu re-attach listener
document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    var msg  = el.dataset.confirm;
    var type = el.classList.contains('btn-warning') ? 'warning' : 'danger';
    var form = el.closest('form');

    showConfirm(msg, function() {
        if (form) {
            form.submit();
        } else if (el.tagName === 'A') {
            window.location.href = el.href;
        }
    }, type);
}, true); // useCapture=true agar menangkap sebelum handler lain

// Modal functions
function openModal(id) {
    document.getElementById(id).classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}
