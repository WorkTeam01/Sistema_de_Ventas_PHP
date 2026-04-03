$(document).ready(function () {
    // Select2 para el selector de categoría
    $('[name="id_categoria"]').select2({
        allowClear: false,
        width: '100%'
    });

    // Preview de imagen con reemplazo de la imagen actual
    $('#fileInput').on('change', function (evt) {
        var files = evt.target.files;
        for (var i = 0, f; f = files[i]; i++) {
            if (!f.type.match('image.*')) continue;
            var reader = new FileReader();
            reader.onload = (function (theFile) {
                return function (e) {
                    document.getElementById('currentImage').style.display = 'none';
                    document.getElementById('imagePreview').innerHTML =
                        '<img class="img-thumbnail img-fluid mt-2 d-block" src="' + e.target.result + '" width="100%" title="' + escape(theFile.name) + '"/>';
                };
            })(f);
            reader.readAsDataURL(f);
        }
    });

    // Validación del formulario
    $('#productEditForm').validate({
        rules: {
            nombre: {
                required: true,
                minlength: 3
            },
            id_categoria: {
                required: true
            },
            stock: {
                required: true,
                number: true,
                min: 0
            },
            stock_minimo: {
                number: true,
                min: 0
            },
            stock_maximo: {
                number: true,
                min: 0
            },
            precio_compra: {
                required: true,
                number: true,
                min: 0.01
            },
            precio_venta: {
                required: true,
                number: true,
                min: 0.01
            },
            fecha_ingreso: {
                required: true
            }
        },
        messages: {
            nombre: {
                required: 'El nombre del producto es requerido',
                minlength: 'El nombre debe tener al menos 3 caracteres'
            },
            id_categoria: {
                required: 'Debe seleccionar una categoría'
            },
            stock: {
                required: 'El stock es requerido',
                number: 'Debe ser un valor numérico',
                min: 'El stock no puede ser negativo'
            },
            stock_minimo: {
                number: 'Debe ser un valor numérico',
                min: 'El stock mínimo no puede ser negativo'
            },
            stock_maximo: {
                number: 'Debe ser un valor numérico',
                min: 'El stock máximo no puede ser negativo'
            },
            precio_compra: {
                required: 'El precio de compra es requerido',
                number: 'Debe ser un valor numérico',
                min: 'El precio debe ser mayor a 0'
            },
            precio_venta: {
                required: 'El precio de venta es requerido',
                number: 'Debe ser un valor numérico',
                min: 'El precio debe ser mayor a 0'
            },
            fecha_ingreso: {
                required: 'La fecha de ingreso es requerida'
            }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        submitHandler: function (form) {
            const $btn = $('#btnEditProduct');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Actualizando producto...', () => {
                    form.submit();
                }, 1000);
            } else {
                form.submit();
            }
        }
    });
});
