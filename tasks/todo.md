# Tareas activas

## 🔵 Features / Fixes en curso

### Task 1 — PDF recibo: doble copia y fixes de layout (🔨 implementado, pendiente feedback)
- [x] Duplicar `.receipt-box` en `resources/views/transactions/pdf.blade.php` (copia Cliente + copia Empresa) con `<hr>` punteado de corte entre ambas.
- [x] Corregir `.signature-line` que se sale del borde negro — ajustar márgenes/padding dentro de `.receipt-box`.
- [x] Aumentar ligeramente la fuente base sin romper el constraint de media hoja por copia.
- [x] Asegurar formato `#13 - Abril 2026` en concepto del PDF (ya existe) y **actualizar `app/Exports/IncomeExport.php`** para que la columna `MENSUALIDAD` muestre `#N - Mes Año` por cuota.

### Task 2 — Módulo "Cobros Extras" ($100 USD) ✅ implementado, pendiente deploy
- [x] Migración `2026_04_15_000001_add_type_to_transactions_table.php`.
- [x] Model `Transaction`: `type` en `$fillable`.
- [x] UI `transactions/create.blade.php` — toggle Alpine, selector de Lote, `notes` requerido.
- [x] `TransactionController@store` — fork a `storeExtra()`.
- [x] `ReportController@incomeReport`, `IncomeExport`, `TransactionHistoryExport` — extras con `EXTRA: {notes}`.
- [x] `pdf_extra.blade.php` con doble copia.
- [x] Listado con badge "EXTRA" + null-safe en moneda.

## 🟢 Post-Task 2 — pendientes de decisión
- [ ] Re-evaluar si se retoma "Saldo a favor" o se cierra definitivamente.
