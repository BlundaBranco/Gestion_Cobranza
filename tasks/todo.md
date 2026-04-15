# Tareas activas

## 🔵 Features / Fixes en curso

### Task 1 — PDF recibo: doble copia y fixes de layout (🔨 implementado, pendiente feedback)
- [x] Duplicar `.receipt-box` en `resources/views/transactions/pdf.blade.php` (copia Cliente + copia Empresa) con `<hr>` punteado de corte entre ambas.
- [x] Corregir `.signature-line` que se sale del borde negro — ajustar márgenes/padding dentro de `.receipt-box`.
- [x] Aumentar ligeramente la fuente base sin romper el constraint de media hoja por copia.
- [x] Asegurar formato `#13 - Abril 2026` en concepto del PDF (ya existe) y **actualizar `app/Exports/IncomeExport.php`** para que la columna `MENSUALIDAD` muestre `#N - Mes Año` por cuota.

### Task 2 — Módulo "Cobros Extras" ($100 USD) ⬜ pendiente de feedback de Task 1
- [ ] Migración: agregar columna `type` a `transactions` (string, default `installment`).
- [ ] Model `Transaction`: `type` en `$fillable`.
- [ ] UI `transactions/create.blade.php`: toggle, selector de Lote, `notes` requerido.
- [ ] `TransactionController@store`: fork a `storeExtra()` — genera folio vía `OwnerSequence`, NO toca pivot ni installments.
- [ ] `ReportController@incomeReport`, `IncomeExport`, `TransactionHistoryExport`: incluir extras con concepto `"EXTRA: {notes}"`.
- [ ] Vista PDF separada `pdf_extra.blade.php` — doble copia, sin desglose de cuotas.
- [ ] Listado `transactions/index.blade.php`: badge "EXTRA" + null-safe en moneda.

## 🟢 Post-Task 2 — pendientes de decisión
- [ ] Re-evaluar si se retoma "Saldo a favor" o se cierra definitivamente.
