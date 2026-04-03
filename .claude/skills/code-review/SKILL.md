---
name: code-review
description: 'Review PHP MVC code before merging a branch in SistemaReservasHospital. Use when the user asks to review code, check a PR, validate a feature before merge, or mentions "code review", "revisar código", "antes del merge", or "PR". Triggers automatically when a feature branch is ready to merge to develop.'
license: MIT
allowed-tools: Bash, Read
---

# Code Review — SistemaReservasHospital

## Overview

Structured code review process for SistemaReservasHospital before any branch merge to develop. Covers security,
conventions, logic, and the project's specific patterns that are easy to miss.

## How to Run the Review

```bash
# See all changed files in the branch
git diff dev --name-only

# Full diff against dev
git diff dev

# Check for any staged but uncommitted changes
git status
```

Review each changed file against the checklist below. Group findings by severity before reporting.

---

## Review Checklist

### 1. Security — block merge if any fail

- [ ] No SQL concatenation — all queries use PDO prepared statements
- [ ] No plain-text passwords — `password_hash()` on save, `password_verify()` on check
- [ ] Output is escaped — `htmlspecialchars()` on all user-generated output
- [ ] No secrets in code — `.env`, `.mcp.json`, passwords, tokens not committed
- [ ] Middleware called — every controller method starts with `Middleware::auth()`, `Middleware::admin()`, or
  `Middleware::guest()` as appropriate
- [ ] No DELETE SQL — soft deletes only via `is_active = 0`
- [ ] CSRF token present on all POST forms

---

### 2. MVC Conventions — block merge if any fail

- [ ] No invented controller methods — only these are valid: `index()`, `showCreate()`, `store()`, `showEdit()`,
  `update()`, `destroy()`, `toggle()`, `show()`, `checkName()`
- [ ] No separate status methods — `complete()`, `cancel()`, `confirm()` as standalone methods are forbidden.
  `toggle()` handles active/inactive state changes:
- [ ] Business logic in Model, not Controller — controllers orchestrate, models contain the logic. If a method is
  longer than ~30 lines in a controller, it probably belongs in the model.
- [ ] DataTables loaded from PHP — no AJAX data source on initial page load. Filters via GET parameters
  server-side, not JavaScript-side filtering:
- [ ] Correct layout — all views use `renderWithLayout()` through the base Controller. No standalone HTML files
  that bypass the main layout.

---

### 3. Logic & Quality — flag as warning, discuss before merge

- [ ] Dual validation — every form has both frontend (jQuery) and backend (PHP) validation. Frontend alone is
  never sufficient.
- [ ] Status badge colors consistent across all views:
- [ ] SweetAlert2 used — no native `alert()` or `confirm()` anywhere in JS. Destructive actions always show a
  confirmation dialog before proceeding.
- [ ] Edge cases covered — check the acceptance criteria of the RF and verify that the implementation handles the
  non-happy-path scenarios:
    - What happens if the record doesn't exist?
    - What happens if the user doesn't have permission?
    - What happens with empty or malformed inputs?
- [ ] RF dependencies respected — if the feature touches availability logic, verify RF15 (doctor schedules) is
  integrated before RF05 validation changes. `Appointment::checkAvailability()` should be reused, not duplicated.
- [ ] No duplicate code — if the same logic appears in two places, it belongs in a shared Model method.

---

### 4. Git & Branch — block merge if any fail

- [ ] Branch name follows `feature/rfXX-descripcion` convention
- [ ] Commits follow Conventional Commits with correct scope:
  `feat(appointments): add edit form with availability check (RF10)`
- [ ] No commits to `main`/`develop` directly — all changes via PR
- [ ] No `.env` or `.mcp.json` in the diff

---

## Output Format

Always structure the review response in this exact format:

```
## Code Review — [branch name] ([RF ID])

### ✅ What's good
- [At least 2 specific things done well]

### ⚠️ Observations (non-blocking)
- [Improvements that would be nice but don't block merge]

### 🚨 Must fix before merge
- [File:line] — [Problem description]
  [Code showing the issue]
  Fix: [Code showing the correct approach]
```

If there are no blocking issues, end with:

```
### Verdict: ✅ Approved — ready to merge to develop
```

If there are blocking issues, end with:

```
### Verdict: 🚨 Changes required — do not merge until fixed
```

---

## Quick Reference — Most Common Issues

These are the issues found most frequently in this project. Check these first:

| # | File type  | Issue                                                              | Severity   |
|---|------------|--------------------------------------------------------------------|------------|
| 1 | Controller | Status method created separately (`complete()`, `cancel()`, `activate()`) | 🚨 Block |
| 2 | Controller | Missing `Middleware::` call at method start                        | 🚨 Block   |
| 3 | Model      | SQL built with string concatenation                                | 🚨 Block   |
| 4 | View       | User output not escaped with `htmlspecialchars()`                  | 🚨 Block   |
| 5 | JS         | DataTables using AJAX data source instead of PHP data              | 🚨 Block   |
| 6 | JS         | Native `alert()` or `confirm()` instead of SweetAlert2             | ⚠️ Warning |
| 7 | View       | Badge color doesn't match status convention                        | ⚠️ Warning |
| 8 | Controller | Business logic that belongs in the Model                           | ⚠️ Warning |
| 9 | Any        | Frontend validation without matching backend validation            | ⚠️ Warning |
| 10 | Git       | Commit messages not following Conventional Commits                 | ⚠️ Warning |