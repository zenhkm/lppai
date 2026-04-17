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
   PAGE LOADER - AJAX Navigation
   ============================================= */
var PageLoader = {
    isLoading: false,
    currentPage: null,

    // Extract page ID from URL
    getPageFromUrl: function(pathname) {
        pathname = pathname || window.location.pathname;
        pathname = pathname.replace(/^.*\//, '').replace(/\.php$/, '');
        return pathname || 'dashboard';
    },

    // Map page names to page IDs for API
    getPageId: function(pageName) {
        var map = {
            'dashboard': 'dashboard',
            'pretes-peserta': 'pretes-peserta',
            'pretes-hasil': 'pretes-hasil',
            'pretes-daftar': 'pretes-daftar',
            'tutorial-gel1-pendaftaran': 'tutorial-gel1-pendaftaran',
            'tutorial-gel1-pembagian': 'tutorial-gel1-pembagian',
            'tutorial-gel1-kelulusan': 'tutorial-gel1-kelulusan',
            'tutorial-gel2-pendaftaran': 'tutorial-gel2-pendaftaran',
            'tutorial-gel2-pembagian': 'tutorial-gel2-pembagian',
            'tutorial-gel2-kelulusan': 'tutorial-gel2-kelulusan',
            'tutorial-mandiri-pendaftaran': 'tutorial-mandiri-pendaftaran',
            'tutorial-mandiri-pembagian': 'tutorial-mandiri-pembagian',
            'admin': 'admin-dashboard',
            'users': 'admin-users',
            'pengumuman': 'admin-pengumuman',
            'pretes-jadwal': 'admin-pretes-jadwal',
        };
        return map[pageName] || pageName;
    },

    // Load page via AJAX
    load: function(pageId, title, pushState) {
        var self = this;
        if (self.isLoading) return;
        self.isLoading = true;

        var baseUrl = document.querySelector('meta[name="base-url"]')
                        ? document.querySelector('meta[name="base-url"]').content
                        : '';
        var contentArea = document.querySelector('.content-area');

        fetch(baseUrl + '/api/load-page.php?page=' + encodeURIComponent(pageId))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    // Fade out
                    contentArea.style.opacity = '0';
                    contentArea.style.transition = 'opacity 0.25s ease';

                    setTimeout(function() {
                        // Update content
                        contentArea.innerHTML = data.content;
                        document.title = (data.title || 'LPPAI Corner') + ' - LPPAI Corner';

                        // Update page title
                        var pageTitle = document.querySelector('.page-title');
                        if (pageTitle) pageTitle.textContent = data.title || 'LPPAI Corner';

                        // Update sidebar active state
                        self.updateActiveNav(pageId);

                        // Fade in
                        contentArea.style.opacity = '1';

                        // Re-attach DataTables if present
                        if (window.$ && window.$.fn.dataTable) {
                            var existingTable = window.$.fn.dataTable.fnIsDataTable('table.datatable');
                            if (existingTable) window.$(existingTable).fnDestroy();
                            if (window.$.fn.DataTable) {
                                window.$('table.datatable').DataTable({
                                    pageLength: 10,
                                    language: { url: baseUrl + '/assets/datatables-id.json' }
                                });
                            }
                        }

                        // Re-init alerts to toasts
                        document.querySelectorAll('.alert').forEach(function(el) {
                            var type = 'info';
                            if (el.classList.contains('alert-success')) type = 'success';
                            else if (el.classList.contains('alert-danger'))  type = 'danger';
                            else if (el.classList.contains('alert-warning')) type = 'warning';
                            showToast(el.innerHTML, type, 6000);
                            el.remove();
                        });

                        // Push to History API
                        if (pushState !== false) {
                            var pageUrl = baseUrl + '/' + pageId + '.php';
                            window.history.pushState({ page: pageId }, data.title, pageUrl);
                        }

                        // Close mobile menu
                        var sidebar = document.querySelector('.sidebar');
                        var overlay = document.querySelector('.sidebar-overlay');
                        if (sidebar) sidebar.classList.remove('open');
                        if (overlay) overlay.classList.remove('open');

                        self.isLoading = false;
                    }, 280);
                } else {
                    showToast(data.message || 'Gagal memuat halaman', 'danger');
                    self.isLoading = false;
                }
            })
            .catch(function(err) {
                showToast('Network error: ' + err.message, 'danger');
                self.isLoading = false;
            });
    },

    // Update active nav link
    updateActiveNav: function(pageId) {
        document.querySelectorAll('.sidebar-menu a').forEach(function(link) {
            var href = link.getAttribute('href') || '';
            var linkPageId = href.replace(/^.*\//, '').replace(/\.php$/, '');
            
            if (linkPageId === pageId || href === pageId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
};

// Handle History API back/forward
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.page) {
        PageLoader.load(e.state.page, '', false);
    }
});

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

    // Update active nav for current page
    var pathname = window.location.pathname;
    var pageName = pathname.replace(/^.*\//, '').replace(/\.php$/, '') || 'dashboard';
    PageLoader.updateActiveNav(pageName);

    // Initialize History API state
    var currentPageId = PageLoader.getPageFromUrl(pathname);
    window.history.replaceState({ page: currentPageId }, '', pathname);
});

/* =============================================
   EVENT DELEGATION — AJAX Navigation
   ============================================= */
// Intercept .page-nav links for AJAX navigation
document.addEventListener('click', function(e) {
    var link = e.target.closest('.page-nav');
    if (!link) return;

    var href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('http') || href.startsWith('javascript')) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    var pageId = href.replace(/^.*\//, '').replace(/\.php$/, '') || 'dashboard';
    var title = link.textContent || 'LPPAI Corner';

    PageLoader.load(pageId, title);
}, true);

// Intercept form submissions for AJAX (for filters, status changes, etc.)
document.addEventListener('submit', function(e) {
    var form = e.target;
    if (form.classList.contains('page-form')) {
        e.preventDefault();

        var baseUrl = document.querySelector('meta[name="base-url"]')
                        ? document.querySelector('meta[name="base-url"]').content
                        : '';
        var formData = new FormData(form);
        var formAction = form.getAttribute('action') || window.location.pathname;

        fetch(formAction, {
            method: form.getAttribute('method') || 'POST',
            body: formData
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            // Extract content from response
            var temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Find content-area div in response
            var contentDiv = temp.querySelector('.content-area');
            if (contentDiv) {
                var contentArea = document.querySelector('.content-area');
                contentArea.style.opacity = '0';
                contentArea.style.transition = 'opacity 0.25s ease';

                setTimeout(function() {
                    contentArea.innerHTML = contentDiv.innerHTML;
                    contentArea.style.opacity = '1';

                    // Re-init alerts
                    document.querySelectorAll('.alert').forEach(function(el) {
                        var type = 'info';
                        if (el.classList.contains('alert-success')) type = 'success';
                        else if (el.classList.contains('alert-danger'))  type = 'danger';
                        else if (el.classList.contains('alert-warning')) type = 'warning';
                        showToast(el.innerHTML, type, 6000);
                        el.remove();
                    });

                    // Re-attach DataTables
                    if (window.$ && window.$.fn.dataTable) {
                        var existingTable = window.$.fn.dataTable.fnIsDataTable('table.datatable');
                        if (existingTable) window.$(existingTable).fnDestroy();
                        if (window.$.fn.DataTable) {
                            window.$('table.datatable').DataTable({
                                pageLength: 10
                            });
                        }
                    }
                }, 280);
            } else {
                // Fallback: full reload if content div not found
                window.location.reload();
            }
        })
        .catch(function(err) {
            showToast('Network error: ' + err.message, 'danger');
        });
    }
}, false);

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

