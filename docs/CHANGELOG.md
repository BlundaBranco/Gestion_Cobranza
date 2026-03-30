# CHANGELOG — Gestión de Cobranza (Project-specific)

Format: [DATE] [TYPE] Description — Files affected

---

## 2026-03-26 — Session Init / Context Loaded

**[FEATURE] Excel Exports — Multi-currency + SoftDeletes support**
- `IncomeExport`: Added Capital vs Interest column breakdown (DLLS/PESOS/INT.DLL/INT.PESO), folio range filter, includes cancelled transactions (`withTrashed`).
- `ClientAccountExport`: Updated column format.
- `OverdueInstallmentsExport`: Updated column format.

**[BUGFIX] PDF Receipt (`transactions/pdf.blade.php`)**
- Added Capital vs Interest breakdown per installment line.
- Dynamic font-sizing for long concept text.
- Handling of excess payments (saldo a favor / anticipo).

**[FEATURE] Client — Additional phone fields**
- Added `phone_label` and `additional_phones` to `Client` model and migration.

**[FEATURE] Installments — Bulk edit + manual interest override**
- `BulkInstallmentUpdateController`: Allows editing multiple installments at once.
- Manual override of `interest_amount` without triggering auto-calculation.

**[FEATURE] Folio system — Multi-issuer (per Owner)**
- `OwnerSequence` model + table: sequential folio counter per Owner.
- `TransactionController@store`: Assigns folio from Owner's sequence using `lockForUpdate()` inside DB transaction.

**[FEATURE] User Management**
- `UserController`: CRUD for users, assign to Owner via `owner_user` pivot.

**[BUGFIX] Folios — Fixed numbering and Excel export sync**
- Folio numbers now correctly pad to 6 digits per owner sequence.

---

## Template for future entries

```
## YYYY-MM-DD — [Short title]

**[BUG|FEATURE] Module — Description**
- Detail 1
- Detail 2
- Files: `app/...`, `resources/...`
```
