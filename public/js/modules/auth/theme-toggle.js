$(function () {
    const $btn = $('#themeToggle');
    const $icon = $btn.find('i');

    function applyIcon(theme) {
        $icon.toggleClass('fa-moon', theme !== 'dark').toggleClass('fa-sun', theme === 'dark');
        $btn.attr('aria-pressed', theme === 'dark' ? 'true' : 'false');
        $btn.attr('aria-label', theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
    }

    let theme = document.documentElement.getAttribute('data-theme') || 'light';
    applyIcon(theme);

    $btn.on('click', function () {
        theme = theme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('auth-theme', theme);
        applyIcon(theme);
    });
});
