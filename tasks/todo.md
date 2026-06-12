# Tareas activas

## ✅ Completadas 2026-06-12 — pack 190 USD DEPLOYADO

- [x] Columna Usuario en reporte de ingresos + Excel. Commit `2d44e89`. (pack 50 USD con cortes)
- [x] Corte diario descargable para no-admin (filtro forzado por su user_id, sin totales). Commit `2d44e89`.
- [x] Marca REIMPRESIÓN en recibos desde la 2da impresión (`print_count` + badge en ambos PDFs). Commit `f10103c`. (40 USD)
- [x] Cancelación de plan de pago conservando historial + reventa de lote. Commit `8af2aff`. (100 USD)
- [x] Suite completa en verde (87/87): `username` en UserFactory + tests scaffold alineados. Commit `c39c186`.
- [x] Review adversarial del diff — 3 bugs confirmados y fixeados. Commit `0246608`.
- [x] **Deploy a prod** (`0246608`): backup + migrate --force (2 migrations) + cachés. Verificado sobre datos reales (9117 txns / 411 planes intactos, smoke render OK).
- [ ] Avisar a Yanet que está entregado (3 mensajes redactados, los envía el user).
- [ ] (Opcional) Refinar para que la auto-apertura del PDF post-cobro no cuente como impresión 1.

## ✅ Completadas 2026-06-01

- [x] Traspaso lote 212 (Guadalupe) EMIGDIO→SECC I, folios 31-46. Garantía. Deployado.
- [x] Fix reporte de ingresos OOM (error 500). Commit `5199ede`. Deployado. Yanet confirmó.
- [x] Numeración propia Electrificación + migración 14 cobros. Merge `ffcad3a`. Deployado. (110 USD)

## ✅ Completadas 2026-05-27

- [x] Sub-agrupar cuotas por servicio en form de cobro. Commit `7c73176`. Deployado.
- [x] Bloque explicativo en card de saldo a favor. Commit `7c73176`. Deployado.

## ✅ Completadas 2026-05-26

- [x] Feature saldo a favor re-implementado (garantía incidente 30/3). Deployado.
- [x] Protocolo Yanet (CLAUDE.md + engram log + hook SessionStart).

## ✅ Completadas 2026-05-25

- [x] Método de pago en recibos (cobrado 70 USD). Deployado.
- [x] Comando `lotes:traspasar` + traspaso lote 25 mz 9 → SECC I. Gratis.
- [x] Columna Socio en reporte de ingresos (20 USD). Deployado.

## 🟡 Pendiente de respuesta de Yanet

- [ ] Confirmación visual electrificación (entregado 1/6).
- [ ] Confirmación visual agrupación luz/terreno + nota saldo a favor (deployado 27/5).
- [ ] Confirmación visual método de pago (deployado 25/5).
- [ ] Excel marcado de los 110 folios duplicados (entregado 12/5).
- [ ] Aclaración "compartidos" en SECC II (folio 020063). Bloquea fase 2 del bug de duplicados.

## 🟢 Pendiente de cobro

- [ ] $190 USD pack 4 features — Yanet deposita el mes próximo (acordado 9/6).
- [ ] $110 USD electrificación — user dijo que está cubierto, verificar.
- [ ] $20 USD columna Socio (aprobado 24/5) — verificar si entró en algún pago.

## 🔵 Deuda técnica (sin prioridad)

- [x] ~~`UserFactory` sin `username`~~ — resuelto en `c39c186`, suite 84/84 verde.
- [ ] Migration `2026_04_10_014707_*` con `UPDATE...JOIN` MySQL-only — workaround vivo.
- [ ] Branches `feat/saldo-favor` y `feat/electrificacion` pueden borrarse.
- [ ] `Transaction::status` defaultea por DB, no explicitado en `store()`.
- [ ] Si Yanet usa rangos de fecha muy amplios en el reporte, considerar paginar o subir memory_limit FPM.
