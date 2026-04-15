# Especificación Funcional — Gestión de Cobranza

Estado: ✅ Completa | 🔨 En progreso | ⬜ Pendiente

---

## 1. Gestión de entidades base

### 1.1 Socios (Owners) ✅
Entidades emisoras de folios. Cada Owner tiene `OwnerSequence` propia para numeración de recibos. Los usuarios se vinculan a uno o varios Owners vía pivot `owner_user` — las vistas y reportes se filtran por ese acceso.

### 1.2 Lotes ✅
Identificados por `block_number` (manzana) + `lot_number`. Pertenecen a un Owner. El cambio de Owner se traquea en `LotOwnershipHistory`.

**Regla crítica (2026-04-10):** Si un lote tiene transacciones registradas, `OwnerTransferController@transfer` bloquea el cambio de socio. Razón: evitar que folios históricos queden bajo el socio equivocado en reportes.

### 1.3 Clientes ✅
Compradores. Pueden tener múltiples lotes. Campo `phone` se imprime en el PDF.

### 1.4 Servicios ✅
Tipo de operación (terreno, agua, etc.). Asociado a PaymentPlan.

---

## 2. Planes de pago e instalamentos

### 2.1 PaymentPlan ✅
Al crearse, genera automáticamente los Installments según:
- `enganche_amount` (opcional) → Installment `#0`
- `number_of_installments` × `monthly_amount` → Installments `#1..N`
- `currency` (MXN/USD) — **no mezclar monedas en una misma transacción**
- `start_date` determina la `due_date` de cada cuota (+1 mes por installment)

### 2.2 Installment #0 (Enganche) ✅
Se renderiza en PDF/Excel como `Eng` en vez de `#0`. No tiene tratamiento financiero especial — sigue las mismas reglas de pago y mora que cualquier cuota.

### 2.3 Estados de cuota
- `pendiente` — no pagada, no vencida
- `vencida` — no pagada, `due_date < now()`
- `pagada` — `sum(amount_applied) >= total - 0.001`
- `condonada` — admin canceló el interés

### 2.4 Interés por mora ✅
**10% mensual** sobre cuotas vencidas, acumulativo. Calculado por Artisan Command / Cron (no en tiempo real). Persistido en:
- `installment.interest_amount` — monto en dinero
- `installment.months_overdue` — meses de atraso

Un pago **cubre primero el interés**, luego capital. El desglose se muestra en el PDF por cuota.

---

## 3. Transacciones (Pagos / Folios)

### 3.1 Aplicación de pago ✅
Flujo (`TransactionController@store`, dentro de `DB::transaction`):
1. Validar cuotas seleccionadas.
2. Ordenar por `due_date` ASC.
3. Por cada cuota: aplicar `min(monto_restante, (base + interés) − ya_pagado)` al pivot `amount_applied`.
4. Marcar `pagada` si se cubrió con tolerancia `0.001`.
5. Derivar `ownerId` desde `firstInstallment.paymentPlan.lot.owner_id`.
6. Generar folio con `OwnerSequence::getNextValue($ownerId)` (usa `lockForUpdate()` — **obligatorio dentro de la transacción**).
7. Guardar `transaction.owner_id` como snapshot histórico.

### 3.2 Folios multi-emisor ✅
Formato: `FOLIO-XXXXXX` con secuencia independiente por Owner. `FOLIO-020640` puede existir legítimamente para dos Owners distintos.

**Deuda histórica conocida:** ~2 duplicados reales en SECC I y ~100 en SECC II por migración previa, irrecuperables sin afectar recibos físicos. Los folios nuevos no tienen este problema.

### 3.3 Cancelación (SoftDelete) ✅
`destroy()`:
1. Revierte status de cuotas (`pagada` → `vencida` o `pendiente` según `due_date`).
2. Hace `updateExistingPivot(amount_applied = 0)` — **nunca `detach()`**. Preserva auditoría y la cadena owner.
3. Marca `status = 'cancelled'`, guarda `cancelled_by = user_id`.
4. `SoftDelete` (`deleted_at`).

La UI:
- Lista con `withTrashed()`.
- Fila roja + badge "CANCELADO" cuando `trashed()`.
- Botón Eliminar oculto en canceladas.

### 3.4 Bloqueo de edición ✅
Los no-admin ven `payment_date` readonly. Solo admin puede editarla vía `TransactionController@update`.

### 3.5 PDF del folio ✅
`resources/views/transactions/pdf.blade.php`. Muestra:
- Logo + datos de la empresa + número de folio
- Cliente, fecha, monto en letras (helper `number_to_words_es`)
- Desglose por cuota: `#N - Mes Año | Capital | Interés | Total`
- Manzana / Lote / Pago # de N
- Firma del receptor

**Regla:** el número de cuota #0 se renderiza como `Eng`. Fuente se ajusta automáticamente si hay muchas cuotas o concepto largo.

**(Task 1 en curso):** doble copia en una página con línea de corte punteada.

### 3.6 Cobros extras ✅
Pagos que generan folio pero no descuentan deuda (traspasos, cargos administrativos).
- Columna `transactions.type` string default `installment`. Valores: `installment` | `extra`.
- Al crear tipo `extra`: exige selector de Lote (para derivar Owner y secuencia de folio) y `notes` como concepto. No se adjunta al pivot `installment_transaction`. No cambia status de cuotas.
- PDF: vista separada `pdf_extra.blade.php`, sin tabla de desglose capital/interés, con aviso "no aplica a cuotas".
- Reportes: IncomeExport y TransactionHistoryExport muestran concepto `EXTRA: {notes}`, capital = amount_paid, interés = 0.
- **Limitación actual:** moneda de extras default MXN. Si se necesita USD hay que agregar campo `currency` explícito en el form.

---

## 4. Reportes

### 4.1 Reporte de ingresos ✅
`ReportController@incomeReport` + `IncomeExport`. Filtros: rango fechas, owner, rango folios (ID).
Columnas: FOLIO, NOMBRE, LOTE, MZ, DLLS, PESOS, FECHA, INT.DLL, INT.PESO, MENSUALIDAD, ESTADO.

Divide capital e interés **por transacción**, respetando el orden cronológico de pagos previos sobre la misma cuota.

### 4.2 Cuotas vencidas ✅
`OverdueInstallmentsExport` con filtros de mora (meses vencidos).

### 4.3 Historial de pagos ✅
`TransactionHistoryExport`. Incluye canceladas. Filtros: search, owner_id. Usa `withTrashed()`.

### 4.4 Estado de cuenta del cliente ✅
Excel por lote con proyección de cuotas pendientes.

---

## 5. Admin

### 5.1 Gestión de usuarios ✅
Listar, crear (modal), cambiar password, eliminar. Vínculo a Owners vía pivot.

### 5.2 Auditoría ✅
Listado de transacciones canceladas, filtros por fecha/owner.

---

## 6. Funcionalidades pausadas / no-goals

### Saldo a favor (Prepayments) ⛔ EN PAUSA
Código revertido (ex-commit `b96b594`). Migraciones `credit_balance` quedan Pending en el repo — inofensivas mientras no se referencian. No re-implementar sin analizar el incidente de 2026-03-30 (ver `memory/project.md`).
