'use strict';

$(function () {
    // Limpiar filtros: redirige a la URL base del reporte sin params
    $(document).on('click', '#btn-clear-filters', function () {
        const base = $(this).data('base-url');
        window.location.href = base;
    });

    // Validación: fecha_desde <= fecha_hasta antes de submit
    $('#form-filters').on('submit', function (e) {
        const desde = $('#fecha_desde').val();
        const hasta = $('#fecha_hasta').val();

        if (desde && hasta && desde > hasta) {
            e.preventDefault();
            ToastUtils.warning('La fecha "Desde" no puede ser mayor que "Hasta".');
            return false;
        }
    });

    // Botones de exportación: añaden &export=... al querystring actual
    $(document).on('click', '[data-export]', function () {
        const format = $(this).data('export');
        const url = new URL(window.location.href);
        url.searchParams.set('export', format);
        window.location.href = url.toString();
    });

    // Imprimir
    $(document).on('click', '[data-action="print"]', function () {
        window.print();
    });
});
