$(document).ready(function () {
    // Configuración de jQuery Validate
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
        ...AuthFormUtils.commonValidateCallbacks,
        submitHandler: function (form) {
            AuthFormUtils.handleSubmit(form, {
                btnId: 'btnLogin',
                statusId: 'formStatus',
                loadingLabel: 'Ingresando...',
                loadingToast: 'Iniciando sesión...',
                statusMessage: 'Iniciando sesión, por favor espera...'
            });
        }
    });
});
