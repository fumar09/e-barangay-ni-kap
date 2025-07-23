/**
 * Custom JavaScript for e-Barangay ni Kap
 * Created: July 15, 2025
 */

// Global variables
const APP_CONFIG = {
    baseUrl: window.location.origin + '/e-Barangay-ni-Kap',
    apiUrl: window.location.origin + '/e-Barangay-ni-Kap/api',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
};

// Utility functions
const Utils = {
    // Show loading spinner
    showLoading: function(container = 'body') {
        const spinner = `
            <div class="loading-overlay">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        $(container).append(spinner);
    },

    // Hide loading spinner
    hideLoading: function() {
        $('.loading-overlay').remove();
    },

    // Show success message
    showSuccess: function(message, duration = 3000) {
        this.showAlert('success', message, duration);
    },

    // Show error message
    showError: function(message, duration = 5000) {
        this.showAlert('danger', message, duration);
    },

    // Show warning message
    showWarning: function(message, duration = 4000) {
        this.showAlert('warning', message, duration);
    },

    // Show info message
    showInfo: function(message, duration = 3000) {
        this.showAlert('info', message, duration);
    },

    // Show alert message
    showAlert: function(type, message, duration) {
        const alertId = 'alert-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Add to page
        if ($('.alert-container').length) {
            $('.alert-container').append(alertHtml);
        } else {
            $('main').prepend(alertHtml);
        }

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                $(`#${alertId}`).fadeOut(() => {
                    $(`#${alertId}`).remove();
                });
            }, duration);
        }
    },

    // Format date
    formatDate: function(date, format = 'YYYY-MM-DD') {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        
        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day);
    },

    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    },

    // Validate email
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Validate phone number
    validatePhone: function(phone) {
        const re = /^(\+63|0)9\d{9}$/;
        return re.test(phone);
    },

    // Generate random string
    randomString: function(length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
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

// Form handling
const FormHandler = {
    // Initialize form validation
    initValidation: function() {
        $('.needs-validation').each(function() {
            $(this).on('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                $(this).addClass('was-validated');
            });
        });
    },

    // Reset form
    resetForm: function(formSelector) {
        $(formSelector)[0].reset();
        $(formSelector).removeClass('was-validated');
        $(formSelector + ' .is-invalid').removeClass('is-invalid');
        $(formSelector + ' .is-valid').removeClass('is-valid');
    },

    // Clear form errors
    clearErrors: function(formSelector) {
        $(formSelector + ' .is-invalid').removeClass('is-invalid');
        $(formSelector + ' .invalid-feedback').remove();
    },

    // Show form errors
    showErrors: function(formSelector, errors) {
        this.clearErrors(formSelector);
        
        Object.keys(errors).forEach(field => {
            const input = $(formSelector + ` [name="${field}"]`);
            input.addClass('is-invalid');
            
            const errorMessage = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
            input.after(`<div class="invalid-feedback">${errorMessage}</div>`);
        });
    }
};

// AJAX utilities
const AjaxHandler = {
    // Make AJAX request
    request: function(options) {
        const defaults = {
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const settings = $.extend({}, defaults, options);

        // Add CSRF token if available
        if (APP_CONFIG.csrfToken) {
            settings.headers['X-CSRF-TOKEN'] = APP_CONFIG.csrfToken;
        }

        return $.ajax(settings);
    },

    // GET request
    get: function(url, data = {}, options = {}) {
        return this.request({
            url: url,
            type: 'GET',
            data: data,
            ...options
        });
    },

    // POST request
    post: function(url, data = {}, options = {}) {
        return this.request({
            url: url,
            type: 'POST',
            data: data,
            ...options
        });
    },

    // PUT request
    put: function(url, data = {}, options = {}) {
        return this.request({
            url: url,
            type: 'PUT',
            data: data,
            ...options
        });
    },

    // DELETE request
    delete: function(url, data = {}, options = {}) {
        return this.request({
            url: url,
            type: 'DELETE',
            data: data,
            ...options
        });
    }
};

// Table utilities
const TableHandler = {
    // Initialize DataTable
    initDataTable: function(selector, options = {}) {
        const defaults = {
            responsive: true,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No matching records found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
        };

        return $(selector).DataTable($.extend({}, defaults, options));
    },

    // Export table to CSV
    exportToCSV: function(selector, filename = 'export.csv') {
        const table = $(selector)[0];
        const csv = [];
        const rows = table.querySelectorAll('tr');

        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            for (let j = 0; j < cols.length; j++) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
            csv.push(row.join(','));
        }

        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
};

// File upload utilities
const FileHandler = {
    // Validate file type
    validateFileType: function(file, allowedTypes) {
        const extension = file.name.split('.').pop().toLowerCase();
        return allowedTypes.includes(extension);
    },

    // Validate file size
    validateFileSize: function(file, maxSize) {
        return file.size <= maxSize;
    },

    // Preview image
    previewImage: function(input, previewSelector) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewSelector).attr('src', e.target.result);
            };
            reader.readAsDataURL(input.files[0]);
        }
    },

    // Upload file via AJAX
    uploadFile: function(file, url, options = {}) {
        const formData = new FormData();
        formData.append('file', file);

        const defaults = {
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false
        };

        return $.ajax($.extend({}, defaults, options));
    }
};

// Chart utilities
const ChartHandler = {
    // Create line chart
    createLineChart: function(canvasId, data, options = {}) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const defaults = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        return new Chart(ctx, $.extend(true, {}, defaults, options));
    },

    // Create bar chart
    createBarChart: function(canvasId, data, options = {}) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const defaults = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        return new Chart(ctx, $.extend(true, {}, defaults, options));
    },

    // Create pie chart
    createPieChart: function(canvasId, data, options = {}) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const defaults = {
            type: 'pie',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        };

        return new Chart(ctx, $.extend(true, {}, defaults, options));
    }
};

// Modal utilities
const ModalHandler = {
    // Show modal
    show: function(modalId) {
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();
    },

    // Hide modal
    hide: function(modalId) {
        const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
        if (modal) {
            modal.hide();
        }
    },

    // Show confirmation modal
    confirm: function(message, callback) {
        const modalId = 'confirmModal';
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal
        $(`#${modalId}`).remove();
        
        // Add new modal
        $('body').append(modalHtml);
        
        // Show modal
        this.show(modalId);
        
        // Handle confirm button
        $(`#${modalId} #confirmBtn`).on('click', function() {
            this.hide(modalId);
            if (callback) callback();
        }.bind(this));
    }
};

// Initialize when document is ready
$(document).ready(function() {
    // Initialize form validation
    FormHandler.initValidation();

    // Initialize tooltips - centralized for all pages
    function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    }
    
    // Auto-initialize tooltips when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeTooltips();
    });

// Common Functions - Eliminates duplicate code across pages

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);

    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 800);
        }
    });

    // Scroll to top functionality
    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            $('#scrollToTop').addClass('show');
        } else {
            $('#scrollToTop').removeClass('show');
        }
    });

    $('#scrollToTop').on('click', function() {
        $('html, body').animate({scrollTop: 0}, 800);
    });

    // File input preview
    $('input[type="file"]').on('change', function() {
        const file = this.files[0];
        const preview = $(this).data('preview');
        
        if (file && preview) {
            FileHandler.previewImage(this, preview);
        }
    });

    // Form reset buttons
    $('.btn-reset').on('click', function() {
        const form = $(this).closest('form');
        FormHandler.resetForm(form);
    });

    // Print functionality
    $('.btn-print').on('click', function() {
        window.print();
    });

    // Export functionality
    $('.btn-export-csv').on('click', function() {
        const table = $(this).data('table');
        const filename = $(this).data('filename') || 'export.csv';
        TableHandler.exportToCSV(table, filename);
    });
});

// Global error handler for AJAX requests
$(document).ajaxError(function(event, xhr, settings, error) {
    console.error('AJAX Error:', error);
    
    if (xhr.status === 401) {
        Utils.showError('Session expired. Please login again.');
        setTimeout(() => {
            window.location.href = APP_CONFIG.baseUrl + '/auth/login.php';
        }, 2000);
    } else if (xhr.status === 403) {
        Utils.showError('Access denied. You do not have permission to perform this action.');
    } else if (xhr.status === 404) {
        Utils.showError('Resource not found.');
    } else if (xhr.status === 500) {
        Utils.showError('Server error. Please try again later.');
    } else {
        Utils.showError('An error occurred. Please try again.');
    }
});

// Global success handler for AJAX requests
$(document).ajaxSuccess(function(event, xhr, settings) {
    // Handle success responses
    if (xhr.responseJSON && xhr.responseJSON.message) {
        Utils.showSuccess(xhr.responseJSON.message);
    }
});

// Export utilities to global scope
window.Utils = Utils;
window.FormHandler = FormHandler;
window.AjaxHandler = AjaxHandler;
window.TableHandler = TableHandler;
window.FileHandler = FileHandler;
window.ChartHandler = ChartHandler;
window.ModalHandler = ModalHandler; 