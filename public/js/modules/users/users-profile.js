$(function () {
    var $formInfo     = $('#form-info');
    var $formPassword = $('#form-password');
    var checkEmailUrl = $formInfo.data('check-email-url');
    var userId        = $formInfo.data('user-id');

    // ── Validación: Editar perfil ────────────────────────────────────────────
    $formInfo.validate({
        rules: {
            nombres: {
                required: true,
                minlength: 2
            },
            email: {
                required: true,
                email: true,
                remote: {
                    url: checkEmailUrl,
                    type: 'post',
                    data: {
                        id_usuario: function () { return userId; }
                    }
                }
            }
        },
        messages: {
            nombres: {
                required: 'El nombre es obligatorio.',
                minlength: 'El nombre debe tener al menos 2 caracteres.'
            },
            email: {
                required: 'El email es obligatorio.',
                email: 'Ingresa un correo electrónico válido.',
                remote: 'El correo electrónico ya está registrado por otro usuario.'
            }
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        }
    });

    // ── Validación: Cambiar contraseña ───────────────────────────────────────
    $formPassword.validate({
        rules: {
            password_user: {
                required: true,
                minlength: 6
            },
            password_repeat: {
                required: true,
                equalTo: '#password-user-input'
            }
        },
        messages: {
            password_user: {
                required: 'La nueva contraseña es obligatoria.',
                minlength: 'La contraseña debe tener al menos 6 caracteres.'
            },
            password_repeat: {
                required: 'Confirma la nueva contraseña.',
                equalTo: 'Las contraseñas no coinciden.'
            }
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        }
    });

    // ── Toggle visibilidad contraseña ────────────────────────────────────────
    $(document).on('click', '.toggle-password', function () {
        var $btn   = $(this);
        var $input = $btn.closest('.input-group').find('input');
        var $icon  = $btn.find('i');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});
