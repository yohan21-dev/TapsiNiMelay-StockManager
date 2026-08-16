    <footer class="footer">Tapsi Business — Stock Management System</footer>
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
</script>
</body>
</html>
