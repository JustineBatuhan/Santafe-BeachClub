/**
 * dark-mode-toggle.js — Shared dark/light mode toggle for Admin & Reception panels.
 * Persists preference in localStorage under 'sbc_dark_mode'.
 * Applies [data-theme="dark"] to <html> element.
 */
(function () {
    var STORAGE_KEY = 'sbc_dark_mode';

    // Apply theme immediately on load to avoid flash of wrong theme
    function applyTheme(isDark) {
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    }

    // Check saved preference
    var saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'true') {
        applyTheme(true);
    } else {
        applyTheme(false);
    }

    // Toggle and update all buttons on the page
    function toggleDarkMode() {
        var current = document.documentElement.getAttribute('data-theme') === 'dark';
        var newState = !current;
        applyTheme(newState);
        localStorage.setItem(STORAGE_KEY, newState ? 'true' : 'false');
        updateButtons();
    }

    function updateButtons() {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.querySelectorAll('.dark-mode-btn').forEach(function (btn) {
            // Sun icon for dark mode (click to go light), Moon icon for light mode (click to go dark)
            btn.innerHTML = isDark
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
            btn.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
            btn.setAttribute('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Attach click handler to all dark mode buttons
        document.querySelectorAll('.dark-mode-btn').forEach(function (btn) {
            btn.addEventListener('click', toggleDarkMode);
        });
        // Set correct icon immediately
        updateButtons();
    });
})();
