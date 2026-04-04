# Plan: Adaptar módulo Purchases al patrón del módulo Users

> Documento generado el 2026-04-04.
> Modelo de planificación: Claude Opus 4.6 — Redacción del doc: Claude Sonnet 4.6.

---

## Prompt utilizado (Plantilla 1 — PROMPTS.md)

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom (sin framework).
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, MySQL.
Módulo activo: purchases (compras)

Estructura relevante:
- app/Controllers/PurchaseController.php
- views/purchases/index.php — DataTable + confirmarEliminar INLINE en <script> (Swal.fire directo)
- views/purchases/create.php — form HTML5 required sin jQuery Validate
- views/purchases/edit.php   — form HTML5 required sin jQuery Validate
- public/js/modules/users/users-index.js — REFERENCIA del patrón objetivo
- public/js/modules/users/users-create.js — REFERENCIA jQuery Validate
- public/js/modules/users/users-edit.js   — REFERENCIA jQuery Validate

Módulo de referencia: users — YA implementa el patrón objetivo:
- JS separado en public/js/modules/users/*.js
- DataTable inicializado en users-index.js (no inline)
- jQuery Validate en users-create.js y users-edit.js
- confirmarEliminar usa AlertUtils/ToastUtils (no Swal.fire directo)
- Controller pasa pageScripts con las dependencias de JS

[Tarea]
Adaptar el módulo purchases al patrón del módulo users.

Brechas detectadas:
1. views/purchases/index.php — bloque <script> inline con DataTable init y
   confirmarEliminar usando Swal.fire() directo → extraer a purchases-index.js
2. PurchaseController::index() — no pasa pageScripts
3. views/purchases/create.php y edit.php — sin jQuery Validate (solo HTML5 required)
4. No existen archivos public/js/modules/purchases/*.js
5. PurchaseController::create() y edit() — sin pageScripts ni ['validation']

Diferencia clave purchases vs users:
- purchases NO tiene isReferenced() ni endpoint check() AJAX
- Flujo delete: AlertUtils.confirm directo → form.submit (sin pre-check AJAX ni redirect
  a página de confirmación intermedia)

[Restricciones]
- Seguir EXACTAMENTE el patrón del módulo users como referencia
- DataTables sin AJAX: datos desde PHP
- AlertUtils.confirm() para confirmación — nunca Swal.fire() directo
- No inventar endpoints nuevos en routes/web.php
- No introducir librerías nuevas
- exportOptions debe excluir la columna Acciones (índice 7)
- Campos del form: fecha_compra, comprobante, id_producto, id_proveedor,
  precio_compra, cantidad

[Formato de salida]
1. Lista de archivos que se crean o modifican
2. Plan paso a paso con código exacto
3. Checklist de testing manual
```

---

## Archivos afectados

| # | Archivo                                           | Operación     | Cambio                                                                 |
|---|---------------------------------------------------|---------------|------------------------------------------------------------------------|
| 1 | `public/js/modules/purchases/purchases-index.js`  | **Crear**     | DataTable estandarizado + `confirmarEliminar` con `AlertUtils.confirm` |
| 2 | `public/js/modules/purchases/purchases-create.js` | **Crear**     | jQuery Validate para `#purchaseCreateForm`                             |
| 3 | `public/js/modules/purchases/purchases-edit.js`   | **Crear**     | jQuery Validate para `#purchaseEditForm`                               |
| 4 | `views/purchases/index.php`                       | **Modificar** | Eliminar bloque `<script>` inline completo                             |
| 5 | `views/purchases/create.php`                      | **Modificar** | Agregar `id="purchaseCreateForm"` al `<form>`                          |
| 6 | `views/purchases/edit.php`                        | **Modificar** | Agregar `id="purchaseEditForm"` al `<form>`                            |
| 7 | `app/Controllers/PurchaseController.php`          | **Modificar** | `pageScripts` en `index()`, `create()`, `edit()`                       |

---

## Plan de implementación

### Paso 1 — Crear `purchases-index.js`

Archivo: `public/js/modules/purchases/purchases-index.js`

Extraer el DataTable de `views/purchases/index.php` y estandarizarlo siguiendo
`users-index.js`. Reemplazar `confirmarEliminar` con `AlertUtils.confirm`.

Diferencia clave vs users: purchases no tiene endpoint `check()` AJAX. El flujo es:
`AlertUtils.confirm` → asignar hidden fields → `ToastUtils.loadingWithMinTime` → `form.submit()`.

```js
/**
 * ============================================================================
 * GESTIÓN DE COMPRAS - Inicialización DataTable
 * ============================================================================
 */

$(document).ready(function () {
    $('#purchaseTable').DataTable({
        responsive: true,
        autoWidth: false,
        buttons: [{
            extend: 'collection',
            text: 'Reportes',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]}
            }, {
                extend: 'pdf',
                title: 'Compras - Sistema de Ventas',
                filename: 'compras_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]},
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';
                    doc.content.splice(0, 1, {
                        text: 'COMPRAS - SISTEMA DE VENTAS',
                        style: {fontSize: 16, alignment: 'center', bold: true, margin: [0, 10, 0, 10]}
                    });
                    doc.content.splice(1, 0, {
                        text: 'Listado de compras registradas en el sistema',
                        style: {fontSize: 11, alignment: 'center', italic: true, margin: [0, 0, 0, 10]}
                    });
                    doc.content.splice(2, 0, {
                        text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        style: {fontSize: 9, alignment: 'right', margin: [0, 0, 0, 10]}
                    });
                    doc.footer = function (currentPage, pageCount) {
                        return {
                            columns: [
                                {text: 'Sistema de Ventas', alignment: 'left', fontSize: 8},
                                {text: 'Página ' + currentPage + ' de ' + pageCount, alignment: 'center', fontSize: 8},
                                {text: 'Confidencial', alignment: 'right', fontSize: 8}
                            ],
                            margin: [40, 0]
                        };
                    };
                }
            }, {
                extend: 'excel',
                title: 'Compras - Sistema de Ventas',
                messageTop: 'Registro de compras del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]}
            }, {
                extend: 'csv',
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]}
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Compras - Sistema de Ventas',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6]},
                customize: function (win) {
                    $(win.document.body).find('table').addClass('table-striped').css('font-size', '12px');
                }
            }]
        }, {
            extend: 'colvis',
            text: 'Columnas'
        }],
        pageLength: 5,
        lengthMenu: [[3, 5, 10, 25, 50], [3, 5, 10, 25, 50]],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sEmptyTable: 'Ningún dato disponible en esta tabla',
            sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ compras',
            sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 compras',
            sInfoFiltered: '(filtrado de un total de _MAX_ compras)',
            sSearch: 'Buscar:',
            oPaginate: {sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior'}
        },
        initComplete: function () {
            $(this.api().table().node()).css('visibility', 'visible');
        }
    }).buttons().container().appendTo('#purchaseTable_wrapper .col-md-6:eq(0)');
});

function confirmarEliminar(id, idProducto, cantidad, nombre) {
    AlertUtils.confirm(
        '¿Está seguro?',
        'Se eliminará la compra del producto "' + nombre + '" y se revertirá el stock. Esta acción no se puede deshacer.',
        function () {
            document.getElementById('eliminarId').value = id;
            document.getElementById('eliminarProductoId').value = idProducto;
            document.getElementById('eliminarCantidad').value = cantidad;

            ToastUtils.loadingWithMinTime('Eliminando compra...', function () {
                document.getElementById('formEliminar').submit();
            }, 800);
        },
        {confirmText: 'Sí, eliminar', cancelText: 'Cancelar'}
    );
}
```

---

### Paso 2 — Limpiar `views/purchases/index.php`

**Eliminar** el bloque `<script>` inline completo (líneas 112–182 del archivo original).
El form oculto `#formEliminar` se mantiene intacto.

```diff
- <script>
-     $(document).ready(function() {
-         $("#purchaseTable").DataTable({ ... });
-     });
-
-     function confirmarEliminar(id, idProducto, cantidad, nombre) {
-         Swal.fire({ ... });
-     }
- </script>
```

---

### Paso 3 — Actualizar `PurchaseController::index()`

```diff
  $this->renderWithLayout('views/purchases/index.php', array_merge(
      $this->sessionData(),
      [
          'purchases_datos' => $purchases_datos,
          'csrf_token'      => Auth::generateCsrfToken(),
+         'pageScripts'     => ['/js/modules/purchases/purchases-index.js'],
      ]
  ), true, ['datatable']);
```

---

### Paso 4 — Crear `purchases-create.js`

Archivo: `public/js/modules/purchases/purchases-create.js`

Sigue el patrón de `users-create.js`. `errorPlacement` maneja el caso especial de
los selects con botón "+" adyacente (wrapper `.d-flex`) en el form de create.

```js
$(document).ready(function () {
    $('#purchaseCreateForm').validate({
        rules: {
            fecha_compra: {required: true},
            comprobante: {required: true, minlength: 3},
            id_producto: {required: true},
            id_proveedor: {required: true},
            precio_compra: {required: true, number: true, min: 0.01},
            cantidad: {required: true, digits: true, min: 1}
        },
        messages: {
            fecha_compra: {required: 'La fecha de compra es requerida'},
            comprobante: {required: 'El comprobante es requerido', minlength: 'Mínimo 3 caracteres'},
            id_producto: {required: 'Debe seleccionar un producto'},
            id_proveedor: {required: 'Debe seleccionar un proveedor'},
            precio_compra: {
                required: 'El precio es requerido',
                number: 'Ingrese un precio válido',
                min: 'El precio debe ser mayor a 0'
            },
            cantidad: {required: 'La cantidad es requerida', digits: 'Debe ser número entero', min: 'Mínimo 1'}
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
```

---

### Paso 5 — Crear `purchases-edit.js`

Archivo: `public/js/modules/purchases/purchases-edit.js`

Igual que `purchases-create.js` pero sin `errorPlacement` especial para `.d-flex`
(el form de edit no tiene el botón "+" adyacente) y con mensaje de loading diferente.

```js
$(document).ready(function () {
    $('#purchaseEditForm').validate({
        /* mismas rules y messages que create */
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
            const $btn = $(form).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Actualizando...');
            if (typeof ToastUtils !== 'undefined') {
                ToastUtils.loadingWithMinTime('Actualizando compra...', function () {
                    form.submit();
                }, 1000);
            } else {
                form.submit();
            }
        }
    });
});
```

---

### Paso 6 — Agregar `id` al form en `views/purchases/create.php`

```diff
- <form action="<?= BASE_URL ?>/purchases" method="post">
+ <form id="purchaseCreateForm" action="<?= BASE_URL ?>/purchases" method="post">
```

---

### Paso 7 — Agregar `id` al form en `views/purchases/edit.php`

```diff
- <form action="<?= BASE_URL ?>/purchases/update" method="post">
+ <form id="purchaseEditForm" action="<?= BASE_URL ?>/purchases/update" method="post">
```

---

### Paso 8 — Actualizar `PurchaseController::create()`

```diff
  $this->renderWithLayout('views/purchases/create.php', array_merge(
      $this->sessionData(),
      [
          'next_number'  => $purchaseModel->nextNumber(),
          'products'     => $productModel->all(),
          'suppliers'    => $supplierModel->all(),
          'email_sesion' => Auth::user()['email'] ?? '',
          'csrf_token'   => Auth::generateCsrfToken(),
+         'pageScripts'  => ['/js/modules/purchases/purchases-create.js'],
      ]
- ));
+ ), true, ['validation']);
```

---

### Paso 9 — Actualizar `PurchaseController::edit()`

```diff
  $this->renderWithLayout('views/purchases/edit.php', array_merge(
      $this->sessionData(),
      [
          /* ... campos existentes ... */
          'csrf_token'   => Auth::generateCsrfToken(),
+         'pageScripts'  => ['/js/modules/purchases/purchases-edit.js'],
      ]
- ));
+ ), true, ['validation']);
```

---

## Diferencia arquitectural: purchases vs users

| Aspecto                 | `users`                                     | `purchases`                                                  |
|-------------------------|---------------------------------------------|--------------------------------------------------------------|
| Delete pre-check        | AJAX `/users/check/{id}` → `isReferenced()` | **Ninguno** — compras no son referenciadas                   |
| Página de confirmación  | `views/users/delete.php`                    | **Ninguna** — form oculto inline                             |
| Flujo de eliminación    | loading → AJAX → redirect a delete.php      | `AlertUtils.confirm` → hidden fields → loading → form.submit |
| `check()` en controller | Sí — endpoint JSON                          | **No aplica**                                                |

---

## Checklist de testing manual

### Listado (`/purchases`)

- [ ] DataTable renderiza visible con botones Reportes y Columnas
- [ ] Export PDF/Excel/CSV/Print excluye la columna Acciones (col 7)
- [ ] Click "Eliminar" → modal `AlertUtils.confirm` con texto correcto
- [ ] Confirmar → loading toast → compra eliminada y stock revertido
- [ ] Cancelar → no se elimina nada
- [ ] Doble-click rápido en Eliminar → no produce doble submit

### Creación (`/purchases/create`)

- [ ] Submit con form vacío → errores jQuery Validate en todos los campos
- [ ] Comprobante con < 3 caracteres → error minlength
- [ ] Precio = 0 o negativo → error min
- [ ] Cantidad con decimales (ej: 1.5) → error digits
- [ ] Form válido → loading toast → compra guardada, stock aumentado

### Edición (`/purchases/edit/{id}`)

- [ ] Campos pre-poblados pasan validación al enviar sin cambios
- [ ] Vaciar campo obligatorio → error jQuery Validate
- [ ] Form válido → loading toast → compra actualizada, stock ajustado
- [ ] Cambiar producto → stock del producto anterior restaurado, stock del nuevo descontado

### Edge cases

- [ ] Select producto/proveedor sin opciones → `required` impide envío
- [ ] Error en `.d-flex` (create) → mensaje aparece debajo del wrapper, no dentro del select
- [ ] Precio con muchos decimales (ej: 10.999) → jQuery acepta, backend trunca a 2 decimales