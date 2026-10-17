@AGENTS.md

# CLAUDE.md — Guía Local para Claude Code

> `AGENTS.md` (importado arriba) es la fuente única de arquitectura, convenciones, stack, BD, rutas, testing y
> prohibiciones. Aquí va solo lo operativo de Claude Code. Arranque local, BD, `.env` y permisos de archivos:
> [docs/guides/desarrollo.md](docs/guides/desarrollo.md).

## Skills del proyecto

- `git-commit` (`.claude/skills/`) — usar **siempre** para commitear; nunca `git commit` directo. Commits atómicos por categoría lógica.
- `code-review` (`.claude/skills/`) — revisión antes de merge a `master`; su checklist sale de `docs/constitution.md` y de las prohibiciones de `AGENTS.md`.

## Flujo SDD

Features nuevas siguen Spec-Driven Development (`/sdd:constitution`, `/sdd:spec`, `/sdd:clarify`, `/sdd:plan`, `/sdd:tasks`, `/sdd:implement <n>`, `/sdd:validate`, `/sdd:change`; documentos derivados con `/sdd:docs`). Artefactos en `docs/constitution.md`, `docs/roadmap.md` y `specs/NNN-<slug>/`. Cuándo aplica: [AGENTS.md § Planificación de features](AGENTS.md).

---

_Última actualización: 2026-10-17 — 1.19.3_
