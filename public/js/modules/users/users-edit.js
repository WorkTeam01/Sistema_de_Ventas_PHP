$(document).ready(function () {
    // Toggle visibilidad de contraseña
    $('#togglePassword').on('click', function () {
        const input = document.getElementById('password_user');
        const icon = this.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    $('#togglePasswordRepeat').on('click', function () {
        const input = document.getElementById('password_repeat');
        const icon = this.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    // Validación del formulario
    $('#userEditForm').validate({
        rules: {
            nombres: {
                required: true,
                minlength: 3
            },
            email: {
                required: true,
                email: true
            },
            rol: {
                required: true
            },
            // Contraseña es opcional: si se llena, debe tener mínimo 6 caracteres
            password_user: {
                minlength: 6
            },
            // Si se llena password_user, repeat debe coincidir
            password_repeat: {
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
                email: 'Por favor, ingresa un correo electrónico válido'
            },
            rol: {
                required: 'Debe seleccionar un rol'
            },
            password_user: {
                minlength: 'La contraseña debe tener al menos 6 caracteres'
            },
            password_repeat: {
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
            const $btn = $(form).find('[type="submit"]');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Actualizando...');

            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Actualizando usuario...', () => {
                    form.submit();
                }, 1000);
            } else {
                form.submit();
            }
        }
    });
});
