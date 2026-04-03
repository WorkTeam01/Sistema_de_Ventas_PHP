---
name: git-commit
description: 'Execute git commit with conventional commit message analysis, intelligent staging, and message generation. Use when user asks to commit changes, create a git commit, or mentions "/commit". Supports: (1) Auto-detecting type and scope from changes, (2) Generating conventional commit messages from diff, (3) Interactive commit with optional type/scope/description overrides, (4) Intelligent file staging for logical grouping'
license: MIT
allowed-tools: Bash
---

# Git Commit with Conventional Commits

## Overview

Create standardized, semantic git commits using the Conventional Commits specification. Analyze the actual diff to
determine appropriate type, scope, and message.

## Conventional Commit Format

```
<type>[optional scope]: <description>

[optional footer(s)]
```

## Commit Types

| Type       | Purpose                        |
|------------|--------------------------------|
| `feat`     | New feature                    |
| `fix`      | Bug fix                        |
| `docs`     | Documentation only             |
| `style`    | Formatting/style (no logic)    |
| `refactor` | Code refactor (no feature/fix) |
| `perf`     | Performance improvement        |
| `test`     | Add/update tests               |
| `build`    | Build system/dependencies      |
| `ci`       | CI/config changes              |
| `chore`    | Maintenance/misc               |
| `revert`   | Revert commit                  |

## Breaking Changes

```
# Exclamation mark after type/scope
feat!: remove deprecated endpoint

# BREAKING CHANGE footer
feat: allow config to extend other configs

BREAKING CHANGE: `extends` key behavior changed
```

## Project Scopes (SistemaReservasHospital)

| Scope          | Area                    |
|----------------|-------------------------|
| `auth`         | Login, logout, sessions |
| `patients`     | Patients module         |
| `doctors`      | Doctors module          |
| `appointments` | Appointments module     |
| `schedules`    | Doctor schedules        |
| `dashboard`    | KPIs and metrics        |
| `profile`      | User profile            |
| `specialties`  | Specialties             |
| `db`           | Migrations and schema   |
| `config`       | General configuration   |

## Project Git Rules

- Branch naming: `feature/descripcion`
- Never commit directly to `main` or `dev`
- Reference the RF ID in the description when applicable:
  `feat(schedules): add doctor availability config (RF15)`

## Workflow

### 1. Analyze Diff

```bash
# If files are staged, use staged diff
git diff --staged

# If nothing staged, use working tree diff
git diff

# Also check status
git status --porcelain
```

### 2. Stage Files (if needed)

If nothing is staged or you want to group changes differently:

```bash
# Stage specific files
git add path/to/file1 path/to/file2

# Stage by pattern
git add *.test.*
git add src/components/*

# Interactive staging
git add -p
```

**Never commit secrets** (.env, .mcp.json, credentials, private keys).

### 3. Generate Commit Message

Analyze the diff to determine:

- **Type**: What kind of change is this?
- **Scope**: What area/module is affected? Use the project scopes table above.
- **Description**: One-line summary in **Spanish** (present tense, imperative mood, <72 chars). The type and scope stay
  in English, only the description after `:` is in Spanish.
- **RF reference**: If the change relates to a backlog requirement, append `(RFXX)` at the end.

### 4. Propose and Wait for Approval

**NEVER commit directly.** Always show the proposed commit message first and wait for user confirmation:

```
Commit propuesto:

  <type>[scope]: <descripción en español>

¿Confirmas el commit?
```

Only execute the commit after the user explicitly approves. If the user requests changes to the message, adjust and
propose again before committing.

### 5. Execute Commit (only after approval)

**Always use a single-line commit message** — no body, no footer, no Co-Authored-By.

```bash
git commit -m "<type>[scope]: <descripción en español>"
```

If the changes require more context, fit it in the description line rather than adding a body.

## Examples

```bash
# New feature with RF reference
feat(appointments): agregar flujo wizard para nueva cita (RF05)

# Bug fix
fix(auth): corregir sesión no destruida al cerrar sesión

# Database migration
chore(db): agregar tabla doctor_schedules con columna slot_interval

# Refactor
refactor(patients): extraer lógica de búsqueda en Patient::search()

# Documentation
docs(config): actualizar AGENT.md con tareas activas del Sprint 4

# Profile module
feat(profile): agregar formulario de cambio de contraseña con indicador de fortaleza (RF09)
```

## Best Practices

- **Single line only**: no body, no footer, no Co-Authored-By attribution
- One logical change per commit
- Present tense: "add" not "added"
- Imperative mood: "fix bug" not "fixes bug"
- Reference issues inline if needed: `fix(auth): resolve token expiry (closes #123)`
- Keep description under 72 characters

## Git Safety Protocol

- NEVER update git config
- NEVER run destructive commands (--force, hard reset) without explicit request
- NEVER skip hooks (--no-verify) unless user asks
- NEVER force push to main/master
- NEVER commit .env or .mcp.json files
- If commit fails due to hooks, fix and create NEW commit (don't amend)