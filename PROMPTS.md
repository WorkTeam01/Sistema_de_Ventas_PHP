# PROMPTS.md — Sistema de Ventas

> Plantillas de prompts para el equipo. Úsalas como base — adapta los bloques
> `[Tarea]` y `[Contexto]` a lo que necesites en cada sesión.
> **Requisito:** Carga [AGENT.md](AGENT.md) (arquitectura, convenciones, stack) al inicio de cada sesión. [CLAUDE.md](CLAUDE.md) es opcional para contexto local.

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

- **Carga el AGENT.md primero** (contexto persistente para cualquier agente) — contiene arquitectura, stack, convenciones globales, prohibiciones
- **CLAUDE.md es opcional** (desarrollo local) — contiene instrucciones operacionales de XAMPP/BD, no convenciones de código
- **Un prompt por subtarea.** Pedir "el módulo completo" en un solo prompt produce resultados genéricos.
- **Si el output no encaja**, no corrijas manualmente primero — ajusta `[Restricciones]` y repite.
- **El spec antes que el código.** Define qué debe hacer antes de pedir que lo implemente.
- **Guarda los prompts que funcionen bien** en este archivo como nuevas plantillas para el equipo.

---

## Plantilla base (copia esto y rellena)

> **Antes de usar:** Asegúrate de consultar [AGENT.md](AGENT.md) (arquitectura global y convenciones). [CLAUDE.md](CLAUDE.md) opcional para desarrollo local.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom (sin framework).
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, MySQL.
Módulo activo: _______________

[Tarea]
_______________

[Restricciones]
- Seguir el patrón MVC existente (referencia: módulo de productos)
- DataTables sin AJAX: datos cargados desde PHP, filtros via GET server-side
- Métodos de controlador válidos: index(), create(), store(), edit(), update(), destroy()
- Borrado físico (DELETE) — isReferenced() para validar FK antes de eliminar
- Passwords: password_hash() al guardar, password_verify() al validar
- Validación en frontend (jQuery) Y backend (PHP) siempre
- Nunca concatenar variables en SQL — usar placeholders ? con execute([$var])
- No inventar métodos de controlador que no existan en el proyecto
- No introducir librerías nuevas sin aprobación del líder técnico

[Formato de salida]
_______________
```

---

## Plantilla 1 — Generar código nuevo (feature)

Usar cuando: implementar un requerimiento nuevo del backlog (RF09, RF10...).

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom (sin framework).
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, MySQL.
Módulo activo: [nombre del módulo — ej: productos, ventas, compras, clientes]

Estructura de archivos relevante:
- app/Controllers/[Módulo]Controller.php
- app/Models/[Módulo].php
- views/[modulo]/[vista].php
- public/js/modules/[modulo]/[script].js

[Tarea]
Implementar [nombre exacto del requerimiento].

Descripción: [describe qué debe hacer]

[Restricciones]
- Seguir el patrón MVC del módulo de productos como referencia
- DataTables en vistas de listado (sin AJAX, datos desde PHP)
- Métodos de controlador válidos: index(), create(), store(), edit(), update(), destroy()
- Borrado físico con DELETE — usar isReferenced() para validar FKs antes de eliminar
- Validación en frontend (jQuery) Y backend (PHP) siempre
- AlertUtils / ToastUtils para notificaciones — nunca Swal.fire() ni alert() directamente
- CSRF token obligatorio en todos los formularios POST
- Nunca concatenar variables en SQL — usar placeholders ? con execute([$var])
- No inventar métodos que no existan en el patrón CRUD del proyecto

[Formato de salida]
Devuelve en este orden:
1. Lista de archivos que se crean o modifican
2. Código de cada archivo (comentarios solo donde la lógica no sea obvia)
3. Queries SQL si hay cambios en BD (incluyendo índices y FKs)
4. Checklist de testing manual (casos exitosos + edge cases, incluyendo isReferenced())
```

---

## Plantilla 2 — Debuggear un error

Usar cuando: algo no funciona y no está claro por qué.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en debugging
de aplicaciones MVC y MySQL.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom (sin framework).
Stack: PHP 8.2+, MySQL, jQuery, AdminLTE 3.
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

## Plantilla 3 — Code review antes del merge

Usar cuando: antes de hacer merge de una rama, o cuando el código funciona
pero algo "huele mal".

```
[Rol]
Actúa como Tech Lead PHP con experiencia en code review de sistemas MVC,
seguridad web y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom.
Rama revisada: feature/[nombre]
Requerimiento implementado: [nombre del requerimiento]

[Tarea]
Revisa el siguiente código antes del merge a dev.

[pega el código o el diff del PR]

[Restricciones]
Evalúa específicamente:
- Seguridad: SQL injection (placeholders ?), XSS (htmlspecialchars), CSRF, autenticación débil, datos sensibles expuestos
- Convenciones: naming, estructura MVC, métodos de controlador válidos (index, create, store, edit, update, destroy)
- Lógica: borrado físico con isReferenced(), validación en ambos lados (frontend + backend)
- Notificaciones: AlertUtils/ToastUtils usados correctamente (no Swal.fire() directo)
- DataTables: sin AJAX — datos desde PHP, filtros server-side
- Subida de archivos: validación de extensión whitelist, MIME type real, tamaño máximo 2MB
- Casos edge que podrían fallar en producción

[Formato de salida]
Responde con esta estructura:
OK  - Lo que está bien (menciona al menos 2 cosas)
OBS - Observaciones (mejoras no críticas, con sugerencia)
FIX - Problemas a corregir antes del merge (con código corregido)
```

---

## Plantilla 4 — Consulta de arquitectura

Usar cuando: hay una decisión técnica importante antes de implementar,
o cuando no está claro cómo integrar algo nuevo.

```
[Rol]
Actúa como arquitecto de software PHP con experiencia en sistemas MVC
custom, diseño de base de datos y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom (sin framework).
Estado actual: MVP completado con módulos: usuarios, roles, categorías, proveedores, clientes, productos, compras, ventas.
BD implementada: tb_usuarios, tb_roles, tb_categorias, tb_proveedores, tb_clientes, tb_almacen,
                 tb_compras, tb_ventas, tb_carrito, tb_activity_log.

[Tarea]
Necesito decidir: [describe la decisión técnica]

Opciones que estoy considerando:
- Opción A: [describe]
- Opción B: [describe]

[Restricciones]
- No introducir frameworks (ni Laravel, ni Symfony)
- Mantener compatibilidad con Router y Database singleton actuales
- La solución debe poder implementarla un dev junior sin romper lo que existe
- Preservar isReferenced() para validación de borrados físicos
- Considerar impacto en stock y auditoría si la decisión toca inventario o transacciones

[Formato de salida]
1. Recomendación directa (cuál opción y por qué en 3 líneas)
2. Trade-offs de cada opción (tabla si aplica)
3. Impacto en el resto del sistema
4. Primeros pasos concretos para implementar la opción recomendada
```

---

## Ejemplo real — Spec First aplicado a módulo Reportes

> Ejemplo completo de cómo se ve un prompt de feature bien estructurado.
> El módulo de Productos ya está implementado — úsalo como
> referencia de calidad al redactar specs para nuevos requerimientos.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC,
diseño de base de datos y generación de reportes.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom (sin framework).
Stack: AdminLTE 3.2.0, Bootstrap 4, jQuery, DataTables, SweetAlert2, TCPDF, MySQL.
Módulo: reportes (nuevo módulo o extensión).

BD relevante:
- tb_usuarios (usuario_id, nombre, apellido, email, contraseña, id_rol, fyh_creacion, fyh_actualizacion)
- tb_roles (rol_id, nombre) — valores: Administrador, Vendedor, Comprador
- tb_almacen (producto_id, nombre, descripcion, precio_venta, precio_costo, stock, id_categoria,
  imagen, fyh_creacion, fyh_actualizacion)
- tb_categorias (categoria_id, nombre)
- tb_ventas (venta_id, numero_venta, usuario_id, cliente_id, fyh_creacion, monto_total)
- tb_carrito (carrito_id, venta_id, producto_id, cantidad, precio_unitario)
- tb_compras (compra_id, numero_compra, proveedor_id, usuario_id, fyh_creacion, monto_total)

Módulo de referencia para patrones: productos (Products).

[Tarea]
Implementar reporte de Ventas por Período.

Criterios de aceptación:
- Vista de reporte accessible desde admin y vendedores (solo sus propias ventas)
- Filtro por rango de fechas (FROM/TO) con validación: TO >= FROM
- Exportación a PDF con TCPDF (inline), Excel, CSV, Imprimir
- Tabla de datos: Nº Venta, Fecha, Cliente, Vendedor, Total, Producto(s)
- Totalizadores: cantidad de ventas, monto total, promedio por venta
- Sin borrado — es solo lectura; no incluir destroy()

[Restricciones]
- Seguir el patrón MVC del módulo de productos como referencia exacta
- DataTables en vista: datos desde PHP, filtros server-side (no AJAX)
- Acceso restringido: Administrador ve todas, Vendedor ve solo las suyas (WHERE usuario_id = ?)
- Métodos de controlador: index(), generate(), export() (PDF/Excel/CSV)
- Query debe usar JOINs a tb_carrito, tb_usuarios, tb_almacen para detalles completos
- Validación de rangos de fecha en el Model: Product::getReportByDateRange()
- AlertUtils / ToastUtils para notificaciones — no Swal.fire() directo
- CSRF token en formulario GET de filtros (aunque técnicamente no necesario para GET, mantener patrón)
- TCPDF se llama con header() de descarga inline, no renderWithLayout()
- No introducir librerías nuevas

[Formato de salida]
Devuelve en este orden:

1. Rutas a agregar en routes/web.php (incluyendo restricción por rol)
2. app/Controllers/ReportController.php (métodos: index, generate, export)
3. app/Models/Sale.php o Report.php (incluyendo getReportByDateRange() reutilizable)
4. views/reports/index.php (formulario filtros + tabla DataTables)
5. public/js/modules/reports/form-validation.js (validación de fechas)
6. Documento TCPDF inline generado en ReportController::export()
7. Checklist de testing manual (rango válido, rango inválido, sin datos, acceso por rol)
```

---

## Plantilla 5 — Escribir tests para un modelo existente

Usar cuando: se quiere agregar cobertura de tests a un modelo que ya existe,
siguiendo el patrón PHPUnit 11 del proyecto.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en testing con PHPUnit 11
y SQLite in-memory para proyectos MVC custom.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC custom, PHP 8.x, Composer/PSR-4.
Testing: PHPUnit 11.x con dos suites: Unit (lógica pura) e Integration (SQLite in-memory).
Singleton PDO resuelto con Database::set(PDO) en tests/bootstrap.php y trait RefreshDatabase.
Trait aliasing para setUp: `use RefreshDatabase { setUp as setUpDatabase; }`.
DataProvider con atributos PHP 8: #[\PHPUnit\Framework\Attributes\DataProvider('method')].
Schema SQLite en tests/fixtures/schema.sqlite.sql.

Modelo a testear: [App\Models\NombreModelo]
Archivo del modelo: app/Models/[Nombre].php
Tests de referencia: tests/Integration/Models/UserRepositoryTest.php

[Tarea]
Escribir tests para [App\Models\Nombre]:

Métodos a cubrir:
- [método1]: [descripción del comportamiento esperado]
- [método2]: [descripción]

Casos edge a incluir:
- [ej: campos opcionales vacíos deben persistir como NULL]
- [ej: nextCode cuando la tabla está vacía vs con registros]

[Restricciones]
- PHPUnit 11: #[\PHPUnit\Framework\Attributes\DataProvider(...)] para DataProviders
- Integration tests: usar trait RefreshDatabase con aliasing de setUp
- Seeders mínimos — solo los registros necesarios por test
- No testear SQL exacto — probar comportamiento observable (valores retornados, tipos, nulls)
- No testear métodos con NOW() en SQLite (findByResetToken, findByRememberToken)
- Clase final, métodos snake_case: test_nombreMetodo_condicion()
- Si hay dependencias FK, semillarlas en seedDependencies() o directamente con $this->pdo->exec()

[Formato de salida]
1. Archivo completo tests/Integration/Models/[Nombre]RepositoryTest.php
   (o tests/Unit/Models/[Nombre]ValidationTest.php si es lógica pura)
2. Lista de casos cubiertos vs casos que quedan fuera y por qué
```

---

_Última actualización: v1.9.0 (2026-05-23)_
_Mantener sincronizado con AGENT.md y CLAUDE.md al iniciar cada sesión._
