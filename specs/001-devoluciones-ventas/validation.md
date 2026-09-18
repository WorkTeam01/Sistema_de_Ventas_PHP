# Validación — Spec 001: Devoluciones de ventas

Validación ejecutada el 2026-09-18 con `composer test`.

| FR    | Resumen                                             | Evidencia / prueba                                                               | Veredicto |
| ----- | --------------------------------------------------- | -------------------------------------------------------------------------------- | --------- |
| FR-1  | Registro parcial con cantidades pendientes y motivo | `SaleReturnTest` V1/V2; `SaleReturn::pendingByVenta()` y flujo MVC implementado  | PASS      |
| FR-2  | Reingreso atómico de stock                          | `SaleReturnTest` V1                                                              | PASS      |
| FR-3  | Monto desde precio histórico y SQL                  | `SaleReturnTest` V1; precio persistido en detalle                                | PASS      |
| FR-4  | Venta original inmutable                            | `SaleReturnTest` V4                                                              | PASS      |
| FR-5  | Devoluciones sucesivas acumulativas                 | `SaleReturnTest` V2                                                              | PASS      |
| FR-6  | Pendiente calculado bajo transacción                | `SaleReturnTest` V1/V2; lock de venta y carrito en `register()`                  | PASS      |
| FR-7  | Rechazo atómico por exceso                          | `SaleReturnTest` V3                                                              | PASS      |
| FR-8  | Rechazo sin líneas                                  | `SaleReturnTest` V3                                                              | PASS      |
| FR-9  | Motivo obligatorio                                  | `SaleReturnTest` V3 y validación de `SaleReturnController::store()`              | PASS      |
| FR-10 | Venta inexistente                                   | `SaleReturnTest` V3                                                              | PASS      |
| FR-11 | Número único correlativo                            | `SaleReturnTest` V1; `nro_devolucion = id_devolucion` y UNIQUE                   | PASS      |
| FR-12 | Registro de actividad                               | `SaleReturnTest` V1; `ActivityLogRendererTest`                                   | PASS      |
| FR-13 | Bloqueo de eliminación                              | `SaleReturnTest` V5; `Sale::isReferenced()`                                      | PASS      |
| FR-14 | Devoluciones en detalle de venta                    | `views/sales/show.php`; `SaleReturn::byVenta()`                                  | PASS      |
| FR-15 | Scoping por propietario                             | `SaleReturnTest` V5 y guards del controlador                                     | PASS      |
| FR-16 | Permisos de lectura y escritura                     | Rutas `can:view_sales` y `can:manage_returns`; seeder y migración 007            | PASS      |
| FR-17 | Listado, detalle y exportación                      | `views/returns/index.php`, `show.php` y `returns-index.js`                       | PASS      |
| FR-18 | Sin ventana temporal                                | `SaleReturn::register()` no aplica límite de fecha; cobertura V1                 | PASS      |
| FR-19 | Reingreso total de mercadería                       | `SaleReturnTest` V1/V2                                                           | PASS      |
| FR-20 | Monto informativo, sin reversión de pago            | Persistencia separada en `tb_devoluciones`; no modifica `tb_ventas.total_pagado` | PASS      |
| FR-21 | Venta neta en dashboard y reportes                  | `ReportReturnsNetTest` V6/V7 y pruebas de `Sale`/`Product`                       | PASS      |
| FR-22 | Indicador en listado de ventas                      | `SaleReturnsIndicatorTest` V8 y badge `Devuelto` en la vista                     | PASS      |

## Criterios de completitud

- [x] Todos los FR tienen evidencia automatizada o revisión de implementación.
- [x] Tablas, permiso, auditoría y migración 007 están documentados.
- [x] `composer test` completo verde: 308 tests, 621 aserciones, 15 skips esperados.
- [x] Las vistas nuevas siguen los patrones de layout, CSRF, DataTables y assets por módulo del proyecto.

## Veredicto

**Spec cumplida.** La feature está implementada, documentada y validada; las
pruebas que dependen de MariaDB se mantienen como skips cuando el servicio no
está disponible, conforme a la política del proyecto.

## Notas

- La validación automatizada cubre modelo, agregaciones netas, indicador y
  renderizado de etiquetas. La comprobación axe-core de las vistas nuevas se
  conserva como evidencia del cierre de UI realizado durante T12/T13.
