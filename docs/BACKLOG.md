# MenuDirect — Product Backlog

> Created 2026-05-23. This is the decomposition of the major in-flight subsystems into
> independent epics. **Each epic is its own project**: it goes through its own
> brainstorm → spec (`docs/superpowers/specs/`) → plan (`docs/superpowers/plans/`) → build
> cycle. This file is the roadmap and running checklist, not a design doc.

## Epics at a glance

| # | Epic | State | Size | Recommended order |
|---|------|-------|------|-------------------|
| 1 | Management / provisioning API (SOS portal → MenuDirect) | Greenfield | Medium | **1st** |
| 2 | Reservations: harden + table/floor management | Mostly built, needs audit | Medium ×2 | 2nd |
| 3 | Full in-person POS (terminal + cash + receipts) | Order-mgmt only today | Large (split) | 3rd, after payment-rail decision |
| 4 | External POS integrations (Square/Toast/Clover…) | Greenfield | Large | **Deferred / parked** |

A strategic note up front: **Epic 3 (be the restaurant's POS)** and **Epic 4 (integrate into the
restaurant's existing POS)** serve opposite customer segments. Decide the product stance before
investing heavily in both.

---

## Epic 1 — Management / Provisioning API
**Goal:** `portal.sos-tech.ca` (the sales/core site) can manage MenuDirect customers
programmatically — the operations a sale, upgrade, or cancellation triggers.

**Current state:** `api.php` only does inbound leads + Stripe webhook *relay* + public reads.
Sanctum is installed (`personal_access_tokens` table) but guards nothing beyond the stock
`/user` route. `RestaurantPlan` exists as a model with no API. → build from scratch.

**Scope (agreed):** create/manage owner logins, provision sites, set/change plan, set billing status.

**Decisions to make first**
- [ ] Auth model: dedicated Sanctum machine token w/ abilities vs a shared static secret (like the
      existing `MENUDIRECT_INTAKE_TOKEN`). Recommend Sanctum token + abilities + IP allowlist (portal host).
- [ ] How plans gate features today (is `RestaurantPlan` wired to `RestaurantSite` capability flags, or just metadata?).
- [ ] What "billing past-due" does on this side (grace period → holding page → suspend?).

**Work items**
- [ ] Versioned, idempotent contract: `POST /api/v1/manage/...`, idempotency keys on provisioning, consistent error envelope.
- [ ] `POST owners` — create login (invite link or set password), reset access.
- [ ] `POST sites` — provision (name, slug, template, plan, link owner); idempotent.
- [ ] `PATCH sites/{id}/plan` — upgrade/downgrade; enforce plan feature gates.
- [ ] `PATCH sites/{id}/status` — active / suspended / archived (suspend takes the public site to a holding page).
- [ ] `PATCH sites/{id}/billing` — active / past-due / comp + downstream behaviour.
- [ ] `GET sites` / `GET sites/{id}` — status read-back for the portal dashboard.
- [ ] Plan feature-gating enforcement (ordering / reservations / multi-location / delivery per plan).
- [ ] Audit every management action (wire the existing `AuditService`).
- [ ] Rate limiting + IP allowlist; optional state-change webhooks back to the SOS portal.

---

## Epic 2 — Reservations: harden + table/floor management
**Goal:** built-in reservations production-solid, plus front-of-house seating/floor management.

**Current state:** `ReservationService` (slots/dates/create/capacity), full status lifecycle
(pending→confirmed→seated→completed/cancelled/no_show), public API, owner management
(index/show/confirm/seat/complete), settings, 3 mailables + notification job. Solid skeleton.

### 2a — Harden the built-in system (own spec)
- [ ] Missing owner actions: **cancel**, **mark no-show**, **edit/modify**, **staff walk-in / manual booking**.
- [ ] Audit slot-capacity math (`getCoversAtTime`, overlapping reservations, max-covers-per-slot) + add tests.
- [ ] Timezone correctness — slots/availability in the restaurant's local tz.
- [ ] Double-booking / race prevention on `store` (transaction + locking).
- [ ] Status-transition guards (can't seat a cancelled reservation, etc.).
- [ ] Notification completeness: owner-alert on new, customer confirm/cancel, **pre-arrival reminder**; consider SMS.
- [ ] End-to-end verify the public widget (the cancel `fetch()` path was already repaired this month).

### 2b — Table / floor management (own spec, follow-on)
- [ ] `Table` model (number, capacity, section/area) + owner-portal CRUD.
- [ ] Assign reservation → table (the `seat` action sets a table); reconcile table capacity vs slot covers.
- [ ] Floor view showing table status (open / reserved / seated).
- [ ] Walk-in seating + optional waitlist.

---

## Epic 3 — Full in-person POS (terminal + cash + receipts)
**Goal:** a real point-of-sale for in-person service, built on the existing order/kitchen system.

**Current state:** order lifecycle (pending→confirmed→preparing→ready→completed/cancelled),
server tablet, live kitchen display, `OrderAuditLog`, notifications, shift summary + `close-shift`
+ `ShiftCloseout`. This is **order management only — no payments, tendering, or receipts anywhere.**

**Decision to make first**
- [ ] **Payment rail:** Stripe Terminal vs the existing `manager` (api.sos-tech.ca) payments API vs other.
      This blocks most of the epic and overlaps with the deferred "Stripe Connect" item in `CLAUDE.md`.
- [ ] Counter-service first vs full-service (tables/tabs) — sets the order-entry model.

**This is large — decompose into sub-specs:**
- [ ] **Order entry (POS)** — build/extend an order from the menu: modifiers, qty, notes, order type (dine-in/takeout), open tabs, hold/fire.
- [ ] **Tendering & payments** — tender types (cash/card/split), change calc, card terminal, tips/gratuity, refunds.
- [ ] **Receipts & hardware** — receipt model, ESC/POS printer, cash-drawer kick, email/SMS receipt, kitchen ticket routing.
- [ ] **Discounts / comps / voids** — line + order level, reason codes, manager approval, audited.
- [ ] **Tax** — per-item tax categories + exemptions (today only a flat `tax_rate`).
- [ ] **Cash management** — opening float, paid in/out, blind drawer count, over/short; extend `ShiftCloseout` to reconcile cash vs tenders vs sales.
- [ ] **Reporting** — X (mid-shift) + Z (end-of-day) reports, sales by item/category/daypart, payment-type + tax + tip breakdowns.
- [ ] **Offline resilience** — keep taking orders/cash if the internet drops; local queue + sync.
- [ ] **Permissions** — which staff roles can void/comp/refund/open drawer/run Z.

---

## Epic 4 — External POS integrations  *(DEFERRED — backlog only)*
**Goal:** push MenuDirect online orders into a restaurant's existing POS (Square/Toast/Clover/Lightspeed)
for restaurants that keep their current system.

**Parked notes (revisit after the Epic 3 stance is set):**
- Two approaches: **aggregator** (Deliverect/Chowly/Otter — one integration, per-location fee) vs
  **per-vendor connectors** (more build, no middleman fee).
- Per integration: menu sync (push/pull), order injection, order-status sync, OAuth per vendor.
- Conflicts with Epic 3 by segment — only worth it for restaurants that won't adopt our POS.

---

## Known cleanup / tech debt (carry-over from recent sessions)
- [ ] Verify the "Accept Pickup Orders" toggle fix end-to-end (uncheck → save → reload).
- [ ] Smoke-test **all** ported owner-portal write paths (hours, announcements, catering, staff) — same class of latent bug as the menu editor and edit page.
- [ ] Enforce CSP — flip `CSP_ENFORCE=true` after a browser pass confirms no violations.
- [ ] Bundle the remaining 12 CDN-loaded views locally (blocked on the per-restaurant brand-colour → CSS-variables refactor).
- [ ] Delete confirmed-dead orphan code (`layouts/client.blade.php`, `order/{plans,configure,checkout}`, `auth/register`, `samples/index`, `admin/restaurant/convert`).
- [ ] Work the `CLAUDE.md` "Deferred items" list (T+7 cutover cleanup target 2026-05-25, 2FA, backups, monitoring).

---

## Suggested sequencing
1. **Epic 1 (Management API)** — well-bounded, independent, removes current manual sales/ops pain. Best first.
2. **Epic 2a (reservation hardening)** — contained quality pass; 2b (floor) as a follow-on.
3. **Epic 3 (POS)** — biggest; gated on the payment-rail decision; build sub-spec by sub-spec.
4. **Epic 4** — revisit once the in-house-POS stance is decided.
