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
