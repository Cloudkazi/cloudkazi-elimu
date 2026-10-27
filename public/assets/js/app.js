/**
 * CloudKazi Elimu — Main App JS
 */
(function () {
  'use strict';

  // ---- Sidebar toggle (mobile) ----
  const sidebar = document.querySelector('.sidebar');
  const mobileToggle = document.getElementById('mobile-menu-toggle');
  if (mobileToggle && sidebar) {
    mobileToggle.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));
    document.addEventListener('click', (e) => {
      if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
        sidebar.classList.remove('mobile-open');
      }
    });
  }

  // ---- Nav sub-menu toggling ----
  document.querySelectorAll('.nav-toggle').forEach(toggle => {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      const subId = this.getAttribute('data-sub');
      const sub = subId ? document.getElementById(subId) : this.nextElementSibling;
      if (!sub) return;

      const isOpen = sub.classList.contains('open');

      // Close all other open submenus
      document.querySelectorAll('.nav-sub.open').forEach(s => {
        if (s !== sub) s.classList.remove('open');
      });
      document.querySelectorAll('.nav-toggle.open').forEach(t => {
        if (t !== this) t.classList.remove('open');
      });

      // Toggle current submenu
      if (isOpen) {
        sub.classList.remove('open');
        this.classList.remove('open');
      } else {
        sub.classList.add('open');
        this.classList.add('open');
      }
    });
  });

  // Auto-open sub-menu for active item
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-item, .nav-sub a').forEach(link => {
    const href = link.getAttribute('href');
    if (href && href !== '#' && (currentPath === href || (href !== '/' && currentPath.startsWith(href)))) {
      link.classList.add('active');
      const sub = link.closest('.nav-sub');
      if (sub) {
        sub.classList.add('open');
        const toggle = document.querySelector(`[data-sub="${sub.id}"]`) || sub.previousElementSibling;
        if (toggle) toggle.classList.add('open');
      }
    }
  });

  // ---- Toast notifications ----
  window.showToast = function (message, type = 'info', duration = 3500) {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span style="font-size:18px">${icons[type] || icons.info}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(110%)'; toast.style.transition = '0.3s ease'; setTimeout(() => toast.remove(), 350); }, duration);
  };

  // ---- Flash alert auto-dismiss ----
  document.querySelectorAll('.alert[data-dismiss]').forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0'; alert.style.maxHeight = '0'; alert.style.padding = '0'; alert.style.overflow = 'hidden'; alert.style.transition = 'all 0.4s ease';
      setTimeout(() => alert.remove(), 420);
    }, 4500);
  });

  // ---- Confirm delete dialogs ----
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Are you sure you want to delete this record?')) e.preventDefault();
    });
  });

  // ---- Attendance: mark all ----
  document.querySelectorAll('.mark-all-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const val = this.dataset.val;
      document.querySelectorAll(`input[type="radio"][value="${val}"]`).forEach(r => { r.checked = true; });
    });
  });

  // ---- Fee calculator ----
  const feeAmtInput = document.getElementById('fee_amount');
  const feeSummary = document.getElementById('fee_summary');
  if (feeAmtInput && feeSummary) {
    feeAmtInput.addEventListener('input', function () {
      const val = parseFloat(this.value) || 0;
      feeSummary.textContent = 'KES ' + val.toLocaleString('en-KE', { minimumFractionDigits: 2 });
    });
  }

  // ---- Table search (client-side) ----
  const tableSearch = document.getElementById('table-search');
  if (tableSearch) {
    tableSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    });
  }

  // ---- Tab switching ----
  document.querySelectorAll('[data-tab-target]').forEach(btn => {
    btn.addEventListener('click', function () {
      const target = this.dataset.tabTarget;
      const group = this.closest('[data-tab-group]');
      if (group) {
        group.querySelectorAll('[data-tab-target]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
      }
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      const pane = document.getElementById(target);
      if (pane) pane.classList.add('active');
    });
  });

  // ---- Print report ----
  document.querySelectorAll('[data-print]').forEach(btn => {
    btn.addEventListener('click', () => window.print());
  });

  // ---- Timetable cell click (highlight) ----
  document.querySelectorAll('.tt-cell.lesson').forEach(cell => {
    cell.addEventListener('click', function () {
      document.querySelectorAll('.tt-cell.lesson.selected').forEach(c => c.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  // ---- CBC Rubric selector ----
  document.querySelectorAll('.cbc-rubric').forEach(rubric => {
    rubric.addEventListener('click', function () {
      const group = this.closest('.cbc-rubric-group');
      if (group) group.querySelectorAll('.cbc-rubric').forEach(r => r.classList.remove('selected'));
      this.classList.add('selected');
      const hidden = this.closest('td')?.querySelector('input[type="hidden"]');
      if (hidden) hidden.value = this.dataset.val;
    });
  });

  // ---- Image preview on upload ----
  document.querySelectorAll('input[data-preview]').forEach(input => {
    input.addEventListener('change', function () {
      const prev = document.getElementById(this.dataset.preview);
      if (prev && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { prev.src = e.target.result; };
        reader.readAsDataURL(this.files[0]);
      }
    });
  });

  // ---- Number formatting helper ----
  window.formatKES = (n) => 'KES ' + parseFloat(n || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });

  // ---- Select2 / Multiselect fallback styling ----
  document.querySelectorAll('select[multiple]').forEach(sel => {
    sel.style.minHeight = '90px';
  });

  // Duplicate active link logic removed – functionality handled earlier in the script

})();
