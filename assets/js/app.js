// LPPAI Corner - Main JavaScript (SPA Edition)

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
   SPA ENGINE — Fetch real URLs, extract content
   No page reload for navigation or form submissions
   ============================================= */
var SPA = {
    loading: false,
    loadedScripts: {},  // track external scripts already loaded

    init: function() {
        // Store initial state for back/forward
        window.history.replaceState({ url: window.location.href }, '', window.location.href);
    },

    getBaseUrl: function() {
        var meta = document.querySelector('meta[name="base-url"]');
        return meta ? meta.content : '';
    },

    // Navigate to URL via AJAX — used by sidebar links
    navigate: function(url, pushState) {
        var self = this;
        if (self.loading) return;
        self.loading = true;

        var contentArea = document.querySelector('.content-area');
        if (!contentArea) { window.location.href = url; return; }

        // Fade out
        contentArea.style.transition = 'opacity 0.2s ease';
        contentArea.style.opacity = '0.3';

        fetch(url, { headers: { 'X-Requested-With': 'SPA' } })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(function(html) {
            self._apply(html, url, pushState !== false);
            self.loading = false;
        })
        .catch(function(err) {
            showToast('Gagal memuat halaman: ' + err.message, 'danger');
            contentArea.style.opacity = '1';
            self.loading = false;
        });
    },

    // Submit form via AJAX — used by all non-delete forms
    submitForm: function(form) {
        var self = this;
        if (self.loading) return;
        self.loading = true;

        var contentArea = document.querySelector('.content-area');
        if (!contentArea) { form.submit(); return; }

        var action = form.getAttribute('action') || window.location.href;
        var method = (form.getAttribute('method') || 'POST').toUpperCase();
        var formData = new FormData(form);

        // Fade out
        contentArea.style.transition = 'opacity 0.2s ease';
        contentArea.style.opacity = '0.3';

        fetch(action, {
            method: method,
            body: formData,
            headers: { 'X-Requested-With': 'SPA' }
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(function(html) {
            self._apply(html, action, false);
            self.loading = false;
        })
        .catch(function(err) {
            showToast('Error: ' + err.message, 'danger');
            contentArea.style.opacity = '1';
            self.loading = false;
        });
    },

    // Core: parse HTML response, swap content area
    _apply: function(html, url, pushState) {
        var self = this;
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');

        // If response has no .content-area → probably login redirect → full reload
        var newContent = doc.querySelector('.content-area');
        if (!newContent) {
            window.location.href = url;
            return;
        }

        var contentArea = document.querySelector('.content-area');
        var newTitle = doc.querySelector('title');
        var pageTitleEl = doc.querySelector('.page-title');

        setTimeout(function() {
            // 1. Swap HTML content
            contentArea.innerHTML = newContent.innerHTML;

            // 2. Update page title
            if (newTitle) document.title = newTitle.textContent;
            var currentPageTitle = document.querySelector('.page-title');
            if (currentPageTitle && pageTitleEl) {
                currentPageTitle.textContent = pageTitleEl.textContent;
            }

            // 3. Update sidebar active state
            self._updateNav(url);

            // 4. Load any new external CSS (e.g. DataTables CSS from EXTRA_HEAD)
            doc.querySelectorAll('head link[rel="stylesheet"]').forEach(function(link) {
                var href = link.getAttribute('href');
                if (href && !document.querySelector('link[href="' + href + '"]')) {
                    var el = document.createElement('link');
                    el.rel = 'stylesheet';
                    el.href = href;
                    document.head.appendChild(el);
                }
            });

            // 5. Load external scripts (jQuery, DataTables, etc.) then run inline scripts
            var externalScripts = [];
            doc.querySelectorAll('script[src]').forEach(function(s) {
                var src = s.getAttribute('src');
                // Skip our own app.js — it's already running
                if (src && src.indexOf('app.js') === -1) {
                    externalScripts.push(src);
                }
            });

            // Collect all inline scripts from the response
            var inlineScripts = [];
            doc.querySelectorAll('script:not([src])').forEach(function(s) {
                var code = s.textContent.trim();
                if (code) inlineScripts.push(code);
            });

            // Load external scripts sequentially, then run inline scripts
            self._loadScriptsSeq(externalScripts, 0, function() {
                // Run inline scripts after externals are loaded
                inlineScripts.forEach(function(code) {
                    try { (new Function(code))(); } catch(e) { /* ignore */ }
                });
            });

            // 6. Convert .alert to toast
            contentArea.querySelectorAll('.alert').forEach(function(el) {
                var type = 'info';
                if (el.classList.contains('alert-success')) type = 'success';
                else if (el.classList.contains('alert-danger'))  type = 'danger';
                else if (el.classList.contains('alert-warning')) type = 'warning';
                showToast(el.innerHTML, type, 6000);
                el.remove();
            });

            // 7. Fade in
            contentArea.style.transition = 'opacity 0.25s ease';
            contentArea.style.opacity = '1';

            // 8. Push to history
            if (pushState && url) {
                window.history.pushState({ url: url }, '', url);
            }

            // 9. Close mobile sidebar
            var sidebar = document.querySelector('.sidebar');
            var overlay = document.querySelector('.sidebar-overlay');
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('open');

            // 10. Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 220);
    },

    // Load external scripts one by one (sequential to preserve order)
    _loadScriptsSeq: function(srcs, idx, callback) {
        var self = this;
        if (idx >= srcs.length) { callback(); return; }

        var src = srcs[idx];
        // Already loaded? Skip
        if (self.loadedScripts[src] || document.querySelector('script[src="' + src + '"]')) {
            self.loadedScripts[src] = true;
            self._loadScriptsSeq(srcs, idx + 1, callback);
            return;
        }

        var s = document.createElement('script');
        s.src = src;
        s.onload = function() {
            self.loadedScripts[src] = true;
            self._loadScriptsSeq(srcs, idx + 1, callback);
        };
        s.onerror = function() {
            self._loadScriptsSeq(srcs, idx + 1, callback);
        };
        document.body.appendChild(s);
    },

    // Highlight the active sidebar link
    _updateNav: function(url) {
        var path = url.replace(/[?#].*$/, '');
        var urlFile = path.replace(/^.*\//, '');
        document.querySelectorAll('.sidebar-menu a').forEach(function(link) {
            var href = link.getAttribute('href') || '';
            var linkFile = href.replace(/^.*\//, '');
            if (linkFile && linkFile === urlFile) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
};

// Browser back / forward
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.url) {
        SPA.navigate(e.state.url, false);
    }
});

/* =============================================
   DOM READY
   ============================================= */
document.addEventListener('DOMContentLoaded', function() {
    SPA.init();

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

    // Convert PHP .alert blocks to toast on initial page load
    document.querySelectorAll('.alert').forEach(function(el) {
        var type = 'info';
        if (el.classList.contains('alert-success')) type = 'success';
        else if (el.classList.contains('alert-danger'))  type = 'danger';
        else if (el.classList.contains('alert-warning')) type = 'warning';
        showToast(el.innerHTML, type, 6000);
        el.remove();
    });
});

/* =============================================
   EVENT DELEGATION — SPA Navigation (sidebar links)
   Intercept all .page-nav clicks for AJAX navigation
   ============================================= */
document.addEventListener('click', function(e) {
    var link = e.target.closest('a.page-nav');
    if (!link) return;

    var href = link.getAttribute('href');
    if (!href || href === '#' || href.indexOf('logout') !== -1) return;

    e.preventDefault();
    e.stopImmediatePropagation();
    SPA.navigate(href, true);
}, true);

/* =============================================
   EVENT DELEGATION — SPA Form Submissions
   All forms inside .content-area submit via AJAX
   EXCEPT: file uploads, data-confirm (delete) forms, login
   ============================================= */
document.addEventListener('submit', function(e) {
    var form = e.target;

    // Skip if form is outside .content-area (e.g. login page)
    if (!form.closest('.content-area')) return;

    // Skip file upload forms (they need traditional submission)
    if (form.getAttribute('enctype') === 'multipart/form-data') return;
    if (form.querySelector('input[type="file"]')) return;

    // Skip forms with data-confirm buttons (handled by confirm dialog → ajaxDelete or submitForm)
    var submitBtn = form.querySelector('[data-confirm]');
    if (submitBtn && submitBtn.dataset.table) return; // AJAX delete — handled separately

    // Skip forms that have data-no-spa attribute
    if (form.hasAttribute('data-no-spa')) return;

    e.preventDefault();
    SPA.submitForm(form);
}, false);

/* =============================================
   EVENT DELEGATION — data-confirm + AJAX delete
   Works for all pages including DataTables pagination
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
            // AJAX delete — row animation, no reload
            var csrf = form ? (form.querySelector('[name=csrf_token]') || {}).value : '';
            if (!csrf) {
                var csrfEl = document.querySelector('input[name=csrf_token]');
                csrf = csrfEl ? csrfEl.value : '';
            }
            var row = el.closest('tr');
            ajaxDelete(table, id, csrf, row);
        } else if (form) {
            // Non-delete confirmed form → submit via SPA
            SPA.submitForm(form);
        } else if (el.tagName === 'A') {
            SPA.navigate(el.href, true);
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

