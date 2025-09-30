/**
 * Kanho Ads Manager v2.0 - Main JavaScript
 */

// Global App Object
window.KanhoAds = {
    config: {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        apiUrl: '/api'
    },
    
    // Initialize application
    init: function() {
        console.log('Kanho Ads Manager v2.0 initialized');
        this.setupGlobalEvents();
        this.setupFormValidation();
        this.setupAjaxDefaults();
        this.setupTooltips();
        this.setupAutoSubmit();
    },
    
    // Setup global event listeners
    setupGlobalEvents: function() {
        // Confirmation dialogs
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-confirm]') || e.target.closest('[data-confirm]')) {
                const element = e.target.matches('[data-confirm]') ? e.target : e.target.closest('[data-confirm]');
                const message = element.getAttribute('data-confirm') || '本当に実行しますか？';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Auto-dismiss alerts
        document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function(alert) {
            const delay = parseInt(alert.getAttribute('data-auto-dismiss')) || 5000;
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, delay);
        });
        
        // Toggle password visibility
        document.addEventListener('click', function(e) {
            if (e.target.matches('.toggle-password') || e.target.closest('.toggle-password')) {
                const button = e.target.matches('.toggle-password') ? e.target : e.target.closest('.toggle-password');
                const input = document.querySelector(button.getAttribute('data-target'));
                const icon = button.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            }
        });
    },
    
    // Setup form validation
    setupFormValidation: function() {
        // Bootstrap form validation
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
        
        // Real-time validation
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[required], select[required], textarea[required]')) {
                const input = e.target;
                const feedback = input.parentNode.querySelector('.invalid-feedback');
                
                if (input.checkValidity()) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                }
            }
        });
    },
    
    // Setup AJAX defaults
    setupAjaxDefaults: function() {
        // jQuery AJAX setup
        if (typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': this.config.csrfToken
                }
            });
        }
        
        // Fetch API defaults
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            options.headers = options.headers || {};
            if (KanhoAds.config.csrfToken) {
                options.headers['X-CSRF-TOKEN'] = KanhoAds.config.csrfToken;
            }
            return originalFetch(url, options);
        };
    },
    
    // Setup tooltips and popovers
    setupTooltips: function() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize Bootstrap popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    },
    
    // Setup auto-submit forms
    setupAutoSubmit: function() {
        document.addEventListener('change', function(e) {
            if (e.target.matches('[data-auto-submit]')) {
                const form = e.target.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    },
    
    // Show loading state
    showLoading: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.classList.add('loading');
            const originalText = element.textContent;
            element.setAttribute('data-original-text', originalText);
            
            if (element.tagName === 'BUTTON') {
                element.disabled = true;
                element.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + originalText;
            }
        }
    },
    
    // Hide loading state
    hideLoading: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.classList.remove('loading');
            const originalText = element.getAttribute('data-original-text');
            
            if (element.tagName === 'BUTTON') {
                element.disabled = false;
                if (originalText) {
                    element.textContent = originalText;
                    element.removeAttribute('data-original-text');
                }
            }
        }
    },
    
    // Show notification
    showNotification: function(message, type = 'info', duration = 5000) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const container = document.querySelector('.notification-container') || 
                         document.querySelector('.container');
        
        if (container) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = alertHtml;
            const alert = tempDiv.firstElementChild;
            
            container.insertBefore(alert, container.firstChild);
            
            // Auto-dismiss
            if (duration > 0) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, duration);
            }
        }
    },
    
    // Format currency
    formatCurrency: function(amount, currency = 'JPY') {
        return new Intl.NumberFormat('ja-JP', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0
        }).format(amount);
    },
    
    // Format date
    formatDate: function(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short', 
            day: 'numeric'
        };
        
        return new Intl.DateTimeFormat('ja-JP', {...defaultOptions, ...options})
                      .format(new Date(date));
    },
    
    // AJAX helper
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.config.csrfToken
            }
        };
        
        return fetch(url, {...defaults, ...options})
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    },
    
    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// Dashboard specific functions
window.KanhoAds.Dashboard = {
    // Update dashboard stats
    updateStats: function() {
        return KanhoAds.ajax('/api/dashboard/stats')
            .then(data => {
                // Update stat cards
                Object.keys(data).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        element.textContent = data[key];
                    }
                });
            })
            .catch(error => {
                console.error('Failed to update dashboard stats:', error);
            });
    },
    
    // Load performance chart
    loadPerformanceChart: function(days = 30) {
        return KanhoAds.ajax(`/api/dashboard/performance?days=${days}`)
            .then(data => {
                // Chart implementation would go here
                console.log('Performance data:', data);
            });
    },
    
    // Load platform chart
    loadPlatformChart: function() {
        return KanhoAds.ajax('/api/dashboard/platforms')
            .then(data => {
                // Chart implementation would go here
                console.log('Platform data:', data);
            });
    }
};

// Client management functions
window.KanhoAds.Clients = {
    // Search clients
    search: function(query) {
        return KanhoAds.ajax(`/api/clients/search?q=${encodeURIComponent(query)}`);
    },
    
    // Get client details
    getClient: function(id) {
        return KanhoAds.ajax(`/api/clients/${id}`);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    KanhoAds.init();
    
    // Initialize dashboard if on dashboard page
    if (document.querySelector('#performanceChart')) {
        KanhoAds.Dashboard.loadPerformanceChart();
        KanhoAds.Dashboard.loadPlatformChart();
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KanhoAds;
}