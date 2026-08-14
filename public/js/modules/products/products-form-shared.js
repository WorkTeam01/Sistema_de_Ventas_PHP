/**
 * Reglas de validación y cálculo de margen compartidos entre
 * el formulario de creación y el de edición de productos.
 */
const ProductFormShared = {
    validationRules: {
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

    validationMessages: {
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

    /**
     * Enfoca el primer campo inválido tras un intento de envío fallido,
     * incluyendo selects mejorados por Select2.
     */
    invalidHandler: function (event, validator) {
        const errors = validator.numberOfInvalids();
        if (errors === 0) return;

        const $firstInvalid = $(validator.errorList[0].element);
        if ($firstInvalid.hasClass('select2-hidden-accessible')) {
            $firstInvalid.select2('open');
        } else {
            $firstInvalid.focus();
        }
    },

    /**
     * Enlaza el cálculo de margen en tiempo real al card #resumenCard.
     */
    initMargenCalculator: function () {
        const currency = $('#resumenCard').data('currency') || '$';

        function calcularMargen() {
            const compra = parseFloat($('#precio_compra').val()) || 0;
            const venta = parseFloat($('#precio_venta').val()) || 0;
            const ganancia = venta - compra;
            const margen = compra > 0 ? (ganancia / compra) * 100 : 0;

            $('#resumenPrecioCompra').text(currency + ' ' + compra.toFixed(2));
            $('#resumenPrecioVenta').text(currency + ' ' + venta.toFixed(2));
            $('#resumenGanancia').text(currency + ' ' + ganancia.toFixed(2));

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
    }
};
