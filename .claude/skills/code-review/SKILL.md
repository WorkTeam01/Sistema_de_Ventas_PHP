---
name: code-review
description: 'Revisa código PHP MVC de Sistema de Ventas PHP antes de integrarlo a master. Usar cuando el usuario pida revisar código, un PR o validar una feature antes del merge, o mencione "code review", "revisar código", "antes del merge" o "PR".'
license: MIT
allowed-tools: Bash, Read
---

# Code Review — Sistema de Ventas PHP

## Qué cubre

Revisión estructurada de un cambio antes de integrarlo a `master` (única rama del proyecto). El checklist sale de
`docs/constitution.md` (principios no negociables) y de las prohibiciones de `AGENTS.md`; ante duda, esos dos
archivos mandan sobre esta lista.

## Cómo correrla

```bash
git status                       # cambios sin commitear
git diff master --name-only      # archivos de la rama
git diff master                  # diff completo
composer test                    # la suite debe estar en verde
```

Revisar cada archivo contra el checklist y agrupar los hallazgos por severidad antes de reportar.

---

## Checklist

### 1. Seguridad y datos — bloquea el merge

- [ ] SQL siempre con placeholders `?` y `execute([...])`; nada de variables interpoladas (`$interval`/`$limit`
      solo con cast `(int)`)
- [ ] Borrado **físico** protegido por `isReferenced()` antes del `DELETE`; nunca borrado lógico con `is_active`
- [ ] Montos, totales y stock calculados desde la BD dentro de la transacción, nunca desde el `$_POST`; las
      operaciones irreversibles usan el snapshot de BD, no el POST
- [ ] El stock solo se escribe en `Sale`, `SaleReturn`, `Purchase`, `StockAdjustment` y `Product::createProduct`
      (lo vigila `StockWritesAuditTest`)
- [ ] CSRF (`Auth::generateCsrfToken()` / `validateCsrfOrFail()`) en todo formulario o petición POST
- [ ] Contraseñas con `password_hash()` / `password_verify()`; mínimo de 8 caracteres en las nuevas
- [ ] Salidas HTML escapadas con `htmlspecialchars()`; datos a JS con `json_encode()`, nunca interpolados
- [ ] Sin secretos en el diff (`.env`, `.env.testing`, credenciales, tokens)

### 2. Autorización — bloquea el merge

- [ ] Todo endpoint no público declara `['auth', 'can:permiso']` en `routes/web.php`
- [ ] Con permisos `*_all` (`view_sales_all`, `view_purchases_all`), el filtro por dueño se aplica al listado **y** a
      `show`/`edit`/`update`/`destroy`; el rechazo usa `Controller::forbidden()`
- [ ] Quien puede crear/editar usuarios o asignar permisos no puede otorgarse `is_superadmin`
      (`blocksSuperadminEscalation()` / `Role::grantsSuperadmin()`)
- [ ] Borrar un recurso inexistente es un no-op exitoso; solo "existe y es ajeno" se rechaza
- [ ] Vistas y sidebar usan `$can[...]` entregado por el controlador, nunca `Auth::` ni los proxies de rol
      `$isAdmin`/`$isSeller`/`$isBuyer` para permisos granulares

### 3. Convenciones MVC — bloquea el merge

- [ ] Métodos de controlador solo los estándar (`index`, `create`, `store`, `edit`, `update`, `destroy`) y los
      auxiliares ya documentados en `AGENTS.md`; nada de `toggle`, `activate`, `complete`
- [ ] Fat model, thin controller, dumb view: la lógica de negocio vive en el modelo; la vista no hace cálculos
      (`foreach` con `+=`)
- [ ] Vistas con `renderWithLayout()`, breadcrumb (`<section class="content-header">`) y assets propios por
      `$pageStyles` / `$pageScripts`
- [ ] Moneda con `APP_CURRENCY_SYMBOL`; sin `"Bs."` fijo ni una constante llamada `CURRENCY_SYMBOL`
- [ ] Identificadores nuevos en inglés; textos al usuario en español; sin renombrar identificadores históricos
- [ ] No se tocan `public/templates/`, `public/css/lib/` ni `public/js/lib/`

### 4. Frontend — advertencia, se discute antes del merge

- [ ] Sin `alert()`, `Swal.fire` ni `onclick` inline en vistas: usar `AlertUtils` / `ToastUtils`
- [ ] Eliminaciones con el patrón del módulo (página dedicada, formulario oculto `#formEliminar` o modal + AJAX)
- [ ] DataTables con datos cargados desde PHP, sin fuente AJAX en la carga inicial
- [ ] Validación doble: jQuery Validate en el cliente **y** validación en PHP
- [ ] Assets propios con `?v=<?= APP_VERSION ?>`; si cambió JS/CSS core, `APP_VERSION` subió
- [ ] Si toca UI: axe-core sin violaciones en modo claro y oscuro (incluye modales y estados vacíos)

### 5. Tests y esquema — bloquea el merge

- [ ] `composer test` en verde, sin tests saltados por falta de MariaDB
- [ ] Lógica nueva de modelo/helper con su test (no se testean Controllers, Middleware, Vistas ni Router)
- [ ] Tests que comparan fechas contra la BD usan expresiones SQL (`NOW() - INTERVAL …`), no `date()` de PHP
- [ ] Cambios en `database/schema.sql` reflejados en `tests/fixtures/schema.sqlite.sql` y, si hace falta, en una
      migración reejecutable bajo `database/migrations/`

### 6. Calidad y comentarios — advertencia

- [ ] Casos límite cubiertos: registro inexistente, usuario sin permiso, entradas vacías o malformadas, doble envío
- [ ] Sin lógica duplicada que debería ser un método compartido del modelo
- [ ] Comentarios pocos, en español y sobre el porqué; sin narrar el código ni citar specs, tareas o tickets
      (`FR-…`, `spec 00X`)

### 7. Git — bloquea el merge

- [ ] Commits Conventional Commits de una línea (tipo y scope en inglés, descripción en español), hechos con la
      skill `git-commit`, sin co-autor
- [ ] Commits atómicos por área lógica, sin mezclar limpieza de comentarios con cambios de código
- [ ] Tags de versión sin prefijo `v` (`1.18.3`)
- [ ] Nada de `.env` en el diff

---

## Formato de salida

```
## Code Review — [rama o PR]

### ✅ Lo bueno
- [Al menos 2 cosas concretas bien hechas]

### ⚠️ Observaciones (no bloquean)
- [Mejoras deseables]

### 🚨 Corregir antes del merge
- [archivo:línea] — [problema]
  Fix: [enfoque correcto]
```

Cerrar con una de estas dos líneas:

```
### Veredicto: ✅ Aprobado — listo para integrar a master
### Veredicto: 🚨 Cambios requeridos — no integrar hasta corregir
```

---

## Hallazgos más frecuentes (revisar primero)

| #   | Dónde      | Problema                                                               | Severidad      |
| --- | ---------- | ---------------------------------------------------------------------- | -------------- |
| 1   | Modelo     | SQL armado con concatenación                                           | 🚨 Bloquea     |
| 2   | Controller | Detalle/edición/borrado sin el filtro de dueño que sí tiene el listado | 🚨 Bloquea     |
| 3   | Ruta       | Endpoint sin `can:permiso` o formulario POST sin CSRF                  | 🚨 Bloquea     |
| 4   | Modelo     | Total o stock tomado del POST en vez de la BD                          | 🚨 Bloquea     |
| 5   | Vista      | `Auth::` o cálculos en la vista; salida sin escapar                    | 🚨 Bloquea     |
| 6   | Modelo     | `DELETE` sin `isReferenced()` o borrado lógico                         | 🚨 Bloquea     |
| 7   | JS         | `alert()`, `Swal.fire` inline o `onclick` en la vista                  | ⚠️ Advertencia |
| 8   | Tests      | Fecha límite calculada en PHP y comparada con `NOW()` de la BD         | ⚠️ Advertencia |
| 9   | Assets     | JS/CSS propio sin `?v=APP_VERSION` o `APP_VERSION` sin subir           | ⚠️ Advertencia |
| 10  | Git        | Commit multilínea, uno por fix o mezclando comentarios con código      | ⚠️ Advertencia |
