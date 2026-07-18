$(document).ready(function () {
    // 1. Manejar la visibilidad de la contraseña
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password_user');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.setAttribute('aria-pressed', type === 'text' ? 'true' : 'false');
            this.setAttribute('aria-label', type === 'text' ? 'Ocultar contraseña' : 'Mostrar contraseña');
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    }

    // 2. Configuración de jQuery Validate
    $('#loginForm').validate({
        rules: {
            email: {
                required: true,
                email: true
            },
            password_user: {
                required: true
            }
        },
        messages: {
            email: {
                required: 'El correo electrónico es requerido',
                email: 'Por favor, ingresa un correo electrónico válido'
            },
            password_user: {
                required: 'La contraseña es requerida'
            }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.input-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid');
            $(element).removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
            $(element).addClass('is-valid');
        },
        submitHandler: function (form) {
            const $submitBtn = $('#btnLogin');
            const originalText = $submitBtn.html();

            // Deshabilitar el botón y mostrar spinner
            $submitBtn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin mr-2"></i>Ingresando...');

            // Mostrar loading toast con tiempo mínimo
            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Iniciando sesión...', () => {
                    form.submit();
                }, 1500);
            } else {
                setTimeout(() => {
                    form.submit();
                }, 1500);
            }

            // Restaurar botón en caso de error (por si el submit no redirige)
            setTimeout(() => {
                $submitBtn.prop('disabled', false).html(originalText);
            }, 5000);
        }
    });
});
