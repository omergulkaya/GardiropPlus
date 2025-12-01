/**
 * Admin Panel JavaScript
 * Modern UX features, keyboard shortcuts, real-time updates
 */

(function() {
    'use strict';

    // Theme Management
    const ThemeManager = {
        init() {
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            this.setTheme(savedTheme);
            this.bindEvents();
        },

        setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('admin-theme', theme);
            this.updateThemeIcon(theme);
        },

        toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            this.setTheme(newTheme);
        },

        updateThemeIcon(theme) {
            const icon = document.querySelector('.theme-toggle i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
            }
        },

        bindEvents() {
            const toggleBtn = document.querySelector('.theme-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => this.toggleTheme());
            }
        }
    };

    // Toast Notifications
    const ToastManager = {
        container: null,

        init() {
            this.createContainer();
        },

        createContainer() {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        },

        show(message, type = 'info', duration = 5000) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-${this.getIcon(type)} me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close" aria-label="Close"></button>
                </div>
            `;

            const closeBtn = toast.querySelector('.btn-close');
            closeBtn.addEventListener('click', () => this.hide(toast));

            this.container.appendChild(toast);

            // Auto hide
            if (duration > 0) {
                setTimeout(() => this.hide(toast), duration);
            }

            return toast;
        },

        hide(toast) {
            toast.style.animation = 'slideOutRight 0.3s ease-in-out';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        },

        getIcon(type) {
            const icons = {
                success: 'check-circle-fill',
                error: 'x-circle-fill',
                warning: 'exclamation-triangle-fill',
                info: 'info-circle-fill'
            };
            return icons[type] || icons.info;
        },

        success(message, duration) {
            return this.show(message, 'success', duration);
        },

        error(message, duration) {
            return this.show(message, 'error', duration);
        },

        warning(message, duration) {
            return this.show(message, 'warning', duration);
        },

        info(message, duration) {
            return this.show(message, 'info', duration);
        }
    };

    // Keyboard Shortcuts
    const KeyboardShortcuts = {
        shortcuts: {},

        init() {
            document.addEventListener('keydown', (e) => this.handleKeydown(e));
            this.registerDefaults();
        },

        register(keys, callback, description) {
            const key = Array.isArray(keys) ? keys.join('+') : keys;
            this.shortcuts[key] = { callback, description };
        },

        registerDefaults() {
            // Global search: Ctrl/Cmd + K
            this.register(['ctrl', 'k'], () => {
                e.preventDefault();
                const searchInput = document.querySelector('.global-search input');
                if (searchInput) {
                    searchInput.focus();
                }
            }, 'Open global search');

            // Theme toggle: Ctrl/Cmd + Shift + T
            this.register(['ctrl', 'shift', 't'], () => {
                e.preventDefault();
                ThemeManager.toggleTheme();
            }, 'Toggle theme');

            // Save: Ctrl/Cmd + S
            this.register(['ctrl', 's'], (e) => {
                e.preventDefault();
                const saveBtn = document.querySelector('button[type="submit"], .btn-save');
                if (saveBtn && !saveBtn.disabled) {
                    saveBtn.click();
                }
            }, 'Save form');
        },

        handleKeydown(e) {
            const keys = [];
            if (e.ctrlKey || e.metaKey) keys.push('ctrl');
            if (e.shiftKey) keys.push('shift');
            if (e.altKey) keys.push('alt');
            keys.push(e.key.toLowerCase());

            const shortcut = this.shortcuts[keys.join('+')];
            if (shortcut) {
                shortcut.callback(e);
            }
        }
    };

    // Global Search
    const GlobalSearch = {
        init() {
            const searchInput = document.querySelector('.global-search input');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => this.handleSearch(e));
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        this.performSearch(e.target.value);
                    }
                });
            }
        },

        handleSearch(e) {
            const query = e.target.value.trim();
            if (query.length >= 2) {
                // Debounce search
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.performSearch(query);
                }, 300);
            }
        },

        async performSearch(query) {
            try {
                const response = await fetch(`/admin/search?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                this.showResults(data);
            } catch (error) {
                console.error('Search error:', error);
            }
        },

        showResults(data) {
            // Implement search results display
            console.log('Search results:', data);
        }
    };

    // Breadcrumb Navigation
    const BreadcrumbManager = {
        init() {
            this.updateBreadcrumb();
        },

        updateBreadcrumb() {
            const breadcrumb = document.querySelector('.breadcrumb');
            if (!breadcrumb) return;

            const path = window.location.pathname.split('/').filter(Boolean);
            const items = path.map((segment, index) => {
                const url = '/' + path.slice(0, index + 1).join('/');
                const label = segment.charAt(0).toUpperCase() + segment.slice(1);
                return { url, label };
            });

            breadcrumb.innerHTML = `
                <li class="breadcrumb-item">
                    <a href="/admin">Dashboard</a>
                </li>
                ${items.map(item => `
                    <li class="breadcrumb-item">
                        <a href="${item.url}">${item.label}</a>
                    </li>
                `).join('')}
            `;
        }
    };

    // Confirmation Dialogs
    const ConfirmationDialog = {
        show(message, onConfirm, onCancel) {
            const modal = document.createElement('div');
            modal.className = 'modal fade show';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Onay Gerekli</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="button" class="btn btn-danger confirm-btn">Onayla</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);

            const confirmBtn = modal.querySelector('.confirm-btn');
            confirmBtn.addEventListener('click', () => {
                if (onConfirm) onConfirm();
                this.hide(modal, backdrop);
            });

            const cancelBtn = modal.querySelector('[data-bs-dismiss="modal"]');
            cancelBtn.addEventListener('click', () => {
                if (onCancel) onCancel();
                this.hide(modal, backdrop);
            });
        },

        hide(modal, backdrop) {
            modal.classList.remove('show');
            backdrop.classList.remove('show');
            setTimeout(() => {
                if (modal.parentNode) modal.parentNode.removeChild(modal);
                if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
            }, 300);
        }
    };

    // Form Auto-save
    const FormAutoSave = {
        init() {
            const forms = document.querySelectorAll('form[data-autosave]');
            forms.forEach(form => {
                this.setupAutoSave(form);
            });
        },

        setupAutoSave(form) {
            const formId = form.id || 'form-' + Date.now();
            const inputs = form.querySelectorAll('input, textarea, select');
            
            // Load saved data
            const saved = localStorage.getItem(`autosave-${formId}`);
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    Object.keys(data).forEach(key => {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            input.value = data[key];
                        }
                    });
                } catch (e) {
                    console.error('Failed to load autosave:', e);
                }
            }

            // Save on input
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    const formData = new FormData(form);
                    const data = {};
                    for (let [key, value] of formData.entries()) {
                        data[key] = value;
                    }
                    localStorage.setItem(`autosave-${formId}`, JSON.stringify(data));
                });
            });

            // Clear on submit
            form.addEventListener('submit', () => {
                localStorage.removeItem(`autosave-${formId}`);
            });
        }
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        ThemeManager.init();
        ToastManager.init();
        KeyboardShortcuts.init();
        GlobalSearch.init();
        BreadcrumbManager.init();
        FormAutoSave.init();

        // Make ToastManager globally available
        window.Toast = ToastManager;
        window.Confirm = ConfirmationDialog;
    });

})();

