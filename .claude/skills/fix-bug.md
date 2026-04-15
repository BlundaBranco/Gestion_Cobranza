# Skill: fix-bug

Workflow para corregir un bug reportado.

1. **Reproducir** — identificar el flujo exacto (controller, vista, estado de DB).
2. **Diagnosticar** — leer archivos relacionados completos, no solo la función. Entender el flujo end-to-end antes de editar.
3. **Root cause, no síntoma** — si es una cascada, corregir el origen. No agregar try/catch para silenciar.
4. **Fix mínimo** — no refactorizar alrededor. Solo lo que el bug requiere.
5. **Verificar edge cases** — qué pasa con transacciones canceladas, cuotas con interés, enganches (#0), multi-moneda.
6. **Commit** — prefijo `Fix:` en español, descripción del problema real.

Nunca:
- Reintentar la misma solución si falla — buscar otro approach.
- Asumir que el bug es en la vista si el síntoma está en datos — ir al controller/model.
