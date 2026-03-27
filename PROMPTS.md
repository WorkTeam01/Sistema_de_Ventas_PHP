# PROMPTS.md — Sistema de Ventas PHP

> Plantillas de prompts para el equipo. Úsalas como base — adapta los bloques
> `[Tarea]` y `[Contexto]` a lo que necesites en cada sesión.
> El `AGENT.md` siempre debe estar disponible para el agente como contexto base.

---

## Cómo usar este archivo

Cada plantilla sigue la estructura de 5 ejes del prompt profesional:

| Eje                   | Pregunta          | Para qué sirve                                    |
| --------------------- | ----------------- | ------------------------------------------------- |
| **Rol**               | ¿Quién eres?      | Define el nivel y especialidad que asume la IA    |
| **Contexto**          | ¿Dónde estamos?   | El proyecto, stack y módulo activo                |
| **Tarea exacta**      | ¿Qué necesitas?   | Concreto y específico — nunca genérico            |
| **Restricciones**     | ¿Qué límites hay? | Convenciones del proyecto que NO se pueden romper |
| **Formato de salida** | ¿Cómo lo quieres? | Estructura del output esperado                    |

> **Regla de oro:** Cuanto más específico sea el bloque `[Tarea]`,
> menos correcciones necesitarás después.

**Reglas de uso del equipo:**

- **Siempre carga el AGENT.md** al inicio de la sesión si la herramienta no lo carga automáticamente.
- **Un prompt por subtarea.** Pedir "el módulo completo" en un solo prompt produce resultados genéricos.
- **Si el output no encaja**, no corrijas manualmente primero — ajusta `[Restricciones]` y repite.
- **El spec antes que el código.** Define qué debe hacer antes de pedir que lo implemente.
- **Guarda los prompts que funcionen bien** en este archivo como nuevas plantillas para el equipo.

---

## Plantilla base (copia esto y rellena)

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas PHP — PHP MVC custom (sin framework), PSR-4 con Composer.
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, MySQL.
Módulo activo: _______________

[Tarea]
_______________

[Restricciones]
- Seguir el patrón MVC existente (referencia: módulo suppliers/clients)
- DataTables sin AJAX: datos cargados desde PHP, sin filtros server-side
- Borrado físico con isReferenced() — nunca borrado lógico con is_active
- Métodos de controlador válidos: index(), create(), store(), edit(?int $id), update(), destroy()
- CSRF token en todos los formularios POST
- Siempre prepared statements con ? — nunca interpolar variables en SQL
- No inventar métodos de controlador que no existan en el proyecto
- No introducir librerías nuevas sin aprobación

[Formato de salida]
_______________
```

---

## Plantilla 1 — Migrar módulo legacy a MVC

Usar cuando: toca migrar un módulo del sistema legacy (`almacen`, `compras`, `ventas`) al patrón MVC.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
y refactoring de sistemas legacy.

[Contexto]
Proyecto: Sistema de Ventas PHP — PHP MVC custom (sin framework), PSR-4 con Composer.
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, MySQL.
Módulo a migrar: [nombre del módulo — ej: almacen, compras, ventas]

Archivos legacy existentes:
- [modulo]/index.php, create.php, update.php, delete.php
- app/controllers/[modulo]/listado_de_[modulo].php
- app/controllers/[modulo]/registro_de_[modulo].php
- app/controllers/[modulo]/actualizar_[modulo].php
- app/controllers/[modulo]/eliminar_[modulo].php

Módulo de referencia para patrones: suppliers o clients (ya migrados).

[Tarea]
Migrar el módulo [nombre] a MVC siguiendo el orden estándar del proyecto:
1. app/Models/[Nombre].php
2. app/Controllers/[Nombre]Controller.php
3. views/[modulo]/index.php, create.php, edit.php
4. Rutas en routes/web.php
5. Sidebar en views/layout/parte1.php
6. DashboardController (si muestra conteo del módulo)
7. Eliminar archivos legacy

[Restricciones]
- Seguir exactamente el patrón de ClientController/SupplierController como referencia
- isReferenced() en el Model si la tabla tiene FK en otras tablas
- Formulario oculto #formEliminar con CSRF para eliminaciones vía SweetAlert2
- Layout de listado: tabla con DataTables + botones export (PDF/Excel/CSV/Imprimir)
- Layout de formularios: col-md-8 (form) + col-md-4 (tarjeta informativa)
- Breadcrumb obligatorio en cada vista
- Sidebar con control de rol (Administrador y/o Vendedor según el módulo)
- BASE_URL para todas las URLs — nunca hardcodear rutas
- Commits separados: feat(modulo) para archivos MVC + chore(modulo) para eliminación de legacy

[Formato de salida]
Devuelve en este orden:
1. Lista de archivos que se crean, modifican o eliminan
2. Código de cada archivo nuevo (Model, Controller, vistas)
3. Diff de archivos modificados (routes/web.php, parte1.php, DashboardController)
4. Checklist de testing manual (flujos exitosos + edge cases)
```

---

## Plantilla 2 — Generar código nuevo (feature)

Usar cuando: implementar una nueva funcionalidad que no existe en el sistema.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas PHP — PHP MVC custom (sin framework), PSR-4 con Composer.
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, MySQL.
Módulo activo: [nombre del módulo]

Estructura de archivos relevante:
- app/Controllers/[Nombre]Controller.php
- app/Models/[Nombre].php
- views/[modulo]/[vista].php

[Tarea]
Implementar [nombre exacto de la funcionalidad].

Descripción: [criterios de aceptación o comportamiento esperado]

[Restricciones]
- Seguir el patrón MVC de suppliers/clients como referencia
- DataTables sin AJAX: datos cargados desde PHP en la vista
- Métodos de controlador válidos: index(), create(), store(), edit(?int $id), update(), destroy()
- Borrado físico con isReferenced() — nunca borrado lógico
- Validación server-side obligatoria en store() y update()
- SweetAlert2 para confirmaciones — formulario oculto #formEliminar + CSRF
- CSRF token en todos los formularios POST
- htmlspecialchars() en todos los outputs HTML
- No inventar métodos ni librerías que no existan en el proyecto

[Formato de salida]
Devuelve en este orden:
1. Lista de archivos que se crean o modifican
2. Código de cada archivo (con comentarios donde la lógica no sea obvia)
3. Queries SQL si hay cambios en BD
4. Checklist de testing manual (casos exitosos + edge cases)
```

---

## Plantilla 3 — Debuggear un error

Usar cuando: algo no funciona y no está claro por qué.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en debugging
de aplicaciones MVC y MySQL sobre XAMPP.

[Contexto]
Proyecto: Sistema de Ventas PHP — PHP MVC custom (sin framework).
Stack: PHP 8.x, MySQL, jQuery, AdminLTE 3.2.0.
Archivo donde ocurre el error: [ruta completa]
Método/función afectada: [nombre]

[Tarea]
Tengo este error:
[pega el mensaje de error exacto o el comportamiento inesperado]

Código actual:
[pega el bloque de código relevante — no todo el archivo]

Lo que debería hacer:
[describe el comportamiento esperado]

Lo que intenté que no funciona:
[describe lo que ya probaste]

[Restricciones]
- No cambiar la arquitectura del archivo — solo corregir el problema específico
- Mantener las convenciones de naming del proyecto
- Si el fix requiere cambiar más de un archivo, indicarlo antes de proponer código

[Formato de salida]
1. Diagnóstico: causa raíz del error en 2-3 líneas
2. Fix: código corregido con comentario explicando el cambio
3. Por qué pasó: explicación breve para no repetirlo
```

---

## Plantilla 4 — Code review antes del commit

Usar cuando: el código funciona pero quieres validarlo antes de commitear o hacer merge.

```
[Rol]
Actúa como Tech Lead PHP con experiencia en code review de sistemas MVC,
seguridad web y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas PHP — PHP MVC custom.
Módulo revisado: [nombre]
Cambio implementado: [descripción breve]

[Tarea]
Revisa el siguiente código antes del commit.

[pega el código o el diff]

[Restricciones]
Evalúa específicamente:
- Seguridad: SQL injection (prepared statements), XSS (htmlspecialchars), CSRF token presente
- Convenciones: naming en inglés, estructura MVC, métodos de controlador válidos
- Lógica: isReferenced() antes de DELETE, validación server-side en store/update
- Vistas: BASE_URL para URLs, json_encode() para strings PHP→JS, breadcrumb presente
- DataTables: datos desde PHP, no AJAX
- Casos edge que podrían fallar en producción (FK violations, inputs vacíos, etc.)

[Formato de salida]
Responde con esta estructura:
OK  - Lo que está bien (menciona al menos 2 cosas)
OBS - Observaciones (mejoras no críticas, con sugerencia)
FIX - Problemas a corregir antes del commit (con código corregido)
```

---

## Plantilla 5 — Consulta de arquitectura

Usar cuando: hay una decisión técnica importante antes de implementar.

```
[Rol]
Actúa como arquitecto de software PHP con experiencia en sistemas MVC
custom, diseño de base de datos y migración de sistemas legacy.

[Contexto]
Proyecto: Sistema de Ventas PHP — PHP MVC custom (sin framework), PSR-4 con Composer.
Estado actual: migración MVC en curso — almacen, compras, ventas pendientes.
BD implementada: tb_usuarios, tb_roles, tb_categorias, tb_proveedores, tb_clientes,
                 tb_almacen, tb_ventas, tb_carrito, tb_compras.

[Tarea]
Necesito decidir: [describe la decisión técnica]

Opciones que estoy considerando:
- Opción A: [describe]
- Opción B: [describe]

[Restricciones]
- No introducir frameworks (ni Laravel, ni Symfony)
- Mantener compatibilidad con el Router, Database singleton y módulos legacy aún no migrados
- La solución debe ser coherente con el patrón ya establecido (suppliers/clients como referencia)
- Considerar el impacto en los módulos legacy si la decisión toca config.php o el entry point

[Formato de salida]
1. Recomendación directa (cuál opción y por qué en 3 líneas)
2. Trade-offs de cada opción (tabla si aplica)
3. Impacto en el resto del sistema
4. Primeros pasos concretos para implementar la opción recomendada
```

---

## Ejemplo real — Migración de módulo `clients` (implementado)

> Ejemplo completo de cómo se ve un prompt de migración bien estructurado.
> `clients` ya está migrado — úsalo como referencia de calidad al planear los próximos módulos.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
y refactoring de sistemas legacy PHP.

[Contexto]
Proyecto: Sistema de Ventas PHP — PHP MVC custom, PSR-4 con Composer.
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, MySQL.
Módulo: clients (migración de clientes legacy).

BD relevante:
- tb_clientes (id_cliente, nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente,
               fyh_creacion, fyh_actualizacion)
- tb_ventas (id_venta, id_cliente, ...) ← FK que protege el borrado

Legacy existente:
- clientes/index.php (solo listado, sin create/update/delete)
- app/controllers/clientes/listado_de_clientes.php
- app/controllers/clientes/cargar_cliente.php (solo INSERT, bug en campo email)

Módulo de referencia: suppliers (ya migrado).

[Tarea]
Migrar el módulo clientes a MVC completo (CRUD), incluyendo:
- Modelo Client con isReferenced() que valida tb_ventas antes de borrar
- ClientController con los 6 métodos estándar
- 3 vistas: index (DataTables), create, edit
- 6 rutas en routes/web.php con middleware auth
- Actualizar sidebar (Administrador + Vendedor)
- Actualizar DashboardController para usar Client::count()
- Eliminar archivos legacy

[Restricciones]
- Seguir ClientController/SupplierController como referencia exacta
- Todos los campos son obligatorios (no hay opcionales en tb_clientes)
- isReferenced() consulta tb_ventas.id_cliente
- Formulario oculto #formEliminar + CSRF para eliminación vía SweetAlert2
- Rutas solo con middleware auth (no admin) — Vendedor también puede gestionar clientes
- Commits separados: feat(clients) para MVC + chore(clients) para legacy eliminado

[Formato de salida]
1. Lista de archivos creados/modificados/eliminados
2. Código completo de cada archivo nuevo
3. Diff de routes/web.php, parte1.php, DashboardController
4. Checklist de testing manual
```

---

_Última actualización: 2026-03-27 — migración MVC en curso_
_Mantener sincronizado con AGENT.md al completar cada módulo._
