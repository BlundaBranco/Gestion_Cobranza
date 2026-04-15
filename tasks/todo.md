# Tareas activas

## ✅ Completadas en esta sesión (2026-04-15)

### Task 1 — PDF recibo doble copia + concepto detallado en Excel
- Commit `4c6ecc5` pusheado a `origin master`.
- PDF duplica ORIGINAL-CLIENTE / COPIA-EMPRESA con línea de corte; fix overflow signature-line; fuente +1px.
- `IncomeExport` y `TransactionHistoryExport`: columna MENSUALIDAD ahora muestra `#N - Mes Año`.
- **Pendiente:** deploy manual del usuario + QA de Yanet.

### Task 2 — Módulo Cobros Extras
- Commit `3aef5d2` local, NO pusheado — espera feedback de Task 1.
- Columna `type` en transactions + fork en `TransactionController@store` a `storeExtra()`.
- UI con toggle Alpine, selector de Lote, `notes` requerido.
- Vista PDF separada `pdf_extra.blade.php` con doble copia.
- Reports muestran `EXTRA: {notes}`.

## 🟡 Pendiente de acción del usuario

- [ ] Deploy Task 1 al servidor (comando en `CLAUDE.md`).
- [ ] QA con Yanet — probar doble copia PDF + Excel concepto.
- [ ] Aprobación para pushear Task 2 (`git push origin 3aef5d2:master`) y deployar.

## 🟢 Pendientes de decisión

- [ ] Moneda en cobros extras: actualmente default MXN. Si Yanet cobra traspasos en USD, agregar selector USD/MXN.
- [ ] Re-evaluar si se retoma "Saldo a favor" o se cierra definitivamente.

## 🔵 Deuda técnica detectada (sin prioridad aún)

- [ ] `Transaction::status` campo defaultea `active` pero no se explicita en el `store()` — funciona por default de DB pero frágil si cambia la migración.
- [ ] `ReportController@incomeReport` tiene indentación inconsistente (línea 61) — limpieza menor.
- [ ] `docs/SPEC.md` describe Task 2 como ✅ pero todavía no está en prod — al deployar actualizar la nota.
