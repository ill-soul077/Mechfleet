/**
 * Mechfleet Modern UI - JavaScript
 * Handles sidebar toggle, DataTables, notifications, and form interactions
 */

(function() {
  'use strict';

  // ============================================
  // SIDEBAR TOGGLE
  // ============================================
  const sidebar = document.getElementById('mfSidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      // Save state to localStorage
      localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    });
    
    // Restore sidebar state on page load
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
      sidebar.classList.add('collapsed');
    }
  }

  // Mobile sidebar toggle
  if (window.innerWidth <= 768) {
    if (toggleBtn && sidebar) {
      toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('show');
      });
      
      // Close sidebar when clicking outside
      document.addEventListener('click', function(e) {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
          sidebar.classList.remove('show');
        }
      });
    }
  }

  // ============================================
  // TOASTR CONFIGURATION
  // ============================================
  if (typeof toastr !== 'undefined') {
    toastr.options = {
      closeButton: true,
      debug: false,
      newestOnTop: true,
      progressBar: true,
      positionClass: 'toast-top-right',
      preventDuplicates: false,
      onclick: null,
      showDuration: '300',
      hideDuration: '1000',
      timeOut: '5000',
      extendedTimeOut: '1000',
      showEasing: 'swing',
      hideEasing: 'linear',
      showMethod: 'fadeIn',
      hideMethod: 'fadeOut'
    };
  }

  // ============================================
  // SHOW MESSAGES FROM PHP
  // ============================================
  window.showSuccess = function(message) {
    if (typeof toastr !== 'undefined') {
      toastr.success(message);
    } else {
      alert(message);
    }
  };

  window.showError = function(message) {
    if (typeof toastr !== 'undefined') {
      toastr.error(message);
    } else {
      alert('Error: ' + message);
    }
  };

  window.showWarning = function(message) {
    if (typeof toastr !== 'undefined') {
      toastr.warning(message);
    } else {
      alert(message);
    }
  };

  window.showInfo = function(message) {
    if (typeof toastr !== 'undefined') {
      toastr.info(message);
    } else {
      alert(message);
    }
  };

  // ============================================
  // DATATABLES INITIALIZATION
  // ============================================
  window.initDataTable = function(selector, options) {
    const defaultOptions = {
      responsive: true,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
      pageLength: 25,
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search...",
        lengthMenu: "Show _MENU_ entries",
        info: "Showing _START_ to _END_ of _TOTAL_ entries",
        infoEmpty: "No entries available",
        infoFiltered: "(filtered from _MAX_ total entries)",
        zeroRecords: "No matching records found",
        emptyTable: "No data available in table",
        paginate: {
          first: "First",
          last: "Last",
          next: "Next",
          previous: "Previous"
        }
      },
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
           '<"row"<"col-sm-12"tr>>' +
           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      ...options
    };
    
    if ($.fn.dataTable.isDataTable(selector)) {
      $(selector).DataTable().destroy();
    }
    
    return $(selector).DataTable(defaultOptions);
  };

  // ============================================
  // SWALCONFIRM DELETE
  // ============================================
  window.confirmDelete = function(message, callback) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Are you sure?',
        text: message || "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
          callback();
        }
      });
    } else {
      if (confirm(message || 'Are you sure you want to delete this?')) {
        if (typeof callback === 'function') {
          callback();
        }
      }
    }
  };

  // ============================================
  // FORM VALIDATION
  // ============================================
  window.validateForm = function(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
      if (!input.value.trim()) {
        input.classList.add('is-invalid');
        isValid = false;
      } else {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
      }
      
      // Email validation
      if (input.type === 'email' && input.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value)) {
          input.classList.add('is-invalid');
          isValid = false;
        }
      }
    });
    
    return isValid;
  };

  // Real-time validation
  document.querySelectorAll('input[required], select[required], textarea[required]').forEach(input => {
    input.addEventListener('blur', function() {
      if (!this.value.trim()) {
        this.classList.add('is-invalid');
        this.classList.remove('is-valid');
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      }
    });
  });

  // ============================================
  // LOADING SPINNER
  // ============================================
  window.showLoading = function() {
    if (!document.getElementById('globalLoading')) {
      const loadingDiv = document.createElement('div');
      loadingDiv.id = 'globalLoading';
      loadingDiv.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
      `;
      loadingDiv.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
      document.body.appendChild(loadingDiv);
    }
  };

  window.hideLoading = function() {
    const loading = document.getElementById('globalLoading');
    if (loading) {
      loading.remove();
    }
  };

  // ============================================
  // GLOBAL SEARCH (placeholder)
  // ============================================
  const globalSearch = document.getElementById('globalSearch');
  if (globalSearch) {
    globalSearch.addEventListener('input', function() {
      // This is a placeholder - implement your search logic here
      console.log('Searching for:', this.value);
    });
  }

  // ============================================
  // AUTO-HIDE ALERTS
  // ============================================
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      if (alert && alert.parentNode) {
        alert.classList.add('fade');
        setTimeout(() => alert.remove(), 300);
      }
    }, 5000);
  });

  // ============================================
  // INITIALIZE ON PAGE LOAD
  // ============================================
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Mechfleet Modern UI loaded successfully');
    
    // Add ripple effect to buttons
    document.querySelectorAll('.btn').forEach(button => {
      button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
          position: absolute;
          width: ${size}px;
          height: ${size}px;
          left: ${x}px;
          top: ${y}px;
          background: rgba(255, 255, 255, 0.5);
          border-radius: 50%;
          transform: scale(0);
          animation: ripple 0.6s ease-out;
          pointer-events: none;
        `;
        
        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
      });
    });
  });

  // ============================================
  // HELPER FUNCTIONS
  // ============================================
  
  // Format currency
  window.formatCurrency = function(amount) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  };

  // Format date
  window.formatDate = function(date) {
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    }).format(new Date(date));
  };

})();

// Add CSS animation for ripple effect
const style = document.createElement('style');
style.textContent = `
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
