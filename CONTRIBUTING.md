# Guía de Contribuciones — Sistema de Ventas

¡Gracias por tu interés en contribuir a **Sistema de Ventas**! Este documento te guía a través del proceso de
colaboración.

## Antes de empezar

Lee estos archivos de referencia según tu rol:

| Archivo                      | Propósito                                            | Para quién                  |
|------------------------------|------------------------------------------------------|-----------------------------|
| [AGENT.md](AGENT.md)         | Arquitectura global, stack tecnológico, convenciones | **Todos los colaboradores** |
| [CLAUDE.md](CLAUDE.md)       | Instrucciones operacionales locales (XAMPP, BD)      | Desarrolladores locales     |
| [PROMPTS.md](PROMPTS.md)     | Plantillas de prompts efectivos con agentes IA       | Equipo de desarrollo        |
| [CHANGELOG.md](CHANGELOG.md) | Historial de cambios y versiones                     | Todos                       |

> **Requisito crítico:** No se aceptarán PRs sin haber leído AGENT.md. Es la fuente de verdad del proyecto.

---

## Flujo de Contribución

### 1️⃣ Abre un Issue

Antes de codificar, abre un **issue** describiendo:

- **Tipo:** Bug fix, Feature, Refactor, Docs
- **Descripción clara** de qué cambio propones y por qué
- **Contexto:** Si aplica, menciona módulo, tabla BD, o historia de usuario

Espera a que el equipo apruebe antes de empezar a trabajar.

### 2️⃣ Fork + Branch

```bash
# 1. Fork el repositorio en GitHub

# 2. Clona tu fork localmente
git clone https://github.com/TU_USUARIO/Sistema_de_Ventas_PHP.git
cd Sistema_de_Ventas_PHP

# 3. Crea rama con nombre descriptivo
git checkout -b feature/nombre-descriptor  # Features nuevas
git checkout -b fix/nombre-descriptor      # Bug fixes
git checkout -b docs/nombre-descriptor     # Documentación

# 4. Mantén sincronización
git remote add upstream https://github.com/WorkTeam01/Sistema_de_Ventas_PHP.git
git fetch upstream
git rebase upstream/develop
```

### 3️⃣ Desarrolla

**Estructura de archivos:**

Sigue la arquitectura MVC descrita en [AGENT.md](AGENT.md):

```
Para nuevo módulo [nombre]:
├── app/Controllers/[Nombre]Controller.php
├── app/Models/[Nombre].php
├── app/Middleware/[Nombre]Middleware.php (si aplica)
├── views/[modulo]/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── show.php (si aplica)
├── public/js/modules/[modulo]/
│   ├── form-validation.js
│   └── handler.js
└── public/css/modules/[modulo]/
    └── styles.css
```

**Convenciones de código:**

- **SQL:** Siempre usar placeholders `?` con `execute([$var])`; nunca concatenación
- **Controladores:** Solo estos 6 métodos estándar: `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`.
  No inventar métodos como `showCreate()`, `toggle()`, `activate()`, etc.
- **Modelos:** Extender de `App\Core\Model`; implementar `isReferenced()` si hay FKs
- **Vistas:** Usar `renderWithLayout()` desde controlador; no variable `Auth::` directo
- **JavaScript:** Usar `json_encode()` para pasar datos PHP → JS; nunca comillas simples
- **Seguridad:** `htmlspecialchars()` en outputs HTML; `password_hash()` para contraseñas

Ver más detalles en **Convenciones de Seguridad** de [AGENT.md](AGENT.md).

**Validación en ambos lados:**

- Frontend: jQuery Validate antes de POST
- Backend: `$this->validate()` en el controlador (vía `App\Core\Controller`)

**Notificaciones:**

- Usar `AlertUtils` / `ToastUtils` desde `public/js/core/sweetalert-utils.js`
- Nunca `alert()` nativo ni `Swal.fire()` directamente

### 4️⃣ Testing Manual

Antes de hacer commit, verifica:

- ✅ Listado (DataTables, filtros, búsqueda)
- ✅ Crear: formulario válido + error si campos vacíos
- ✅ Editar: carga datos actuales, guarda cambios
- ✅ Eliminar: valida `isReferenced()`, muestra toast/alerta
- ✅ CSRF: token presente en todos los formularios POST
- ✅ Seguridad: sin SQL injection, XSS, campos sensibles protegidos
- ✅ Acceso por rol: middleware restringe correctamente
- ✅ Edge cases: campos null, strings largos, caracteres especiales

### 5️⃣ Commit con Conventional Commits

Usa el formato convencional para mensajes de commit:

```bash
git commit -m "type(scope): description"
```

**Tipos válidos:**

| Tipo       | Descripción                      | Ejemplo                                         |
|------------|----------------------------------|-------------------------------------------------|
| `feat`     | Feature nueva                    | `feat(products): agregar filtro por categoría`  |
| `fix`      | Bug fix                          | `fix(sales): corregir cálculo de descuento`     |
| `refactor` | Mejora sin cambiar funcionalidad | `refactor(auth): simplificar lógica de sesión`  |
| `docs`     | Documentación                    | `docs: actualizar instrucciones de instalación` |
| `chore`    | Tareas (deps, config)            | `chore: actualizar composer.json`               |
| `test`     | Tests (cuando se agreguen)       | `test(products): agregar casos de validación`   |

**Ejemplos completos:**

```bash
git commit -m "feat(users): agregar vista de perfil de usuario"
git commit -m "fix(cart): corregir cálculo de total en carrito"
git commit -m "docs(readme): agregar seccion de troubleshooting"
```

### 6️⃣ Push + Pull Request

```bash
# Push a tu fork
git push origin feature/nombre-descriptor

# Abre PR en GitHub
# - Title: usa el mismo formato que commit ("feat(scope): description")
# - Description: menciona el issue (#123), explica cambios, adjunta screenshots si aplica
# - Requiere que pase checks automáticos
```

**Plantilla de PR:**

```markdown
## 🎯 Objetivo

Resuelve #123: [descripción del issue]

## 📝 Cambios

- [ ] Agregar/Modificar funcionalidad X
- [ ] Actualizar tabla BD Y
- [ ] Agregar validación Z

## 📸 Screenshots (si aplica)

[Pega imágenes del UI]

## ✅ Checklist

- [ ] Leí AGENT.md y CLAUDE.md
- [ ] Código sigue convenciones del proyecto
- [ ] Validación frontend + backend
- [ ] Testing manual completado
- [ ] Sin SQL injection, XSS, CSRF
- [ ] CHANGELOG.md actualizado
- [ ] Commit messages en formato convencional
```

### 7️⃣ Code Review

- El equipo revisará tu código (2-3 días máximo)
- Si hay cambios pendientes, actualiza con commits adicionales
- Una vez aprobado, se mergea a `develop`

---

## Estructura de Commits por Impacto

### Bug Fix (pequeño)

```bash
git commit -m "fix(sales): corregir total negativo en descuento"
```

### Feature pequeña (una tabla, un CRUD)

```bash
git commit -m "feat(inventory): agregar vista de movimientos de stock"
git commit -m "docs(readme): agregar seccion de inventory"
```

### Feature grande (múltiples tablas/modules)

```bash
# Commit 1: Tabla + Modelo
git commit -m "feat(reports): scaffolding tabla tb_reportes y modelo Report"

# Commit 2: Controlador + Vistas
git commit -m "feat(reports): controller, vistas index/create de reportes"

# Commit 3: Rutas + Middleware
git commit -m "feat(reports): registrar rutas y middleware de acceso"

# Commit 4: Validación + tests manual
git commit -m "feat(reports): agregar validación y testing de borrado"

# Commit 5: Documentación
git commit -m "docs: actualizar CLAUDE.md y CHANGELOG.md con módulo reportes"
```

---

## Reglas Prohibidas

❌ **Nunca hacer esto:**

- Modificar `public/templates/` (AdminLTE) — no está para cambios
- Concatenar variables en SQL: `"WHERE id = '$id'"` → usar `?` con `execute()`
- `alert()` nativo o `Swal.fire()` directo — usar `AlertUtils`
- Borrado lógico con `is_active` — este proyecto usa **borrado físico** con `isReferenced()`
- Inventar métodos en controladores más allá de los 6 estándar
- Commitear `.env` (credentials) — nunca, nunca, nunca
- PRs sin issue abierto primero — siempre discute antes
- Código sin validación backend — frontend sola no es suficiente

---

## Ayuda y Preguntas

- 📧 **Documentación:** Abre una discussion en GitHub
- 🐛 **Reporte bugs:** Issue con etiqueta `bug`
- 💡 **Sugerencias:** Issue con etiqueta `enhancement`
- 💬 **Discusiones:** Usa la sección Discussions de GitHub

---

## Reconocimiento

Los colaboradores que mergeen features significativas serán agregados a `README.md` bajo sección **Contributors**.

---

_Última actualización: Abril 2026_
_Sigue las prácticas de AGENT.md y CLAUDE.md — son la fuente de verdad._
