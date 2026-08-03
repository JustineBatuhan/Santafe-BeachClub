/**
 * sidebar-toggle.js — Responsive hamburger menu for the sidebar.
 * Works for both the Reception Desk (.sidebar / .main-content) and
 * Admin Panel (.admin-sidebar / .admin-main) layouts.
 *
 * - Desktop (> 1024px): sidebar is expanded by default; the hamburger
 *   collapses/expands it, sliding it fully off-canvas and reflowing
 *   the main content to fill the freed space.
 * - Tablet/Mobile (<= 1024px): sidebar is hidden by default and opens
 *   as an overlay above the content, with a dark backdrop.
 * - Clicking outside the open overlay sidebar, or pressing Escape,
 *   closes it.
 */
(function () {
    function init() {
        var sidebar = document.querySelector('.admin-sidebar') || document.querySelector('.sidebar');
        if (!sidebar) return;

        var STORAGE_KEY = 'sbc_sidebar_collapsed';

        function getMainWrap() {
            return document.querySelector('.admin-main') || document.querySelector('.main-content');
        }

        function isMobile() {
            return window.innerWidth <= 1024;
        }

        // Use the hamburger button already rendered in the page header
        var toggleBtn = document.querySelector('.sidebar-toggle-btn');

        // Overlay backdrop (mobile/tablet only)
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        function openSidebar() {
            if (isMobile()) {
                sidebar.classList.add('open');
                overlay.classList.add('active');
            } else {
                sidebar.classList.remove('collapsed');
                var mainWrap = getMainWrap();
                if (mainWrap) mainWrap.classList.remove('main-collapsed');
                try { localStorage.setItem(STORAGE_KEY, 'false'); } catch (e) {}
            }
        }

        function closeSidebar() {
            if (isMobile()) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.add('collapsed');
                var mainWrap = getMainWrap();
                if (mainWrap) mainWrap.classList.add('main-collapsed');
                try { localStorage.setItem(STORAGE_KEY, 'true'); } catch (e) {}
            }
        }

        function toggleSidebar() {
            var isOpenState = isMobile() ? sidebar.classList.contains('open') : !sidebar.classList.contains('collapsed');
            if (isOpenState) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleSidebar();
            });
        }

        // Click outside closes the overlay sidebar on tablet/mobile
        document.addEventListener('click', function (e) {
            if (isMobile() && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && e.target !== toggleBtn && !(toggleBtn && toggleBtn.contains(e.target))) {
                    closeSidebar();
                }
            }
        });

        overlay.addEventListener('click', closeSidebar);

        // Escape key closes the overlay sidebar
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isMobile() && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });

        // Reset mobile-only state when resizing back to desktop, and vice versa
        window.addEventListener('resize', function () {
            if (!isMobile()) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });

        // Restore the desktop collapsed preference
        try {
            if (!isMobile() && localStorage.getItem(STORAGE_KEY) === 'true') {
                sidebar.classList.add('collapsed');
                var mainWrap = getMainWrap();
                if (mainWrap) mainWrap.classList.add('main-collapsed');
            }
        } catch (e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();