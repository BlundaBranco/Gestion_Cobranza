# CLIENT_COMMUNICATIONS.md — Yanet (Real Estate Client)

## Client Profile
- **Name:** Yanet
- **Business:** Mexican real estate company (fraccionamiento/lotes)
- **Communication:** WhatsApp, casual Mexican Spanish
- **Tone:** Friendly but professional. She's non-technical — explain things in plain terms.
- **Relationship:** Active production client. SaaS subscription + change requests.

---

## Bug vs Feature Classification Guide

| Scenario | Classification | Billable? |
|---|---|---|
| Something that worked before is now broken | **BUG** | No (warranty) |
| Data displaying incorrectly due to our logic | **BUG** | No |
| A calculation is wrong | **BUG** | No |
| New column, field, or section she wants added | **FEATURE** | Yes |
| New report or export she wants | **FEATURE** | Yes |
| Changed her mind on existing behavior | **FEATURE** | Yes |
| Improving UX of existing flow | **FEATURE** | Yes (small) |
| Security or permission issue | **BUG** | No |

---

## Pricing Reference (USD)

| Scope | Price Range |
|---|---|
| Simple fix / label change | $0 (bug) — $15–25 (feature) |
| New form field + migration | $30–50 |
| New report or export | $50–100 |
| New module (CRUD) | $150–300 |
| Complex multi-model feature | $200–400 |

---

## Request Log

### [YYYY-MM-DD] — Request Title
- **Raw request:** "..."
- **Classification:** [BUG] / [FEATURE]
- **Estimated cost:** $X USD / Free
- **Status:** Pending / In Progress / Done
- **Files modified:** `app/...`
- **Notes:** —

---

### [2026-03-26] — Anticipo en PDF, tipografía, teléfono, agrupado por lote, ocultar totales
- **Raw request:** Yanet reportó: (1) excedente de pago no visible en recibo; (2) tipografía pequeña para adultos mayores, número junto al nombre (era el teléfono, no el ID); (3) cuotas de múltiples lotes mezcladas en pantalla de pago; (4) ocultar totales del reporte a empleados no-admin.
- **Classification:** (1) COMMITTED FIX — gratis | (2) COMMITTED FIX + FEATURE menor — gratis | (3) FEATURE — $35 USD | (4) FEATURE — $20 USD
- **Estimated cost:** $55 USD (ítems 3 y 4)
- **Status:** Done
- **Files modified:** `resources/views/transactions/pdf.blade.php`, `resources/views/transactions/create.blade.php`, `resources/views/reports/income.blade.php`
- **Notes:** El número junto al nombre era el teléfono del cliente, no el ID como le dijo Branco. Se quitó igual. Implementados todos los ítems incluyendo los de cobro — pendiente confirmación de Yanet sobre los $55 USD.

---

<!-- Add new entries above this line, newest first -->
