# Skill: new-feature

Workflow para agregar una feature nueva.

1. **Mini-spec** — en el chat, escribir: qué se agrega, a qué archivos afecta, qué cambia en DB, qué puede romper. Esperar confirmación del usuario.
2. **Plan de archivos** — listar controllers, views, models, migrations, exports afectados.
3. **Implementación** — cambio mínimo, respetar convenciones existentes (Alpine, Blade, Tailwind).
4. **Verificación** — `php artisan test` si hay tests relevantes; probar flujo manual mentalmente.
5. **Commit** — atómico, en español, mensaje descriptivo sin prefijos convencionales.
6. **Mensaje cliente** — redactar resumen profesional para Yanet en español, tono ingeniero senior.

Reglas:
- No correr migraciones en prod automáticamente. Dar comandos al usuario.
- Si la feature toca dinero (installments/transactions), revisar `OwnerSequence`, `lockForUpdate`, tolerancia `0.001`.
- No romper `type='installment'` flow existente.
