$(function () {
    const $formInfo = $('#form-info');
    const $formPassword = $('#form-password');

    // La contraseña actual solo se pide cuando el correo cambia.
    const $email = $('#email');
    const $emailPasswordGroup = $('#email-password-group');
    $email.on('input', function () {
        const changed = $email.val().trim().toLowerCase() !== String($email.data('original')).toLowerCase();
        $emailPasswordGroup.toggleClass('d-none', !changed);
    });

    // ── Validación: Editar perfil ────────────────────────────────────────────
    $formInfo.validate({
        rules: {
            password_current_email: {
                required: function () {
                    return !$emailPasswordGroup.hasClass('d-none');
                }
            },
            nombres: {
                required: true,
                minlength: 2
            },
            email: {
                required: true,
                email: true
            }
        },
        messages: {
            password_current_email: {
                required: 'Ingresa tu contraseña actual para cambiar el correo.'
            },
            nombres: {
                required: 'El nombre es obligatorio.',
                minlength: 'El nombre debe tener al menos 2 caracteres.'
            },
            email: {
                required: 'El email es obligatorio.',
                email: 'Ingresa un correo electrónico válido.'
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
            password_current: {
                required: true
            },
            password_user: {
                required: true,
                minlength: 8
            },
            password_repeat: {
                required: true,
                equalTo: '#password-user-input'
            }
        },
        messages: {
            password_current: {
                required: 'Ingresa tu contraseña actual.'
            },
            password_user: {
                required: 'La nueva contraseña es obligatoria.',
                minlength: 'La contraseña debe tener al menos 8 caracteres.'
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

    // Toggle de visibilidad de contraseña: manejado globalmente por core/password-toggle.js
});
