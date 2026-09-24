/**
 * ============================================================================
 * GESTIÓN DE VENTAS - POS Wizard (3 pasos: Cliente → Carrito → Pago)
 * ============================================================================
 */

// ---- Estado del wizard ----

let currentStep = 0;
const steps = ['#pane-cliente', '#pane-carrito', '#pane-pago'];
const tabLinks = ['#tab-cliente-link', '#tab-carrito-link', '#tab-pago-link'];

function goToStep(n) {
    currentStep = Math.max(0, Math.min(n, steps.length - 1));
    $(tabLinks[currentStep]).tab('show');
    updateProgress();
}

function updateProgress() {
    const pct = Math.round(((currentStep + 1) / steps.length) * 100);
    const text = 'Paso ' + (currentStep + 1) + ' de ' + steps.length;
    $('#tab-progress').css('width', pct + '%').text(text)
        .attr('aria-valuenow', pct);
}

// Sincronizar progreso cuando el usuario hace click directamente en un tab
$('#saleTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    const idx = tabLinks.indexOf('#' + $(e.target).attr('id'));
    if (idx !== -1) {
        currentStep = idx;
        updateProgress();
    }
});

// ---- Restaurar tab y cliente tras recarga por carrito (sessionStorage) ----

$(function () {
    // Restaurar cliente seleccionado
    const savedClient = sessionStorage.getItem('pos_client');
    if (savedClient) {
        try {
            const c = JSON.parse(savedClient);
            seleccionarCliente(c.id, c.nombre, c.nit, c.celular, c.email);
        } catch (e) {
            sessionStorage.removeItem('pos_client');
        }
    }

    // Restaurar paso del wizard
    const savedStep = sessionStorage.getItem('pos_step');
    if (savedStep !== null) {
        sessionStorage.removeItem('pos_step');
        goToStep(parseInt(savedStep, 10));
    }
});

// Guardar paso actual antes de agregar al carrito (vuelve a Tab 2)
$('#btn_agregar_carrito').on('click', function () {
    if (!selectedProductId) {
        AlertUtils.warning('Atención', 'Seleccione un producto primero.');
        return;
    }
    const cantidad = parseInt($('#prod_cantidad').val());
    if (!cantidad || cantidad < 1) {
        $('#lbl_cantidad').removeClass('d-none');
        $('#prod_cantidad').focus();
        return;
    }
    $('#lbl_cantidad').addClass('d-none');
    sessionStorage.setItem('pos_step', '1'); // Tab 2: Carrito
    $('#cart_id_producto').val(selectedProductId);
    $('#cart_cantidad').val(cantidad);
    $('#formCarrito').submit();
});

// Guardar paso actual antes de eliminar del carrito (vuelve a Tab 2)
$(document).on('submit', '.form-cart-remove', function () {
    sessionStorage.setItem('pos_step', '1'); // Tab 2: Carrito
});

// ---- Botones de navegación ----

$('#btn-sig-cliente').on('click', function () {
    goToStep(1);
});

$('#btn-ant-carrito').on('click', function () {
    goToStep(0);
});

$('#btn-sig-carrito').on('click', function () {
    const cartCount = parseInt($('#badge-cart-count').text(), 10) || 0;
    if (cartCount === 0) {
        AlertUtils.warning('Atención', 'Debe agregar al menos un producto al carrito antes de continuar.');
        return;
    }
    goToStep(2);
});

$('#btn-ant-pago').on('click', function () {
    goToStep(1);
});

// ---- DataTables en modales (lazy init) ----

$('#modal-buscar_producto').on('shown.bs.modal', function () {
    if ($.fn.DataTable.isDataTable('#productTable')) {
        $('#productTable').DataTable().columns.adjust().responsive.recalc();
        return;
    }
    $('#productTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 5,
        lengthMenu: [[5, 10, 25], [5, 10, 25]],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando _START_ al _END_ de _TOTAL_ productos',
            sInfoEmpty: 'Mostrando 0 al 0 de 0 productos',
            sInfoFiltered: '(de _MAX_ productos)',
            sSearch: 'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' }
        }
    });
});

$('#modal-buscar_cliente').on('shown.bs.modal', function () {
    if ($.fn.DataTable.isDataTable('#clientTable')) {
        $('#clientTable').DataTable().columns.adjust().responsive.recalc();
        return;
    }
    $('#clientTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 5,
        lengthMenu: [[5, 10, 25], [5, 10, 25]],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando _START_ al _END_ de _TOTAL_ clientes',
            sInfoEmpty: 'Mostrando 0 al 0 de 0 clientes',
            sInfoFiltered: '(de _MAX_ clientes)',
            sSearch: 'Buscar:',
            sLoadingRecords: 'Cargando...',
            oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' }
        }
    });
});

// ---- Seleccionar producto del modal ----

let selectedProductId = '';

$(document).on('click', '.btn-seleccionar', function () {
    selectedProductId = $(this).data('id');
    $('#prod_nombre').val($(this).data('nombre'));
    $('#prod_descripcion').val($(this).data('descripcion'));
    $('#prod_precio').val($(this).data('precio'));
    $('#prod_cantidad').val(1).focus();
});

// ---- Seleccionar cliente del modal ----

function seleccionarCliente(id, nombre, nit, celular, email) {
    $('#id_cliente_hidden').val(id);
    $('#cliente_nombre').val(nombre);
    $('#cliente_nit').val(nit);
    $('#cliente_celular').val(celular);
    $('#cliente_email').val(email);

    // Mostrar campos, ocultar alert
    $('#alert-sin-cliente').addClass('d-none');
    $('#cliente-fields').removeClass('d-none');

    // Actualizar resumen lateral
    $('#resumen-cliente').removeClass('text-muted font-italic').text(nombre);

    // Persistir en sessionStorage para sobrevivir recargas del carrito
    sessionStorage.setItem('pos_client', JSON.stringify({ id, nombre, nit, celular, email }));
}

$(document).on('click', '.btn-seleccionar-cliente', function () {
    seleccionarCliente(
        $(this).data('id'),
        $(this).data('nombre'),
        $(this).data('nit'),
        $(this).data('celular'),
        $(this).data('email')
    );
    $('#modal-buscar_cliente').modal('hide');
});

// ---- Crear nuevo cliente desde ventas ----

$('#formNuevoCliente').validate({
    rules: {
        nombre_cliente: { required: true, minlength: 3, maxlength: 255 },
        nit_ci_cliente: {
            required: true, minlength: 3, maxlength: 50,
            remote: {
                url: BASE_URL + '/clients/check-nit-ci',
                type: 'POST',
                data: {
                    nit_ci_cliente: function () {
                        return $('#nc_nit_ci').val();
                    },
                    id: function () {
                        return null;
                    }
                }
            }
        },
        celular_cliente: { required: true, minlength: 7, maxlength: 50 },
        email_cliente: {
            required: true, email: true, maxlength: 254,
            remote: {
                url: BASE_URL + '/clients/check-email',
                type: 'POST',
                data: {
                    email_cliente: function () {
                        return $('#nc_email').val();
                    },
                    id: function () {
                        return null;
                    }
                }
            }
        }
    },
    messages: {
        nombre_cliente: {
            required: 'El nombre es obligatorio.',
            minlength: 'Mínimo 3 caracteres.'
        },
        nit_ci_cliente: {
            required: 'El NIT/CI es obligatorio.',
            minlength: 'Mínimo 3 caracteres.'
        },
        celular_cliente: {
            required: 'El celular es obligatorio.',
            minlength: 'Mínimo 7 caracteres.'
        },
        email_cliente: {
            required: 'El correo es obligatorio.',
            email: 'Ingrese un correo válido.'
        }
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
    },
    highlight: function (element) {
        $(element).addClass('is-invalid');
    },
    unhighlight: function (element) {
        $(element).removeClass('is-invalid');
    },
    submitHandler: function () {
        crearNuevoCliente();
    }
});

let isCreatingClient = false;

function crearNuevoCliente() {
    if (isCreatingClient) return;

    const formData = $('#formNuevoCliente').serialize();
    const $btn = $('#btnNuevoCliente');
    const originalHtml = $btn.html();

    isCreatingClient = true;
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

    ToastUtils.loadingWithMinTime('Guardando cliente...', function (loadingToast) {
        $.ajax({
            url: BASE_URL + '/clients/store',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                loadingToast.close();
                isCreatingClient = false;
                $btn.prop('disabled', false).html(originalHtml);

                if (response.success) {
                    const d = response.data;
                    seleccionarCliente(d.id_cliente, d.nombre_cliente, d.nit_ci_cliente, d.celular_cliente, d.email_cliente);

                    $('#modal-nuevo_cliente').modal('hide');
                    $('#formNuevoCliente')[0].reset();
                    $('#formNuevoCliente').validate().resetForm();
                    $('#formNuevoCliente').find('.is-invalid').removeClass('is-invalid');

                    ToastUtils.success(response.message || 'Cliente creado y seleccionado.');
                } else {
                    ToastUtils.error(response.message);
                }
            },
            error: function () {
                loadingToast.close();
                isCreatingClient = false;
                $btn.prop('disabled', false).html(originalHtml);
                ToastUtils.error('Error en la comunicación con el servidor.');
            }
        });
    }, 1500);
}

$('#modal-nuevo_cliente').on('hidden.bs.modal', function () {
    isCreatingClient = false;
    $('#formNuevoCliente')[0].reset();
    $('#formNuevoCliente').validate().resetForm();
    $('#formNuevoCliente').find('.is-invalid').removeClass('is-invalid');
});

// ---- Cobro: forma de pago (único / mixto), suma en vivo ----

let metodosActivos = $('#formVenta').data('metodos') || [];
if (typeof metodosActivos === 'string') {
    try {
        metodosActivos = JSON.parse(metodosActivos);
    } catch (e) {
        metodosActivos = [];
    }
}

let currentPaymentMode = 'unico';

function formatMoney(amount) {
    const currency = $('#formVenta').data('currency') || 'Bs.';
    return currency + ' ' + amount.toFixed(2);
}

function parseAmount(value) {
    const n = parseFloat(value);
    return isNaN(n) ? 0 : n;
}

function optionForMetodo(m) {
    return $('<option>').val(m.id_metodo_pago).attr('data-tipo', m.tipo).text(m.nombre);
}

function refillMethodSelect($select, excludedIds) {
    $select.empty().append($('<option>').val('').text('-- Seleccione método --'));
    metodosActivos.forEach(function (m) {
        if (excludedIds.indexOf(m.id_metodo_pago) !== -1) return;
        $select.append(optionForMetodo(m));
    });
}

function currentUsedMethodIds() {
    const used = [];
    $('#contenedor-pagos .pago-item').each(function () {
        const v = $(this).find('.select-metodo-pago').val();
        if (v) used.push(parseInt(v, 10));
    });
    return used;
}

function refillOtherSelects() {
    const ids = currentUsedMethodIds();
    $('#contenedor-pagos .pago-item').each(function () {
        const $sel = $(this).find('.select-metodo-pago');
        const propio = parseInt($sel.val(), 10) || 0;
        const others = $.grep(ids, function (id) { return id !== propio; });
        refillMethodSelect($sel, others);
        if (propio) $sel.val(propio);
    });
}

function agregarPagoMixto() {
    const $template = $('#template-pago-mixto')[0];
    const $row = $(document.importNode($template.content, true));
    refillMethodSelect($row.find('.select-metodo-pago'), currentUsedMethodIds());
    $('#contenedor-pagos').append($row);
    refillOtherSelects();
    syncPaymentNames();
    updatePaymentSummary();
}

function paymentLines() {
    const lines = [];
    if (currentPaymentMode === 'unico') {
        const id = parseInt($('#metodo-pago-unico').val(), 10);
        if (id) {
            lines.push({
                id_metodo_pago: id,
                tipo: $('#metodo-pago-unico option:selected').data('tipo') || '',
                monto: parseAmount($('#monto-unico').val())
            });
        }
    } else {
        $('#contenedor-pagos .pago-item').each(function () {
            const $sel = $(this).find('.select-metodo-pago');
            const id = parseInt($sel.val(), 10);
            if (!id) return;
            lines.push({
                id_metodo_pago: id,
                tipo: $sel.find('option:selected').data('tipo') || '',
                monto: parseAmount($(this).find('.pago-monto-mixto').val())
            });
        });
    }
    return lines;
}

function paymentSummary() {
    const total = parseAmount($('#formVenta').data('total'));
    let suma = 0;
    let hasCashAmount = false;

    paymentLines().forEach(function (line) {
        suma += line.monto;
        if (line.tipo === 'efectivo' && line.monto > 0) {
            hasCashAmount = true;
        }
    });

    suma = Math.round(suma * 100) / 100;
    const diff = Math.round((suma - total) * 100) / 100;
    const noMethods = metodosActivos.length === 0;
    const coverTotal = total > 0 && suma >= total;
    const excessOk = diff <= 0 || (diff > 0 && hasCashAmount);
    const canSubmit = !noMethods && coverTotal && excessOk;

    return { total, suma, diff, hasCashAmount, canSubmit, noMethods };
}

function updatePaymentSummary() {
    const s = paymentSummary();

    $('#cobro-faltante').addClass('d-none');
    $('#cobro-vuelto').addClass('d-none');
    $('#cobro-exceso-aviso').addClass('d-none');

    if (s.noMethods) {
        $('#btn_guardar_venta').prop('disabled', true);
        return;
    }

    if (s.suma < s.total) {
        $('#cobro-faltante')
            .text('Faltan ' + formatMoney(s.total - s.suma) + ' para completar el total.')
            .removeClass('d-none');
    } else if (s.diff > 0) {
        if (s.hasCashAmount) {
            $('#cobro-vuelto')
                .text('Vuelto: ' + formatMoney(s.diff))
                .removeClass('d-none');
        } else {
            $('#cobro-exceso-aviso')
                .text('El exceso sobre el total solo se admite en efectivo.')
                .removeClass('d-none');
        }
    }

    $('#btn_guardar_venta').prop('disabled', !s.canSubmit);
}

function syncPaymentNames() {
    $('#pane-pago input[name^="pagos["]').removeAttr('name');

    if (currentPaymentMode === 'unico') {
        const id = parseInt($('#metodo-pago-unico').val(), 10);
        if (id) {
            $('#monto-unico').attr('name', 'pagos[' + id + '][monto]');
        }
    } else {
        $('#contenedor-pagos .pago-item').each(function () {
            const id = parseInt($(this).find('.select-metodo-pago').val(), 10);
            if (!id) return;
            $(this).find('.pago-monto-mixto').attr('name', 'pagos[' + id + '][monto]');
        });
    }
}

function setPaymentMode(mode) {
    currentPaymentMode = mode;
    const isUnico = mode === 'unico';
    const $unico = $('#seccion-pago-unico');
    const $mixto = $('#seccion-pago-mixto');

    $('#btn-pago-unico').toggleClass('active', isUnico);
    $('#btn-pago-mixto').toggleClass('active', !isUnico);
    $('#forma_pago_unico').prop('checked', isUnico);
    $('#forma_pago_mixto').prop('checked', !isUnico);

    $unico.toggleClass('d-none', !isUnico);
    $mixto.toggleClass('d-none', isUnico);

    // La sección inactiva queda deshabilitada para que no envíe inputs al backend
    (isUnico ? $mixto : $unico).find('input, select, textarea').prop('disabled', true);
    (isUnico ? $unico : $mixto).find('input, select, textarea').prop('disabled', false);

    if (!isUnico && $('#contenedor-pagos .pago-item').length === 0) {
        agregarPagoMixto();
    }

    syncPaymentNames();
    updatePaymentSummary();
}

$(document).on('change', 'input[name="forma_pago"]', function () {
    const mode = $('input[name="forma_pago"]:checked').val() === 'mixto' ? 'mixto' : 'unico';
    setPaymentMode(mode);
    sessionStorage.setItem('pos_forma_pago', mode);
});

$('#metodo-pago-unico').on('change', function () {
    syncPaymentNames();
    updatePaymentSummary();
});

$(document).on('input', '#monto-unico', updatePaymentSummary);

$('#btn-agregar-pago').on('click', agregarPagoMixto);

$(document).on('change', '.select-metodo-pago', function () {
    refillOtherSelects();
    syncPaymentNames();
    updatePaymentSummary();
});

$(document).on('input', '.pago-monto-mixto', function () {
    syncPaymentNames();
    updatePaymentSummary();
});

$(document).on('click', '.btn-eliminar-pago', function () {
    $(this).closest('.pago-item').remove();
    refillOtherSelects();
    syncPaymentNames();
    updatePaymentSummary();
    if ($('#contenedor-pagos .pago-item').length === 0) {
        agregarPagoMixto();
    }
});

$(function () {
    const savedMode = sessionStorage.getItem('pos_forma_pago');
    setPaymentMode(savedMode === 'mixto' ? 'mixto' : 'unico');
});

// ---- Validar antes de guardar la venta ----

$('#formVenta').on('submit', function (e) {
    syncPaymentNames();

    const s = paymentSummary();
    if (!s.canSubmit) {
        e.preventDefault();
        AlertUtils.warning(
            'Atención',
            s.noMethods
                ? 'No hay métodos de pago activos. Configure al menos un método para poder cobrar.'
                : 'La suma de los pagos debe cubrir el total de la venta.'
        );
        goToStep(2);
        return;
    }

    // Limpiar sesión al confirmar la venta
    sessionStorage.removeItem('pos_client');
    sessionStorage.removeItem('pos_step');
    sessionStorage.removeItem('pos_forma_pago');
});

// ---- Cancelar venta ----

$('#btn-cancelar-venta').on('click', function () {
    const $btn = $(this);
    const nroVenta = $btn.data('nro-venta');
    const csrfToken = $btn.data('csrf');

    sessionStorage.removeItem('pos_client');
    sessionStorage.removeItem('pos_step');
    sessionStorage.removeItem('pos_forma_pago');

    fetch(BASE_URL + '/sales/cancel', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nro_venta=' + encodeURIComponent(nroVenta) + '&csrf_token=' + encodeURIComponent(csrfToken)
    }).finally(function () {
        window.location.href = BASE_URL + '/sales';
    });
});