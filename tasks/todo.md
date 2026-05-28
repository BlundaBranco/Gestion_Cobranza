# Tareas activas

## ✅ Completadas 2026-05-27

- [x] Sub-agrupar cuotas por servicio en form de cobro (`transactions/create.blade.php`). Commit `7c73176`. Deployado.
- [x] Bloque explicativo en card de saldo a favor (`clients/show.blade.php`). Commit `7c73176`. Deployado.
- [x] Mensaje a Yanet redactado y entregado al user.

## ✅ Completadas 2026-05-26

- [x] Feature saldo a favor re-implementado (gratis, garantía del incidente 30/3). Commits `6c40dbe`, `95700e2`. Deployado.
- [x] Protocolo Yanet (CLAUDE.md + engram log + hook SessionStart).

## ✅ Completadas 2026-05-25

- [x] Método de pago en recibos (cobrado 70 USD). Deployado.
- [x] Comando `lotes:traspasar` + traspaso lote 25 mz 9 → SECC I. Gratis.
- [x] Columna Socio en reporte de ingresos. Aprobado 20 USD, pendiente de cobro.

## 🟡 Pendiente de respuesta de Yanet

- [ ] Confirmación visual agrupación luz/terreno + nota explicativa saldo a favor (deployado 27/5).
- [ ] Confirmación visual método de pago (deployado 25/5).
- [ ] Excel marcado de los 110 folios duplicados (entregado 12/5).
- [ ] Aclaración "compartidos" en SECC II (folio 020063 en Margarita y José María). Bloquea fase 2 del bug de duplicados.

## 🟢 Pendiente de cobro

- [ ] $20 USD por columna Socio en reporte (aprobado 24/5, deployado 25/5, no cobrado).

## 🔵 Deuda técnica (sin prioridad)

- [ ] `UserFactory` sin `username` — rompe 22 tests scaffold de Auth.
- [ ] Migration `2026_04_10_014707_*` con `UPDATE...JOIN` MySQL-only — workaround vivo (envuelto en `if (DB::getDriverName() !== 'sqlite')`).
- [ ] Branch `feat/saldo-favor` puede borrarse (`git branch -d feat/saldo-favor`).
- [ ] `Transaction::status` defaultea por DB, no explicitado en `store()`.
