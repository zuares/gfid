{{-- resources/views/layouts/partials/theme-script.blade.php --}}
<script>
    (function() {
        const root = document.documentElement;
        const btn = document.getElementById('themeToggleBtn');
        const icon = document.getElementById('themeToggleIcon');
        const label = document.getElementById('themeToggleLabel');
        const storageKey = 'gfid_theme';

        function applyTheme(theme) {
            if (!['light', 'dark'].includes(theme)) {
                theme = 'light';
            }

            root.setAttribute('data-theme', theme);

            if (theme === 'dark') {
                if (icon) icon.textContent = '☀️';
                if (label) label.textContent = 'Mode Terang';
            } else {
                if (icon) icon.textContent = '🌙';
                if (label) label.textContent = 'Mode Gelap';
            }

            try {
                localStorage.setItem(storageKey, theme);
            } catch (e) {
                // abaikan kalau localStorage tidak tersedia
            }
        }

        function initTheme() {
            let savedTheme = null;
            try {
                savedTheme = localStorage.getItem(storageKey);
            } catch (e) {}

            if (savedTheme === 'light' || savedTheme === 'dark') {
                applyTheme(savedTheme);
                return;
            }

            // default: ikut OS
            if (window.matchMedia &&
                window.matchMedia('(prefers-color-scheme: dark)').matches) {
                applyTheme('dark');
            } else {
                applyTheme('light');
            }
        }

        initTheme();

        if (btn) {
            btn.addEventListener('click', function() {
                const current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                applyTheme(next);
            });
        }
    })();
</script>
