# PROJECT_CONTEXT.md — Gestión de Cobranza Inmobiliaria

## Overview
SaaS web app for a Mexican real estate company. Replaces manual Excel workflows with automated payment plans, interest tracking, folio/receipt generation, and income reports.

**Stack:** Laravel 12 / PHP 8.2+ / MySQL / Blade + Alpine.js / Tailwind CSS / DomPDF / Maatwebsite Excel 4

---

## Domain Model & Relationships

```
Owner (Socio)
  ├── hasMany Lot
  │     ├── belongsTo Client
  │     ├── hasMany PaymentPlan
  │     │     ├── hasMany Installment
  │     │     └── belongsTo Service
  │     └── hasMany LotOwnershipHistory
  └── hasOne OwnerSequence   ← folio counter per owner

Client
  ├── hasMany Lot
  ├── hasMany Transaction
  └── hasMany ClientDocument

Transaction (SoftDeletes)
  └── belongsToMany Installment  [pivot: amount_applied]

User
  └── belongsToMany Owner  [pivot: owner_user]
```

### Key Fields

| Model | Notable Fields |
|---|---|
| `Lot` | `owner_id`, `client_id`, `block_number`, `lot_number`, `identifier` (auto-computed), `status` |
| `PaymentPlan` | `lot_id`, `service_id`, `currency` (MXN/USD), `total_amount`, `number_of_installments`, `start_date` |
| `Installment` | `payment_plan_id`, `installment_number`, `due_date`, `amount`, `base_amount`, `interest_amount`, `status`, `months_overdue` |
| `Transaction` | `client_id`, `user_id`, `amount_paid`, `payment_date`, `folio_number`, `notes`, `status`, `cancelled_by` |
| `OwnerSequence` | `owner_id`, `current_value` |
| `Client` | `name`, `email`, `phone`, `phone_label`, `additional_phones`, `address`, `notes` |

---

## Business Rules

### Installment Statuses
- `pendiente` — not yet due or unpaid
- `vencida` — past due date, not paid
- `pagada` — fully paid
- `condonada` — forgiven/condoned

### Interest Calculation
- 10% monthly surcharge on overdue installments.
- `installments:update-status` Artisan command runs daily (cron) to:
  1. Mark past-due installments as `vencida`
  2. Add 10% interest to overdue amounts (`interest_amount` field)
  3. Auto-liquidate lots where all installments are `pagada`
- Interest can be manually overridden or condoned via BulkInstallmentUpdateController.

### Payment Math (TransactionController@store)
1. `totalValue = amount + interest_amount` (per installment)
2. `remainingBalance = totalValue - SUM(pivot.amount_applied)` from prior transactions
3. Payment is applied greedily in `due_date` ascending order.
4. Installment marked `pagada` when `newTotalPaid >= totalValue - 0.001` (float tolerance).
5. Excess payment (`amount_paid > sum of all balances`) is recorded as anticipo/saldo a favor.

### Multi-Issuer Folio Logic
- Folios are **NOT globally unique** — they are sequential **per Owner**.
- `OwnerSequence::getNextValue($ownerId)` uses `lockForUpdate()` inside the DB transaction to guarantee atomicity.
- Format: `FOLIO-000001` (6-digit zero-padded).
- Fallback (no owner): uses `transaction->id`.

### Multi-Currency
- Plans are MXN or USD. Reports/exports split totals by currency.
- Capital vs Interest breakdown is tracked per transaction in `IncomeExport`.

### Soft Deletes / Cancellations
- `Transaction` uses `SoftDeletes`.
- On cancel (`destroy`): installment statuses are reverted, pivot `amount_applied` set to 0, `status='cancelled'`, `cancelled_by=auth()->id()`.
- Cancelled transactions still appear in exports with status "Cancelado".

### Access Control
- Users access only their assigned Owner's data via `owner_user` pivot.
- Policies enforce per-resource authorization.
- Only Admins can force-delete payment plans with existing transactions.

---

## Key Controllers

| Controller | Responsibility |
|---|---|
| `TransactionController` | Register payments, generate PDF folios, cancel transactions |
| `InstallmentController` | CRUD cuotas, bulk update, condone interest |
| `PaymentPlanController` | Create/edit plans, currency management, destroy |
| `ReportController` | Income reports, overdue tracking, Excel exports |
| `DashboardController` | Metrics, overdue alerts, recent activity |
| `BulkInstallmentUpdateController` | Bulk edit installments, manual interest overrides |
| `LotTransferController` | Transfer lot ownership between clients |

## Exports (app/Exports/)

| Export | Columns |
|---|---|
| `IncomeExport` | FOLIO, NOMBRE, LOTE, MZ, DLLS, PESOS, FECHA, INT.DLL, INT.PESO, MENSUALIDAD, ESTADO |
| `ClientAccountExport` | Per-client account statement |
| `OverdueInstallmentsExport` | Overdue installments with mora detail |

## Artisan Commands (app/Console/Commands/)
- `installments:update-status` — daily cron: updates statuses, applies interest, auto-liquidates

## Helpers (app/Helpers/)
- `currency_format()` — format monetary values
- `number_to_words_es()` — amounts to Spanish words (PDF folios)
- WhatsApp message generator for payment reminders
