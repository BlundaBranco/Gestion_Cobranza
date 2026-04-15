# Lecciones Aprendidas

> Se actualiza automáticamente después de cada corrección del usuario.
> Formato: - [FECHA] **Categoría**: Descripción atómica. Confianza: alta/media.

- [2026-03-31] **Deployment**: Nunca correr `route:cache` en producción — rompe rutas bajo FPM. Confianza: alta.
- [2026-03-31] **Deployment**: Nunca deployar código que referencia migraciones sin correr `php artisan migrate` primero en prod. Confianza: alta.
- [2026-04-10] **Financial logic**: En `TransactionController@destroy` usar `updateExistingPivot(amount_applied=0)`, nunca `detach()` — preserva auditoría y cadena owner. Confianza: alta.
- [2026-04-10] **Data integrity**: `owner_id` en transactions debe guardarse como snapshot al crear, no derivarse por JOIN a lote (el lote puede transferirse). Confianza: alta.
- [2026-04-15] **Scope**: Doc `SPEC.md` debe incluir lógica de negocio completa (folios, interés, SoftDeletes, enganche, multi-moneda), no solo features pendientes — sirve para prevenir regresiones. Confianza: alta.
- [2026-04-15] **Workflow**: Implementar todo el código sin esperar feedback intermedio — el testing y QA lo hace el cliente, no el dev. Confianza: alta.
- [2026-04-15] **Reports null safety**: transacciones sin installments (tipo `extra`) rompen `$transaction->installments->first()->paymentPlan->currency` — usar operador null-safe `?->` en todos los reports/vistas que infieren moneda desde installments. Confianza: alta.
