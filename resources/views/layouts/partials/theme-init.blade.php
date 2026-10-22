{{-- Sets data-theme on <html> before the page paints (no light flash) and exposes ErpTheme.toggle().
     Saved choice (localStorage "erp-theme") wins; without one the device's colour scheme is followed. --}}
<script>
    (function () {
        var KEY = 'erp-theme';
        var root = document.documentElement;
        var media = window.matchMedia('(prefers-color-scheme: dark)');

        function stored() {
            try { return localStorage.getItem(KEY); } catch (e) { return null; }
        }

        function label() {
            var dark = root.dataset.theme === 'dark';
            document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
                var text = (dark ? button.dataset.toLight : button.dataset.toDark) || button.title;

                button.title = text;
                button.setAttribute('aria-label', text);
                button.setAttribute('aria-pressed', dark ? 'true' : 'false');
            });
        }

        function apply() {
            var saved = stored();
            root.dataset.theme = saved === 'dark' || saved === 'light' ? saved : (media.matches ? 'dark' : 'light');
            label();
        }

        window.ErpTheme = {
            toggle: function () {
                try { localStorage.setItem(KEY, root.dataset.theme === 'dark' ? 'light' : 'dark'); } catch (e) {}
                apply();
            },
        };

        if (media.addEventListener) {
            media.addEventListener('change', function () { if (!stored()) apply(); });
        }

        window.addEventListener('storage', function (event) { if (event.key === KEY) apply(); });
        document.addEventListener('DOMContentLoaded', label);

        apply();
    })();
</script>
