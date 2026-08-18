<footer class="footer">Tapsi ni Melay</footer>
</main>

<script>
    (function () {
        const THEME_KEY = 'tapsiStockTheme';
        const themeButton = document.getElementById('themeButton');

        function applyTheme(theme) {
            document.body.classList.toggle('dark', theme === 'dark');
            if (themeButton) themeButton.textContent = theme === 'dark' ? '☀️' : '🌙';
        }

        applyTheme(localStorage.getItem(THEME_KEY) || 'light');

        if (themeButton) {
            themeButton.addEventListener('click', function () {
                const isDark = document.body.classList.toggle('dark');
                localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
                themeButton.textContent = isDark ? '☀️' : '🌙';
            });
        }
    })();

    (function () {
        // Hamburger menu toggle for the main navigation
        const navToggle = document.getElementById('navToggle');
        const mainNav = document.getElementById('mainNav');
        if (!navToggle || !mainNav) return;

        function closeNav() {
            mainNav.classList.remove('nav-open');
            navToggle.setAttribute('aria-expanded', 'false');
        }

        function toggleNav() {
            const isOpen = mainNav.classList.toggle('nav-open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        navToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleNav();
        });

        // Close the menu after choosing a link (mobile)
        mainNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeNav);
        });

        // Close when tapping/clicking outside the menu
        document.addEventListener('click', function (event) {
            if (!mainNav.classList.contains('nav-open')) return;
            if (mainNav.contains(event.target) || navToggle.contains(event.target)) return;
            closeNav();
        });

        // Close automatically if the window is resized back to desktop width
        window.addEventListener('resize', function () {
            if (window.innerWidth > 700) closeNav();
        });
    })();
</script>
</body>
</html>