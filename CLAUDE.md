# CLAUDE.md

Guía para trabajar en este repo. Si crece, compactar — mantener ≤100 líneas.

## Proyecto

SaaS de gestión de cobranza inmobiliaria (cliente: **Yanet**). Planes de pago, cuotas, folios por socio, reportes de ingresos. En producción en `gestioncobranza.duckdns.org`.

## Stack

Laravel 12 (PHP 8.2+), Blade + Alpine.js + Tailwind 3, SQLite local / MySQL prod, barryvdh/laravel-dompdf, Maatwebsite/Excel 4, PHPUnit 11. DB driver para sessions/cache/queue. Sin Redis.

## Comandos

```bash
composer run dev           # artisan serve + queue + logs + vite
npm run build              # build prod
php artisan migrate --seed
php artisan test [--filter=Name]
```

## Arquitectura (resumen)

```
Owner ──< Lot ──< PaymentPlan ──< Installment
                      └──< Transaction >── Installment (pivot: amount_applied)
Client ──< Lot
```

- **Folios por Owner** con `OwnerSequence::getNextValue()` + `lockForUpdate()` dentro de `DB::transaction`.
- **Interés:** 10% mensual sobre cuotas vencidas, persistido en `installments.interest_amount`.
- **owner_id en transactions** = snapshot al crear (no derivar por JOIN).
- **SoftDeletes en Transaction:** al cancelar, `updateExistingPivot(amount_applied=0)` — **nunca `detach()`**.

Ver `docs/ARCHITECTURE.md` y `docs/SPEC.md` para detalle completo.

## Reglas críticas — NUNCA

- **NUNCA** `php artisan route:cache` en prod — rompe rutas bajo FPM.
- **NUNCA** deploy de código que referencia migraciones sin correr `migrate --force` primero.
- **NUNCA** `detach()` en pivot `installment_transaction` al cancelar.
- **NUNCA** generar folio fuera de `DB::transaction`.
- **NUNCA** configurar Pint hooks ni auto-format — el usuario quiere diffs mínimos.
- **NUNCA** mencionar el uso de IA al cliente. El usuario se presenta como ingeniero senior.
- **NUNCA** guardar credenciales (passwords, tokens) en archivos commiteados o en memoria persistente.

## Deploy a producción

SSH key ya autorizada para `testuser@137.184.38.230`. Deploy manual por el usuario:

```bash
ssh testuser@137.184.38.230
cd /var/www/gestion_cobranza && git pull origin master && composer install --no-dev -o && sudo php artisan migrate --force && sudo php artisan optimize:clear && sudo php artisan view:clear
```

- `git pull` SIN `sudo` (sino los archivos quedan como root y rompen pulls futuros).
- `migrate --force` es obligatorio en prod (evita prompt interactivo).

## Negocio — contexto clave

- Cliente **Yanet**. Tono formal en español. Nunca bullets innecesarios en mensajes.
- Tipos de transaction: `installment` (default) y `extra` (cobros sin descuento de deuda).
- **Saldo a favor (`credit_balance`) está en PAUSA** tras incidente 2026-03-30. No re-implementar sin análisis.

## Convenciones

- Alpine.js para cálculos en vivo en formularios. Select2 para dropdowns grandes.
- Modales para bulk updates y pagos — estado con Alpine.
- Helpers: `currency_format()`, `number_to_words_es()`.
- Null-safe `?->` en vistas/exports que infieren moneda desde `installments.first()` — puede no existir (tipo `extra`).

## Meta-reglas (NUNCA borrar)

- Toda corrección del usuario → agregar regla atómica a `tasks/lessons.md`.
- Lección de convención → además actualizar este CLAUDE.md.
- Cambio que toque >3 archivos → mini-spec y pedir confirmación antes.
- Tarea completada → marcar en `tasks/todo.md`.
- Commits atómicos, en español, sin prefijos convencionales (salvo `Fix:` / `feat:` del repo).
- Si no estás seguro, PREGUNTÁ.
- Comunicación con el usuario en español, código/identifiers en inglés.

## Documentación

- `docs/ARCHITECTURE.md` — diagrama de dominio, flujos, decisiones
- `docs/SPEC.md` — spec funcional completa con estado por feature
- `tasks/todo.md` — tareas activas priorizadas
- `tasks/lessons.md` — lecciones aprendidas
- `CLAUDE.local.md` — notas de sesión (no commiteado)
