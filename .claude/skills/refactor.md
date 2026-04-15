# Skill: refactor

Workflow para refactorizar sin romper funcionalidad.

1. **Justificar** — un refactor sin razón es over-engineering. Debe mejorar legibilidad, performance o mantenibilidad medible.
2. **Tests primero** — si no hay tests, considerar si el refactor vale el riesgo. Escribir test mínimo que cubra el comportamiento actual.
3. **Respetar convenciones del proyecto** — no introducir patrones nuevos (Service, Repository, Action) sin consultar.
4. **Commit separado del feature/fix** — nunca mezclar.
5. **Diff mínimo** — respetar preferencia del usuario de strict git diffs.

No refactorizar:
- Código financiero (`TransactionController@store`, `OwnerSequence`, `IncomeExport`) sin discusión previa.
- Migraciones ya aplicadas.
