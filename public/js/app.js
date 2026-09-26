/* MarketLink front-end helpers (plain JS, no build step) */

/* ---------- Theme (dark mode) toggle ---------- */
(function () {
    var THEME_KEY = 'ml-theme';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        var icon = document.getElementById('themeIcon');
        if (icon) {
            // Moon shown in light mode (click -> dark), sun in dark mode
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    applyTheme(document.documentElement.getAttribute('data-bs-theme') || 'light');

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                localStorage.setItem(THEME_KEY, next);
                applyTheme(next);
            });
        }
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    // Auto-hide flash alerts after a few seconds
    document.querySelectorAll('.alert-auto-hide').forEach(function (el) {
        setTimeout(function () {
            try {
                bootstrap.Alert.getOrCreateInstance(el).close();
            } catch (e) {
                el.classList.add('d-none');
            }
        }, 4500);
    });

    // Confirm dialogs for destructive forms
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
});
