# Management / Provisioning API — Design Spec

> Status: **approved for planning** · Date: 2026-05-23 · Epic 1 of `docs/BACKLOG.md`

## 1. Problem & context

The SOS Tech core site (`portal.sos-tech.ca`) is the sales/billing master. Today there is **no
programmatic way** for it to act on MenuDirect — customers are added by hand, and the ported admin
provisioning code (`Admin/RestaurantSitesController::convertToPaying` / `setupBilling`) is **dead on
this VM**: it references `App\Models\Service` (absent) and `users` columns `first_name` /
`last_name` / `force_password_change` (absent). So there is no working provisioning path at all.

This spec defines a thin, authenticated HTTP API that lets `portal.sos-tech.ca` apply **operational
state** to MenuDirect: create owner logins, provision restaurant sites, change plans, and set site
status. Billing stays entirely in the SOS portal.

## 2. Goals / non-goals

**Goals**
- Let SOS create an owner login (and get the owner an invite/set-password link).
- Let SOS provision a restaurant site, assign a plan, and link it to an owner.
- Let SOS change a site's plan and set its status (demo/active/suspended/archived).
- One convenience call to do "add a customer" (owner + site + plan) atomically.

**Non-goals (explicitly out)**
- Modelling billing/invoices/subscriptions on MenuDirect — SOS owns that.
- Read-back GET endpoints and webhooks (one-way push; command responses echo state instead).
- Full plan **feature-gating** enforcement across every feature (separate backlog item).
- Custom-domain provisioning (lives at HAProxy/Cloudflare).
- Stripe / payments.

## 3. Locked decisions

| Decision | Choice |
|---|---|
| Sync direction | **One-way push**, SOS is master; no write-back |
| Billing state | **Not modelled here**; SOS toggles `status` (active/suspended) |
| Owner credentials | **Invite / set-password link** — no password over the wire |
| Auth | **Static shared secret + IP allowlist** (single caller: `portal.sos-tech.ca`) |
| Build approach | **Thin command API over a new `SiteProvisioningService`** |
| New site default status | `active` (SOS calls post-sale); caller may override |

## 4. Architecture

```
routes/api.php
  └── /api/v1/manage/*  (group: VerifyManagementApiToken + throttle)
        ├── Api\Manage\OwnerController
        ├── Api\Manage\SiteController
        └── Api\Manage\CustomerController        # convenience composite
app/Services/SiteProvisioningService.php          # ALL operational writes (transactional)
app/Http/Middleware/VerifyManagementApiToken.php  # static secret + IP allowlist
```

Controllers stay thin: validate → call `SiteProvisioningService` → return a JSON resource.
The service is the single source of provisioning logic (later the admin UI can be repointed at it).

**No database migrations are required.** Everything maps to existing columns
(`restaurant_sites`: `client_id`, `restaurant_plan_id`, `plan`, `status`, `slug`, `archived_at`,
`ordering_enabled`; `users`: `name`, `email`, `password`). Idempotency uses Redis cache; the
invite uses the existing Laravel password-reset table; audit uses `audit_logs`.

## 5. Auth & security

- **Bearer token**: `Authorization: Bearer <MANAGEMENT_API_TOKEN>` compared with `hash_equals`
  (timing-safe). Token in `.env` (`MANAGEMENT_API_TOKEN`), separate from `MENUDIRECT_INTAKE_TOKEN`.
- **IP allowlist**: `MANAGEMENT_API_ALLOWED_IPS` (comma-separated; the SOS portal host). Checked
  against the real client IP (HAProxy `X-Forwarded-For` is already trusted via `TrustProxies`).
  Empty allowlist = deny all (fail closed).
- **Throttle**: `throttle:60,1` on the group.
- **Audit**: every successful command writes an `audit_logs` row (actor label `sos-portal`,
  action, target id, sanitized payload) via `AuditService`. (Implementation note: `AuditService`
  may need to accept a system/no-User actor.)
- Failures: `401` (bad/missing token), `403` (IP not allowed) — both logged as warnings.

## 6. Endpoints

All bodies are JSON. All responses use the error envelope in §8. `POST` creates accept an optional
`Idempotency-Key` header (§7).

### POST `/api/v1/manage/owners` — create owner login
Request:
```json
{ "email": "owner@example.com", "name": "Jane Doe", "send_welcome_email": false }
```
- Creates a `User` with an unguessable random password hash (no usable password).
- Generates a password-reset token and returns a `set_password_url` to the existing
  `portal.menudirect.ca` reset page. If `send_welcome_email` is true, MenuDirect emails the link.
- **Idempotent on email**: if the user already exists, returns `200` with `already_existed: true`
  and the owner (no new link unless `reissue_invite: true`).

Response `201`:
```json
{ "owner": { "id": 58, "email": "owner@example.com", "name": "Jane Doe", "is_admin": false },
  "set_password_url": "https://portal.menudirect.ca/reset-password/<token>?email=owner%40example.com",
  "set_password_expires_at": "2026-05-24T12:00:00Z",
  "already_existed": false }
```

### POST `/api/v1/manage/sites` — provision a site
Request:
```json
{ "business_name": "Burgers at Buster's", "slug": "burgers-at-busters",
  "template": "pizzeria", "plan_id": 3, "owner_id": 58, "status": "active" }
```
- `slug` optional → generated from `business_name`, uniqueness-checked. `owner_id` **or**
  `owner_email` (must resolve to an existing owner). `template` optional → default. `status`
  optional → `active`.
- Sets `client_id`, `restaurant_plan_id`, `plan` (via `mapPlanToType(plan.slug)`), `slug`,
  `status`, and syncs derived availability (`ordering_enabled` = plan `online_ordering`) per §9.
- Fires `RestaurantSiteObserver` automatically (cache-bust + IndexNow).
- `409` if slug is taken (unless a matching `Idempotency-Key` returns the prior result).

Response `201`: `{ "site": { id, slug, business_name, status, plan_id, plan, ordering_enabled, public_url, owner_id } }`

### PATCH `/api/v1/manage/sites/{id}/plan` — change plan
Request: `{ "plan_id": 4 }` → sets `restaurant_plan_id` + `plan` enum, re-syncs derived flags (§9),
never deletes data. Response `200`: `{ "site": { … } }`.

### PATCH `/api/v1/manage/sites/{id}/status` — set status
Request: `{ "status": "suspended" }` where status ∈ `demo | active | suspended | archived`.
- `archived` sets `archived_at = now()`; any non-archived status clears `archived_at` and sets the
  `status` enum. `suspended` → public site serves a holding page (§10).
Response `200`: `{ "site": { … } }`.

### POST `/api/v1/manage/customers` — "add a customer" (atomic convenience)
Request:
```json
{ "owner": { "email": "owner@example.com", "name": "Jane Doe" },
  "site":  { "business_name": "Burgers at Buster's", "slug": "burgers-at-busters", "template": "pizzeria" },
  "plan_id": 3, "status": "active", "send_welcome_email": true }
```
- Single DB transaction: create-or-get owner → create site → assign plan → link owner.
- Response `201`: `{ "owner": { …, "set_password_url": … }, "site": { … } }`.

## 7. Idempotency

`POST` creates accept an `Idempotency-Key` header. The service stores `key → response` in Redis
(24h TTL, namespaced by token). A repeat with the same key returns the stored response with
`200` and no new writes. Without the header, normal uniqueness rules apply (duplicate email →
existing owner; duplicate slug → `409`).

## 8. Error envelope

```json
{ "error": { "code": "validation_failed", "message": "The slug has already been taken.",
             "details": { "slug": ["taken"] } } }
```
Codes/status: `unauthorized` 401 · `ip_forbidden` 403 · `not_found` 404 · `validation_failed` 422
· `conflict` 409 · `server_error` 500.

## 9. Plan → operational sync rules

On provision and plan-change:
1. `restaurant_plan_id = plan.id`; `plan = mapPlanToType(plan.slug)`.
2. `ordering_enabled = plan.online_ordering` (availability only).
3. **No destructive changes** — menu items, reservations, photos are never deleted on downgrade;
   only availability toggles. If a plan removes online ordering, `ordering_enabled` goes false but
   the menu is retained for a future upgrade.
4. Comprehensive feature-gating (extending `canUse*` to ordering/reservations/etc. and enforcing
   limits like `menu_items_limit`) is **out of scope** here — flagged as a follow-on.

## 10. Status behaviour

- `suspended`: `SampleSiteController` (and `showByDomain`) must check `status` and serve a
  **503 holding page** ("temporarily unavailable") for the public site. It does not gate on status
  today — this check is part of this epic. Owner portal login is unaffected (minimal scope).
- `archived`: already handled by `archived_at` + the `notArchived` global scope (hidden from
  listings/sitemaps).
- `demo` / `active`: normal rendering.

## 11. Testing (TDD)

Laravel feature (HTTP) tests covering:
- Auth: valid token, missing/bad token (401), disallowed IP (403).
- `owners`: create + set_password_url issued; duplicate email → existing (idempotent).
- `sites`: provision happy path; slug auto-gen; duplicate slug → 409; owner resolution by id/email;
  `ordering_enabled` synced from plan.
- `sites/{id}/plan`: plan + enum updated, flags re-synced, no data loss.
- `sites/{id}/status`: each transition; suspended → public 503; archived sets `archived_at`.
- `customers`: atomic create; partial failure rolls back (no orphan owner/site).
- Idempotency: identical double-POST yields one resource.
- Validation envelope shape for bad input.

## 12. Configuration (`.env`)

```
MANAGEMENT_API_TOKEN=<long random secret>
MANAGEMENT_API_ALLOWED_IPS=<sos-portal host IP>
```
Surfaced via `config/services.php` (`services.management.token`, `services.management.allowed_ips`)
so it works under cached config.

## 13. Out of scope / follow-ons

- Read-back GET endpoints + webhooks to SOS (revisit if the sales dashboard needs live state).
- Full plan feature-gating + limit enforcement.
- Repointing the broken admin UI provisioning at `SiteProvisioningService` (and deleting the dead
  `Service`-coupled code) — recommended cleanup, separate change.
