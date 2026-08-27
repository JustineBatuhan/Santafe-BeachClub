// Root redirect for dark-mode-toggle.js
importScripts ? null : null;
// Load assets/js/dark-mode-toggle.js
var s = document.createElement('script');
s.src = 'assets/js/dark-mode-toggle.js?v=3';
document.head.appendChild(s);
