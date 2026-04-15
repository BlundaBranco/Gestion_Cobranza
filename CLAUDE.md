# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Sistema de Gestión de Cobranza Inmobiliaria — SaaS web app for real estate sales financing and collection management. Replaces manual Excel workflows with automated payment plans, interest tracking, folio/receipt generation, and income reports.

## Stack

- **Backend:** Laravel 12 (PHP 8.2+), Eloquent ORM, Laravel Breeze (auth)
- **Frontend:** Blade + Alpine.js (reactivity), Tailwind CSS 3, Vite
- **DB:** SQLite (local/demo) or MySQL (production)
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** Maatwebsite Excel 4
- **Testing:** PHPUnit 11

## Commands

```bash
# Development (runs artisan serve + queue + logs + vite concurrently)
composer run dev

# Or separately
php artisan serve
npm run dev

# Build for production
npm run build

# Migrate & seed
php artisan migrate --seed

# Tests
composer test
# or
php artisan test

# Single test
php artisan test --filter=TestClassName

# Code style
./vendor/bin/pint
```

## Architecture

### Core Domain Model

```
Owner (Socio) ──< Lot ──< PaymentPlan ──< Installment
                              │
                              └──< Transaction (Pago/Folio) >── Installment (pivot: amount_applied)
Client ──< Lot
```

- **Owner (Socio):** Property partner. Has own folio sequence (`sequence` field). Revenue isolated per owner in reports.
- **Lot (Lote):** Property identified by `block_number` + `lot_number`. Has `LotOwnershipHistory` for transfers.
- **PaymentPlan:** Financing structure (enganche + cuotas, currency, start_date). Auto-generates Installments on creation.
- **Installment (Cuota):** Individual payment with `due_date`, `status` (pendiente/pagada/vencida/condonada), `interest_amount`, `months_overdue`.
- **Transaction:** A payment (folio). Applies to multiple installments via pivot table. PDF folio auto-generated with owner-specific sequence.

### Multi-Owner Access Control

Users belong to Owners via `owner_user` pivot. Views/reports filter by owner. Policies enforce per-resource authorization.

### Financial Logic (Critical Area)

- **Interest:** 10% monthly on overdue installments. Calculated and updated via scheduled job / Artisan commands.
- **Partial payments:** `transactions_installments` pivot stores `amount_applied` per installment.
- **Folios:** Sequential per Owner (not global). Tracked in `owners.sequence`.
- **Months overdue:** Tracked on installments for display and interest calculation.

### Key Controllers

| Controller | Responsibility |
|---|---|
| `TransactionController` | Register payments, generate PDF folios |
| `InstallmentController` | CRUD cuotas, bulk update, condone interest |
| `PaymentPlanController` | Create/edit plans, currency management |
| `ReportController` | Income reports, overdue tracking, Excel exports |
| `DashboardController` | Metrics, overdue alerts, recent activity |

### Helpers (`app/Helpers/`)

- `currency_format()` — format monetary values
- `number_to_words_es()` — amounts to Spanish words (used in PDF folios)
- WhatsApp message generator for payment reminders

### Artisan Commands

Located in `app/Console/Commands/` — used for data migrations and one-off fixes (installment date corrections, amount recalculations). Check before writing new migration logic.

## Key Conventions

- Alpine.js handles real-time calculations in forms (installment totals, payment distribution).
- Select2 used for searchable dropdowns on large datasets (clients, lots).
- Modals used for bulk installment updates and payment processing — state managed via Alpine.
- Tailwind custom theme: `primary` (blue), `success` (green), `danger` (red), `warning` (yellow).
- DB sessions and cache use database driver (no Redis).
- Queue driver is `database` — jobs are not async by default.

## Critical Rules — NEVER

- **NUNCA** correr `php artisan route:cache` en producción — rompe rutas bajo FPM.
- **NUNCA** deployar código que referencia migraciones sin correr `php artisan migrate` antes en prod.
- **NUNCA** usar `detach()` en el pivot `installment_transaction` al cancelar una Transaction — usar `updateExistingPivot(amount_applied=0)` para preservar auditoría y cadena owner.
- **NUNCA** generar folios fuera de `DB::transaction` — el `lockForUpdate()` en `OwnerSequence::getNextValue()` solo es efectivo dentro de la transacción.
- **NUNCA** configurar Pint hooks o auto-format — el usuario quiere diffs mínimos y estrictos.
- **NUNCA** deploy automático por SSH — dar los comandos al usuario para que los copie.

## Negocio — contexto clave

- Cliente: Yanet (comunicación en español, tono formal, nunca mencionar IA).
- Folios son **por Owner**, no globales — colisiones entre secciones son normales.
- Interés = 10% mensual sobre cuotas vencidas, acumulativo, persistido en `installments.interest_amount`.
- Saldo a favor (`credit_balance`) está **en pausa** tras incidente 2026-03-30. No re-implementar sin análisis.

## Documentación

- `docs/ARCHITECTURE.md` — diagrama de dominio, flujo de pago, decisiones arquitectónicas
- `docs/SPEC.md` — especificación funcional completa con estado por feature
- `tasks/todo.md` — tareas activas priorizadas
- `tasks/lessons.md` — lecciones aprendidas (actualizar tras cada corrección)

## Meta-reglas (NUNCA borrar)

- Después de CUALQUIER corrección del usuario, actualizar `tasks/lessons.md` con una regla atómica.
- Si la lección es convención o estilo, además actualizar este CLAUDE.md.
- Antes de implementar algo que toque >3 archivos, escribir mini-spec y pedir confirmación.
- Después de cada tarea completada, marcarla en `tasks/todo.md`.
- Commits atómicos, en español, descriptivos. Sin prefijos convencionales salvo `Fix:` / `feat:` ya establecidos en el repo.
- Si no estás seguro, PREGUNTÁ. No asumas.
- Idioma: comunicación con el usuario en español, código/identifiers en inglés.
