/**
 * ============================================================================
 * GESTIÓN DE COMPRAS - Validación formulario de creación
 * ============================================================================
 */

$(document).ready(function () {
    $('#purchaseCreateForm').validate({
        rules: {
            fecha_compra: {
                required: true
            },
            comprobante: {
                required: true,
                minlength: 3
            },
            id_producto: {
                required: true
            },
            id_proveedor: {
                required: true
            },
            precio_compra: {
                required: true,
                number: true,
                min: 0.01
            },
            cantidad: {
                required: true,
                digits: true,
                min: 1
            }
        },
        messages: {
            fecha_compra: {
                required: 'La fecha de compra es requerida'
            },
            comprobante: {
                required: 'El comprobante es requerido',
                minlength: 'El comprobante debe tener al menos 3 caracteres'
            },
            id_producto: {
                required: 'Debe seleccionar un producto'
            },
            id_proveedor: {
                required: 'Debe seleccionar un proveedor'
            },
            precio_compra: {
                required: 'El precio de compra es requerido',
                number: 'Ingrese un precio válido',
                min: 'El precio debe ser mayor a 0'
            },
            cantidad: {
                required: 'La cantidad es requerida',
                digits: 'La cantidad debe ser un número entero',
                min: 'La cantidad debe ser al menos 1'
            }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            if (element.closest('.d-flex').length) {
                element.closest('.d-flex').after(error);
            } else {
                element.closest('.form-group').append(error);
            }
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        submitHandler: function (form) {
            const $btn = $(form).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Guardando compra...', function () {
                    form.submit();
                }, 1000);
            } else {
                form.submit();
            }
        }
    });
});