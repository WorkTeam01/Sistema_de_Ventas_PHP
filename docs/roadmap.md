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

## Siguiente 🔜

- **Devoluciones de ventas** — tabla `tb_devoluciones` con FK a `tb_ventas`,
  reversión de stock, devolución parcial (uno o varios ítems), slug de permiso
  nuevo, entrada en el activity log, impacto en reportes. → `specs/001-devoluciones-ventas/`

## Backlog · ideas 💡

_No comprometido ni ordenado. Toda idea debe respetar `docs/constitution.md`,
incluido el alcance del producto. Ordenadas por qué tan propias de un POS son._

- **Formas de pago + pago mixto** — hoy `tb_ventas` solo guarda `total_pagado`
  sin método. Registrar efectivo / tarjeta / transferencia y permitir pago
  combinado en una venta. Cambio de esquema chico, cierra un hueco básico de POS.
- **Arqueo / cuadre de caja** — abrir caja con fondo inicial, registrar
  entradas/salidas de efectivo, cerrar con conteo y diferencia, reporte Z. Es lo
  que distingue un POS de un CRUD de ventas. Feature grande: máquina de estados,
  sesión de caja, conciliación — buen candidato para estrenar el ciclo SDD completo.
- **Ventas a crédito / cuenta corriente de cliente** — saldo pendiente por
  cliente + registro de abonos (`tb_pagos`). Condicional: solo si se quiere
  extender el alcance del producto. Requiere diseño previo cuidadoso.
