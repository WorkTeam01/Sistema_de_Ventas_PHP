$(document).ready(function () {
    // Toggle de visibilidad de contraseña: manejado globalmente por core/password-toggle.js

    // Validación del formulario
    $('#userCreateForm').validate({
        rules: {
            nombres: {
                required: true,
                minlength: 3
            },
            email: {
                required: true,
                email: true,
                remote: {
                    url: BASE_URL + '/users/check-email',
                    type: 'POST',
                    data: {
                        email: function () {
                            return $('#email').val();
                        }
                    }
                }
            },
            rol: {
                required: true
            },
            password_user: {
                required: true,
                minlength: 6
            },
            password_repeat: {
                required: true,
                equalTo: '#password_user'
            }
        },
        messages: {
            nombres: {
                required: 'El nombre del usuario es requerido',
                minlength: 'El nombre debe tener al menos 3 caracteres'
            },
            email: {
                required: 'El correo electrónico es requerido',
                email: 'Por favor, ingresa un correo electrónico válido',
                remote: 'El correo electrónico ya está registrado en el sistema'
            },
            rol: {
                required: 'Debe seleccionar un rol'
            },
            password_user: {
                required: 'La contraseña es requerida',
                minlength: 'La contraseña debe tener al menos 6 caracteres'
            },
            password_repeat: {
                required: 'Debe confirmar la contraseña',
                equalTo: 'Las contraseñas no coinciden'
            }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.input-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        submitHandler: function (form) {
            const $btn = $('#btnCreateUser');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Guardando usuario...', () => {
                    form.submit();
                }, 1000);
            } else {
                form.submit();
            }
        }
    });

    // Listeners de live preview en sidebar
    $('#nombres').on('input', function () {
        $('#preview-nombre').text($(this).val() || 'Nombre del Usuario');
    });

    $('#email').on('input', function () {
        $('#preview-email').text($(this).val() || 'email@ejemplo.com');
    });

    $('#rol').on('change', function () {
        const texto = $(this).find('option:selected').text().trim();
        $('#preview-rol').text(texto || '— Sin rol —');
    });
});
