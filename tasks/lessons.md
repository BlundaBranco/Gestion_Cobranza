# Lecciones Aprendidas

> Se actualiza automáticamente después de cada corrección del usuario.
> Formato: - [FECHA] **Categoría**: Lección atómica. Confianza: alta/media.

- [2026-03-31] **Deployment**: Nunca correr `route:cache` en producción — rompe rutas bajo FPM. Confianza: alta.
- [2026-03-31] **Deployment**: Nunca deployar código que referencia migraciones sin correr `php artisan migrate --force` primero en prod. Confianza: alta.
- [2026-04-10] **Financial logic**: En `TransactionController@destroy` usar `updateExistingPivot(amount_applied=0)`, nunca `detach()` — preserva auditoría y cadena owner. Confianza: alta.
- [2026-04-10] **Data integrity**: `owner_id` en transactions debe guardarse como snapshot al crear, no derivarse por JOIN a lote (el lote puede transferirse). Confianza: alta.
- [2026-04-15] **Scope**: `docs/SPEC.md` debe incluir lógica de negocio completa (folios, interés, SoftDeletes, enganche, multi-moneda), no solo features pendientes — sirve para prevenir regresiones. Confianza: alta.
- [2026-04-15] **Workflow**: Implementar todo el código sin esperar feedback intermedio — el testing/QA lo hace el cliente, no el dev. Confianza: alta.
- [2026-04-15] **Reports null safety**: transacciones sin installments (tipo `extra`) rompen `$transaction->installments->first()->paymentPlan->currency` — usar `?->` en todos los reports/vistas que infieran moneda. Confianza: alta.
- [2026-04-15] **Security**: Jamás guardar passwords SSH en `docs/`, archivos commiteados, ni memoria persistente. Usar SSH key auth. Confianza: alta.
- [2026-04-15] **Deploy commands**: `sudo git pull` es un antipatrón — deja archivos como root y rompe pulls futuros del usuario normal. `git pull` va sin sudo. Confianza: alta.
- [2026-04-15] **Git workflow**: Para separar un commit en dos después de hacerlo, usar `git reset --soft HEAD~1` + re-stage selectivo. Pushear solo el primero con `git push origin <sha>:master`. Confianza: alta.
- [2026-04-15] **File state after checkout**: Después de `git checkout HEAD -- <file>`, los Reads en contexto quedan stale. Siempre re-leer antes de Edit. Confianza: alta.
- [2026-04-15] **Autonomy scope**: El usuario quiere máxima autonomía en local (commits, pushes, docs), pero deploy SSH lo hace él mismo. Confianza: alta.
