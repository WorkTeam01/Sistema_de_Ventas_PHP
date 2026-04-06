$(document).ready(function () {
    // Preview de imagen con reemplazo de la imagen actual
    $('#fileInput').on('change', function (evt) {
        const files = evt.target.files;
        for (let i = 0, f; f = files[i]; i++) {
            if (!f.type.match('image.*')) continue;
            const reader = new FileReader();
            reader.onload = (function (theFile) {
                return function (e) {
                    document.getElementById('currentImage').style.display = 'none';
                    document.getElementById('imagePreview').innerHTML =
                        '<img class="img-thumbnail img-fluid d-block mb-3" src="' + e.target.result + '" width="100%" title="' + escape(theFile.name) + '"/>';
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

    // =============================================
    // Cálculo de margen en tiempo real (sidebar)
    // =============================================
    function calcularMargen() {
        const compra   = parseFloat($('#precio_compra').val()) || 0;
        const venta    = parseFloat($('#precio_venta').val())  || 0;
        const ganancia = venta - compra;
        const margen   = compra > 0 ? (ganancia / compra) * 100 : 0;

        $('#resumenPrecioCompra').text('$ ' + compra.toFixed(2));
        $('#resumenPrecioVenta').text('$ '  + venta.toFixed(2));
        $('#resumenGanancia').text('$ '     + ganancia.toFixed(2));

        const $badge = $('#badgeMargen');
        $badge.text(margen.toFixed(2) + '%');
        $badge.removeClass('badge-success badge-warning badge-danger badge-secondary');

        if (compra <= 0 || venta <= 0) {
            $badge.addClass('badge-secondary');
        } else if (margen >= 20) {
            $badge.addClass('badge-success');
        } else if (margen >= 10) {
            $badge.addClass('badge-warning');
        } else {
            $badge.addClass('badge-danger');
        }
    }

    $('#precio_compra, #precio_venta').on('input', calcularMargen);
    calcularMargen();
});
