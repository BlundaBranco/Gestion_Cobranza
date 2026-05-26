# CLAUDE.md

Guía para trabajar en este repo. Si crece, compactar — mantener ≤100 líneas.

## Proyecto

SaaS de gestión de cobranza inmobiliaria. Planes de pago, cuotas, folios por socio, reportes de ingresos.

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
- **NUNCA** guardar credenciales (passwords, tokens) en archivos commiteados o en memoria persistente.

## Deploy a producción

Deploy manual vía SSH:

```bash
cd /var/www/gestion_cobranza && git pull origin master && composer install --no-dev -o && sudo php artisan migrate --force && sudo php artisan optimize:clear && sudo php artisan view:clear
```

- `git pull` SIN `sudo` (sino los archivos quedan como root y rompen pulls futuros).
- `migrate --force` es obligatorio en prod (evita prompt interactivo).

## Negocio — contexto clave

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

## Protocolo Yanet (CRÍTICO — leer SIEMPRE al inicio de cada sesión)

**Antes de tomar cualquier decisión, redactar cualquier mensaje, o cotizar cualquier feature relacionado a Yanet:**

1. **Buscar en engram** el log vivo de compromisos: `mem_search("gestion_cobranza/yanet-commitments-log")`. Si la búsqueda devuelve resultados, obtener el contenido completo con `mem_get_observation(id)`. Ese log tiene TODO el histórico de qué se prometió, qué se cobró, qué se entregó, qué se revirtió, y qué queda pendiente.
2. **Si existe `Chat de WhatsApp con Yanet Landa.zip` en la raíz del proyecto**, descomprimirlo a temp y leer el `.txt` resultante — sobre todo las últimas 300 líneas. El user lo actualiza periódicamente exportando la conversación.
3. **El zip está en `.gitignore`** — es información privada de la cliente. No commitearlo nunca.

**Después de CUALQUIER interacción con Yanet** (promesa, cotización, entrega, revert, decisión, aclaración):

- Actualizar inmediatamente el log de compromisos en engram (topic_key estable: `gestion_cobranza/yanet-commitments-log`).
- Si se revierte algo que ya fue anunciado a Yanet, dejar registro EXPLÍCITO de la discrepancia entre "lo prometido" y "lo realmente entregado". Esa fue la falla del incidente credit_balance de 2026-03-30.

**Regla de oro**: si una feature está en el log como "anunciada y entregada a Yanet", asumir que ella la considera pagada y operativa. Cualquier discrepancia con la realidad técnica del código es responsabilidad nuestra de resolver — sin re-cobrar y sin admitir culpa en los mensajes externos (regla absoluta de comunicación).

## Documentación

- `docs/ARCHITECTURE.md` — diagrama de dominio, flujos, decisiones
- `docs/SPEC.md` — spec funcional completa con estado por feature
- `tasks/todo.md` — tareas activas priorizadas
- `tasks/lessons.md` — lecciones aprendidas
- `CLAUDE.local.md` — notas de sesión (no commiteado)
