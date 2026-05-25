# Wiring portal.sos-tech.ca → MenuDirect Management API

> **Audience:** the Claude Code agent on the **portal server (192.168.23.50)**.
> **Goal:** make the SOS portal call MenuDirect's management API so a sale/upgrade/cancellation
> in the portal provisions and updates the matching MenuDirect site automatically.
>
> The receiving end (this API) is **built, deployed, and live** on the MenuDirect VM. Your job is
> the **calling** side in the portal codebase. This doc is the contract.

---

## 1. Connection & auth

- **Base URL:** `http://192.168.23.65/api/v1/manage` — the MenuDirect VM over the LAN (same host
  the existing lead-intake integration already uses, `PORTAL_API_URL=http://192.168.23.65`). Do
  **not** route this through Cloudflare/HAProxy — call the VM directly so the source IP is the
  portal's `192.168.23.50`.
- **Why the LAN:** the API is IP-allowlisted to `192.168.23.50`. A direct LAN call presents that
  IP; going through the edge would present a different one and be rejected (`403`).
- **Auth header:** `Authorization: Bearer <MANAGEMENT_API_TOKEN>` on every request.
- **Store the token** in the portal's `.env` (e.g. `MENUDIRECT_MANAGEMENT_TOKEN=`). **Frank will
  give you the value** (it lives in MenuDirect's `.env` as `MANAGEMENT_API_TOKEN`). Never hardcode
  or log it.
- **Content-Type:** `application/json`. Send `Accept: application/json`.
- **Idempotency:** on every `POST`, send an `Idempotency-Key: <stable-uuid>` header (e.g. derived
  from the portal's order/service id). A retried call with the same key returns the original result
  instead of creating duplicates — safe to retry on timeout.

---

## 2. The operations you'll call

### Add a customer (the main one) — `POST /customers`
Use when a sale closes / a new paying restaurant is onboarded. Creates the owner login **and** the
site **and** assigns the plan, atomically.

```http
POST http://192.168.23.65/api/v1/manage/customers
Authorization: Bearer <token>
Idempotency-Key: portal-service-<id>
Content-Type: application/json

{
  "owner": { "email": "owner@example.com", "name": "Jane Doe" },
  "site":  { "business_name": "Buster's Burgers", "slug": "busters-burgers", "template": "pizzeria" },
  "plan_id": 3,
  "status": "active"
}
```
`201` response:
```json
{
  "owner": { "id": 58, "email": "owner@example.com", "name": "Jane Doe", "is_admin": false },
  "set_password_url": "https://portal.menudirect.ca/reset-password/<token>?email=owner%40example.com",
  "site": { "id": 42, "slug": "busters-burgers", "status": "active", "plan": "premium",
            "plan_id": 3, "ordering_enabled": true, "owner_id": 58,
            "public_url": "https://busters-burgers.menudirect.ca" }
}
```
- **Persist `owner.id` and `site.id`** against the portal's customer/service record — you need
  `site.id` for later plan/status calls.
- **`set_password_url`** is a one-time link for the owner to set their MenuDirect password. Email it
  to the customer from the portal (recommended), or pass `"send_welcome_email": true` to have
  MenuDirect send it.
- `slug` is optional (auto-generated from `business_name`); `status` defaults to `active`.

### Change plan — `PATCH /sites/{id}/plan`
```json
{ "plan_id": 4 }
```
Returns the updated `site`. Re-syncs derived flags (e.g. `ordering_enabled`); never deletes data.

### Set status — `PATCH /sites/{id}/status`
```json
{ "status": "suspended" }   // one of: demo | active | suspended | archived
```
- `suspended` → the public site serves a 503 holding page. Use for **past-due / cancellation**.
- `active` → restore. `archived` → soft-archive (hidden). `demo` → demo mode.

### Create an owner only — `POST /owners`
`{ "email", "name" }` → returns owner + `set_password_url`. Idempotent on email (`200` +
`"already_existed": true` if the email exists). Use if you need a login without a site yet.

### Provision a site for an existing owner — `POST /sites`
`{ "business_name", "slug?", "template?", "plan_id", "owner_id" | "owner_email", "status?" }` →
returns `site`. Use for a **second location** under an existing owner.

---

## 3. plan_id mapping
`plan_id` is MenuDirect's `restaurant_plans.id`. The portal must map its own plan/SKU to the
MenuDirect plan id. Current MenuDirect plan slugs: `basic`, `sitefresh`, `sitefresh-pro`,
`menudirect-max`. Ask Frank for the id↔slug mapping, or store the `plan_id` returned on the first
provision. (A `GET /plans` lookup endpoint is a planned follow-on but not built yet.)

---

## 4. Error handling
All errors share one envelope:
```json
{ "error": { "code": "validation_failed", "message": "…", "details": { "field": ["…"] } } }
```
| Status | code | Meaning / action |
|---|---|---|
| 401 | `unauthorized` | Bad/missing token — check the header. |
| 403 | `ip_forbidden` | Not calling from 192.168.23.50 / went through the edge. |
| 404 | `not_found` | Unknown `site` id. |
| 409 | `conflict` | Slug already taken — pick another or treat as already-provisioned. |
| 422 | `validation_failed` | Fix the fields in `details`. |

Retry `5xx`/timeouts with the **same `Idempotency-Key`** (safe). Do not retry `4xx` without fixing input.

---

## 5. Suggested portal wiring (integration points)
- **New paying customer / sale closes** → `POST /customers`; store `site.id`; email `set_password_url`.
- **Plan upgrade/downgrade** → `PATCH /sites/{id}/plan`.
- **Payment past-due or cancellation** → `PATCH /sites/{id}/status {"status":"suspended"}`;
  **reactivation** → `{"status":"active"}`; **closure** → `{"status":"archived"}`.
- Wrap calls in a small client class (base URL + token from env + Idempotency-Key + JSON), mirroring
  the existing lead-intake client. Log failures (without the token) and surface them to staff.

---

## 6. What this API does NOT do (so don't wait on it)
- **No read-back / GET endpoints** and **no webhooks** — the portal is the source of truth; it
  pushes state to MenuDirect, one-way. Track everything portal-side.
- **No billing** — invoices/Stripe stay entirely in the portal. MenuDirect only flips
  `status`/`plan` when you tell it to.
- Plan **feature-gating enforcement** is minimal on MenuDirect today; setting the plan is recorded
  and toggles ordering availability, but don't assume hard per-feature limits yet.

---

## 7. Quick connectivity test (from the portal server)
```bash
curl -s -X POST http://192.168.23.65/api/v1/manage/ping \
  -H "Authorization: Bearer $MENUDIRECT_MANAGEMENT_TOKEN" -H "Accept: application/json"
# expect: {"ok":true}   (401 = token; 403 = not calling from 192.168.23.50)
```
