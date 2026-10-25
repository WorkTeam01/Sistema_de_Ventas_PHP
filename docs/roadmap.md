# Roadmap — Sistema de Ventas PHP

Vista transversal: qué está hecho, qué sigue y qué está en el backlog. Artefacto
de nivel proyecto (junto a `docs/constitution.md`). Se mantiene a mano; `/sdd:spec`
lo lee al elegir número y `/sdd:validate` mueve una feature a `Hecho ✅` al cerrarla.

Cada entrada de Hecho / Siguiente enlaza la **carpeta** de la feature
(`specs/NNN-<slug>/`), nunca solo un número.

## Hecho ✅

_Features previas a la adopción de SDD: una línea cada una, sin spec/plan/tasks
retroactivos. El detalle vive en [CHANGELOG.md](../CHANGELOG.md)._

1. **Migración a MVC** — front controller, Router, PSR-4, sin módulos legacy. → CHANGELOG 1.0–1.7
2. **Gaps de seguridad e integridad** — carrito huérfano, índices únicos en clientes, rate limiting en login. → CHANGELOG 1.8.0
3. **Registro de actividad (audit log)** — módulo completo en 3 fases, KPIs, cobertura total. → CHANGELOG 1.9.0, 1.14.1
4. **Módulo de inventario** — ajustes de stock atómicos, tab de alertas. → CHANGELOG 1.10.0
5. **Dashboard** — KPIs colapsables, doughnut top 5, fix XSS. → CHANGELOG 1.11.0
6. **Módulo de reportes** — ventas / compras / top productos / clientes; export PDF/CSV/Excel. → CHANGELOG 1.12.0
7. **CSRF completo + fix carritos concurrentes** — `Sale::nextNumber()` con UNION, `pos_nro_venta` sticky. → CHANGELOG 1.12.2, 1.12.3
8. **RBAC granular** — `tb_permisos` + `tb_rol_permiso`, `can:permiso` en todas las rutas, scoping `*_all` por usuario. → CHANGELOG 1.13.0
9. **Gestión de permisos vía UI** — catálogo + asignación por rol, invalidación de caché por `permisos_version`. → CHANGELOG 1.14.0
10. **Hardening de seguridad** — cabeceras HTTP, HTTPS tras proxy, saneo de HTML en SweetAlert2, prevención de IDOR en compras. → CHANGELOG 1.15.0, 1.16.1
11. **Sin `Swal.fire`/`onclick` inline en vistas** — centralizado en `AlertUtils`/`ToastUtils`. → CHANGELOG 1.16.0
12. **Accesibilidad WCAG AA** — auditorías por módulo (auth, POS, Productos, Compras, Clientes, Ventas, Categorías, Inventario, Permisos, Roles, Proveedores, Registro de actividad); contraste claro/oscuro y orden de encabezados app-wide, verificado con axe-core. → CHANGELOG 1.15.x–1.16.6
13. **Cache-busting de assets propios** vía `APP_VERSION`. → CHANGELOG 1.16.1
14. **Moneda configurable** vía `.env` (`APP_CURRENCY_SYMBOL`). → CHANGELOG 1.16.x
15. **Precio histórico por línea de venta** — `tb_carrito.precio_unitario`, congelamiento al finalizar, backfill idempotente, COALESCE en lecturas. → `specs/002-precio-historico-por-linea/`
16. **Devoluciones de ventas** — devoluciones parciales acumulativas, reingreso atómico de stock, permiso `manage_returns`, auditoría y venta neta en dashboard/reportes. → `specs/001-devoluciones-ventas/`
17. **Formas de pago + pago mixto** — catálogo configurable de métodos, pago mixto
    por venta, validación suma ≥ total, vuelto, desglose en detalle/PDF y backfill
    idempotente. Prerequisito del arqueo/cuadre de caja. → `specs/003-formas-de-pago-mixto/`
18. **Ventas sin cliente + forma de pago único/mixto** — `tb_ventas.id_cliente`
    nullable ("Consumidor final"), selector único/mixto en el paso Cobro con
    `<template>`, método único no duplicable, vuelto intacto. → CHANGELOG 1.18.1
19. **Auditoría de seguridad y lógica de negocio** — dos rondas de revisión con
    subagentes (SQL/inputs, XSS, stock y dinero, auth/IDOR/CSRF) con 38
    hallazgos: 16 corregidos en 1.18.2 (escalada de privilegios, revalidación de
    cuenta/rol, scoping de reportes, códigos de producto, rate limit, stock y
    precios negativos, histórico de ajustes, vendedor en factura, exports,
    literales, filtros de fecha) y 10 derivados a spec. → CHANGELOG 1.18.2
20. **Auditoría de seguridad e integridad (los 10 hallazgos derivados + endurecimiento)** —
    reserva del `nro_venta` del POS por usuario (`tb_pos_reservas`) y carrito solo
    del dueño, cobro y devolución idempotentes, `UNIQUE` en `nro_venta`/`nro_compra`
    sin la FK invertida, montos en céntimos y vuelto acotado al efectivo, stock sin
    escrituras pisadas (ajustes relativos, compras revertibles solo con stock),
    devoluciones con tope y líneas repetidas, login con intentos en BD y
    "recordarme" por dispositivo, reset sin enlace en pantalla, migraciones
    reejecutables compatibles con MySQL 8.4 y actualización verificada desde 1.18.3;
    pasada final de accesibilidad (axe-core, claro y oscuro) sobre las vistas tocadas.
    → `specs/004-auditoria-seguridad-integridad/`
21. **Ronda de correcciones 1.19.1** — compras y ajustes de inventario idempotentes ante doble
    envío, texto guardado sin escape HTML (migración 015), sesiones cerradas al cambiar la
    contraseña, `APP_TIMEZONE` aplicado a la sesión de MySQL, sidebar con permisos reales y
    comentarios sin referencias a specs. Sin spec propia (sin cambios de esquema). → CHANGELOG 1.19.1
22. **Ronda de correcciones y despliegue 1.19.2** — auditoría de solo lectura: exportación
    CSV a prueba de fórmulas, XSS del saludo, portada de reportes con permisos propios, guard del
    último superadmin, borrados idempotentes/JSON; seeder de producción, `RewriteBase` portable y
    `docs/guides/despliegue.md`. Sin spec propia (sin cambios de esquema). → CHANGELOG 1.19.2
23. **Reorganización de la documentación 1.19.3** — `docs/schema.md`, `app-flow.md` y `ux-brief.md`
    generados con `/sdd:docs`; guías en `docs/guides/`; `AGENTS.md` y `CLAUDE.md` reducidos.
    Sin spec propia (solo documentación). → CHANGELOG 1.19.3

24. **Ronda a11y y hardening 1.19.4** — `/reports`, `/inventory` y `/errors/403` con 0 violaciones de
    axe en claro y oscuro; desplegables y aviso de «Copiar» de DataTables Buttons legibles en oscuro;
    CSV a prueba de fórmulas; se elimina `/profile/check-email`; contraseña actual para cambiar el correo;
    guard del último superusuario por usuario; entradas con forma de array sin 500. Sin spec propia
    (sin cambios de esquema). → CHANGELOG 1.19.4

## Siguiente 🔜

- **Hardening pendiente de la auditoría (2026-10-17)** — lo aplicado en 1.19.4 queda en el CHANGELOG. Falta:
  - `Cache-Control: no-store` en los PDF (TCPDF escribe sus propias cabeceras en `Output()`: hay que generar el
    PDF en memoria y enviarlo con las cabeceras propias).
  - CSP y HSTS; `Auth::isHttps()` confía en `X-Forwarded-Proto` de cualquier cliente; el límite de intentos usa
    `REMOTE_ADDR`, que se colapsa tras un proxy.
  - `PermissionMiddleware` repite `Auth::check()` (dos SELECT por petición): cachear el resultado por petición.
  - Librerías JS antiguas (jQuery 3.6.0, DataTables 1.11.4, JSZip 3.7.1, Chart.js 2.9.4): cada subida exige smoke test.
  - `Model::create/update` sin lista blanca de columnas; `StockWritesAuditTest` no detecta `Model::update(['stock' => …])`.
  - `.select2-selection` sin `aria-expanded` (axe `aria-required-attr`, viene de la librería).
- **Despliegue real** — `docs/guides/despliegue.md` solo se ha probado con BD vacía; falta un servidor real y
  conocer la zona horaria del MySQL de producción.

## Backlog · ideas 💡

_No comprometido ni ordenado. Toda idea debe respetar `docs/constitution.md`,
incluido el alcance del producto. Ordenadas por qué tan propias de un POS son._

- **Arqueo / cuadre de caja** — abrir caja con fondo inicial, registrar
  entradas/salidas de efectivo, cerrar con conteo y diferencia, reporte Z.
  Depende de que los pagos estén normalizados por método. Es lo que distingue
  a un POS de un CRUD de ventas. Feature grande: máquina de estados, sesión de
  caja y conciliación — buen candidato para estrenar el ciclo SDD completo.
- **Ventas a crédito / cuenta corriente de cliente** — saldo pendiente por
  cliente + registro de abonos (`tb_pagos`). Condicional: solo si se quiere
  extender el alcance del producto. Requiere diseño previo cuidadoso.
