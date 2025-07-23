/**
 * Phase 2 JavaScript - Resident Services Module
 * e-Barangay ni Kap
 * Created: July 21, 2025
 */

// Phase 2 Global Variables
const PHASE2_CONFIG = {
    maxFileSize: 5 * 1024 * 1024, // 5MB
    allowedFileTypes: ['pdf', 'doc', 'docx'],
    animationDuration: 600,
    tooltipDelay: 100
};

// Phase 2 Utility Functions
const Phase2Utils = {
    /**
     * Format file size for display
     */
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    /**
     * Validate file type
     */
    validateFileType: function(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        return PHASE2_CONFIG.allowedFileTypes.includes(extension);
    },

    /**
     * Validate file size
     */
    validateFileSize: function(file) {
        return file.size <= PHASE2_CONFIG.maxFileSize;
    },

    /**
     * Show notification
     */
    showNotification: function(message, type = 'info') {
        const alertClass = type === 'error' ? 'alert-danger' : 
                          type === 'success' ? 'alert-success' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 
                                  type === 'success' ? 'check-circle' : 
                                  type === 'warning' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert at the top of main content
        const mainContent = document.querySelector('.main-content .container');
        if (mainContent) {
            mainContent.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const alert = mainContent.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    },

    /**
     * Animate element on scroll
     */
    animateOnScroll: function(element, animationClass = 'animate-in') {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add(animationClass);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        observer.observe(element);
    },

    /**
     * Initialize scroll animations
     */
    initScrollAnimations: function() {
        const elements = document.querySelectorAll('.certificate-form-card, .info-card, .request-status-card, .search-form-card');
        elements.forEach(element => {
            this.animateOnScroll(element);
        });
    }
};

// Certificate Request Form Handler
const CertificateRequestForm = {
    /**
     * Initialize form validation and handlers
     */
    init: function() {
        const form = document.getElementById('certificateForm');
        if (!form) return;

        // File input validation
        const fileInput = document.getElementById('supporting_documents');
        if (fileInput) {
            fileInput.addEventListener('change', this.handleFileChange.bind(this));
        }

        // Form submission
        form.addEventListener('submit', this.handleSubmit.bind(this));

        // Real-time validation
        this.initRealTimeValidation();
    },

    /**
     * Handle file input change
     */
    handleFileChange: function(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        if (!Phase2Utils.validateFileType(file)) {
            Phase2Utils.showNotification('Invalid file type. Please upload PDF, DOC, or DOCX files only.', 'error');
            event.target.value = '';
            return;
        }

        // Validate file size
        if (!Phase2Utils.validateFileSize(file)) {
            Phase2Utils.showNotification(`File size exceeds ${Phase2Utils.formatFileSize(PHASE2_CONFIG.maxFileSize)} limit.`, 'error');
            event.target.value = '';
            return;
        }

        // Show success message
        Phase2Utils.showNotification(`File "${file.name}" selected successfully.`, 'success');
    },

    /**
     * Handle form submission
     */
    handleSubmit: function(event) {
        const form = event.target;
        const certificateType = form.querySelector('#certificate_type').value;
        const purpose = form.querySelector('#purpose').value.trim();

        // Validate required fields
        if (!certificateType) {
            event.preventDefault();
            Phase2Utils.showNotification('Please select a certificate type.', 'error');
            return;
        }

        if (!purpose) {
            event.preventDefault();
            Phase2Utils.showNotification('Please specify the purpose of your request.', 'error');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        }
    },

    /**
     * Initialize real-time validation
     */
    initRealTimeValidation: function() {
        const purposeField = document.getElementById('purpose');
        if (purposeField) {
            purposeField.addEventListener('input', function() {
                const value = this.value.trim();
                const minLength = 10;
                
                if (value.length > 0 && value.length < minLength) {
                    this.classList.add('is-invalid');
                    this.setCustomValidity(`Purpose must be at least ${minLength} characters long.`);
                } else {
                    this.classList.remove('is-invalid');
                    this.setCustomValidity('');
                }
            });
        }
    }
};

// Request Tracking Handler
const RequestTracking = {
    /**
     * Initialize tracking functionality
     */
    init: function() {
        this.initSearchForm();
        this.initRequestCards();
    },

    /**
     * Initialize search form
     */
    initSearchForm: function() {
        const searchType = document.getElementById('search_type');
        const searchValue = document.getElementById('search_value');
        
        if (searchType && searchValue) {
            searchType.addEventListener('change', function() {
                if (this.value === 'all') {
                    searchValue.value = '';
                    searchValue.placeholder = 'All requests will be shown';
                    searchValue.disabled = true;
                } else {
                    searchValue.disabled = false;
                    searchValue.placeholder = 'Enter search value...';
                }
            });

            // Initialize on page load
            if (searchType.value === 'all') {
                searchValue.disabled = true;
                searchValue.placeholder = 'All requests will be shown';
            }
        }
    },

    /**
     * Initialize request cards
     */
    initRequestCards: function() {
        const requestCards = document.querySelectorAll('.request-status-card');
        requestCards.forEach(card => {
            // Add hover effects
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    },

    /**
     * View request details
     */
    viewRequestDetails: function(requestId) {
        // This would typically make an AJAX call
        const modalBody = document.getElementById('requestModalBody');
        if (modalBody) {
            modalBody.innerHTML = `
                <div class="request-details">
                    <h6>Request Information</h6>
                    <div class="detail-item">
                        <span class="detail-label">Request ID:</span>
                        <span class="detail-value">#${requestId}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">Processing</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Submitted:</span>
                        <span class="detail-value">Today</span>
                    </div>
                </div>
                <p class="text-muted">Detailed information will be loaded here.</p>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('requestModal'));
            modal.show();
        }
    },

    /**
     * Download certificate
     */
    downloadCertificate: function(requestId) {
        Phase2Utils.showNotification('Certificate download functionality will be implemented in the next phase.', 'info');
    }
};

// Admin Processing Handler
const AdminProcessing = {
    /**
     * Initialize admin processing functionality
     */
    init: function() {
        this.initProcessingTable();
        this.initModals();
    },

    /**
     * Initialize processing table
     */
    initProcessingTable: function() {
        const table = document.querySelector('.processing-table');
        if (table) {
            // Add row hover effects
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(3, 89, 182, 0.05)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
        }
    },

    /**
     * Initialize modals
     */
    initModals: function() {
        // Process modal
        const processModal = document.getElementById('processModal');
        if (processModal) {
            processModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const requestId = button.getAttribute('data-request-id');
                const action = button.getAttribute('data-action');
                
                if (requestId && action) {
                    document.getElementById('processRequestId').value = requestId;
                    document.getElementById('processAction').value = action;
                    
                    const actionText = action.charAt(0).toUpperCase() + action.slice(1);
                    document.getElementById('processMessage').textContent = `Are you sure you want to ${action} this request?`;
                    document.getElementById('processSubmitBtn').textContent = actionText;
                }
            });
        }
    },

    /**
     * Process request
     */
    processRequest: function(requestId, action) {
        document.getElementById('processRequestId').value = requestId;
        document.getElementById('processAction').value = action;
        
        const actionText = action.charAt(0).toUpperCase() + action.slice(1);
        document.getElementById('processMessage').textContent = `Are you sure you want to ${action} this request?`;
        document.getElementById('processSubmitBtn').textContent = actionText;
        
        const modal = new bootstrap.Modal(document.getElementById('processModal'));
        modal.show();
    },

    /**
     * View request details
     */
    viewRequestDetails: function(requestId) {
        const modalBody = document.getElementById('requestModalBody');
        if (modalBody) {
            modalBody.innerHTML = `
                <div class="request-details">
                    <h6>Request Information</h6>
                    <div class="detail-item">
                        <span class="detail-label">Request ID:</span>
                        <span class="detail-value">#${requestId}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">Pending</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Submitted:</span>
                        <span class="detail-value">Today</span>
                    </div>
                </div>
                <p class="text-muted">Detailed information will be loaded here.</p>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('requestModal'));
            modal.show();
        }
    },

    /**
     * View request audit trail
     */
    viewRequestHistory: function(requestId) {
        const modalBody = document.getElementById('historyModalBody');
        if (modalBody) {
            // Show loading state
            modalBody.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading audit trail...</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();
            
            // Fetch audit trail data
            fetch(`?view_history=${requestId}&ajax=1`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading audit trail:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading audit trail. Please try again.
                        </div>
                    `;
                });
        }
    }
};

// Phase 2 Initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: PHASE2_CONFIG.tooltipDelay }
        });
    });

    // Initialize scroll animations
    Phase2Utils.initScrollAnimations();

    // Initialize based on page type
    if (document.querySelector('.certificate-request-page')) {
        CertificateRequestForm.init();
    }
    
    if (document.querySelector('.request-tracking-page')) {
        RequestTracking.init();
    }
    
    if (document.querySelector('.admin-processing-page')) {
        AdminProcessing.init();
    }

    // Global error handling
    window.addEventListener('error', function(event) {
        console.error('Phase 2 Error:', event.error);
        Phase2Utils.showNotification('An error occurred. Please try again.', 'error');
    });

    // Performance monitoring
    if ('performance' in window) {
        window.addEventListener('load', function() {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log(`Phase 2 page loaded in ${loadTime}ms`);
        });
    }
});

// Global functions for admin processing
window.processRequest = function(requestId, action) {
    AdminProcessing.processRequest(requestId, action);
};

window.viewRequestDetails = function(requestId) {
    AdminProcessing.viewRequestDetails(requestId);
};

window.viewRequestHistory = function(requestId) {
    AdminProcessing.viewRequestHistory(requestId);
};

// Export for global access
window.Phase2Utils = Phase2Utils;
window.CertificateRequestForm = CertificateRequestForm;
window.RequestTracking = RequestTracking;
window.AdminProcessing = AdminProcessing; 