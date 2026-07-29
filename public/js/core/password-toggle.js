/**
 * Mostrar/ocultar contraseña. Soporta dos marcados:
 *  - data-toggle-password="<id-del-input>" (vistas de auth)
 *  - .toggle-password dentro del mismo .input-group que el input (resto de módulos)
 */
function togglePasswordVisibility($btn, $input) {
    if (!$input.length) return;

    const isPassword = $input.attr('type') === 'password';
    $input.attr('type', isPassword ? 'text' : 'password');
    $btn.attr('aria-pressed', isPassword ? 'true' : 'false');
    $btn.attr('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    $btn.find('i').toggleClass('fa-eye fa-eye-slash');
}

$(document).on('click', '[data-toggle-password]', function () {
    const $btn = $(this);
    togglePasswordVisibility($btn, $('#' + $btn.data('toggle-password')));
});

$(document).on('click', '.toggle-password', function () {
    const $btn = $(this);
    togglePasswordVisibility($btn, $btn.closest('.input-group').find('input'));
});
