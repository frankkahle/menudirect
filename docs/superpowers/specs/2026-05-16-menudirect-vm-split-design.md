# MenuDirect VM Split-off — Design Spec

**Date:** 2026-05-16
**Status:** Approved (pending file review)
**Author:** Frank Kahle (decisions) + Claude (drafting)
**Topic:** Splitting MenuDirect (restaurant SaaS) out of the SOS Tech monorepo into its own dedicated VM at 192.168.23.65.

---

## 1. Summary

MenuDirect (restaurant site SaaS — public restaurant sites at `*.menudirect.ca`, owner portal, admin, ordering/reservation API, lead intake) is currently distributed across two Laravel applications in the SOS Tech monorepo: `/var/www/portal` (data, owner portal, admin, API) and `/var/www/sos-tech` (public restaurant site rendering, marketing apex). This design moves **all** MenuDirect-related code and data onto a new dedicated VM (`192.168.23.65`, running Laravel 13.9.0 on PHP 8.3.31, with its own MySQL 8.0 instance). Authentication for restaurant owners forks into a separate `users` table on the new VM, seeded from the subset of SOS Tech `clients` who own a restaurant. Billing for restaurant SaaS plans remains in the SOS Tech portal because Frank is the sole operator and there is no self-serve signup yet.

The motivation is **portability**: with the restaurant codebase as a self-contained unit on its own VM, MenuDirect can be relocated to another host (cloud, separate physical machine, sold off) without rewriting any cross-host wiring.

## 2. Goals

- New VM is self-contained: kill the SOS Tech portal-host and MenuDirect keeps serving (data is local, mail relays to Mailcow which is its own host already, Stripe / Cloudflare / VOIP.MS are external services).
- Suwanna's live restaurant site and the 11+ demo sites survive cutover without their owners noticing.
- Existing owner logins (Suwanna + Frank-as-admin + any demo accounts) continue working with the same email + password they have today.
- A single short downtime window (~5–10 minutes) at cutover, with a clear rollback path if anything fails.
- Post-cutover, `/var/www/portal` and `/var/www/sos-tech` contain zero restaurant code.

## 3. Non-goals

- Self-serve restaurant signup at `portal.menudirect.ca/register` — deferred.
- Native Stripe Subscriptions billing on the new VM — deferred; billing stays in SOS Tech portal.
- Restaurant SaaS plan dunning / payment-failure suspension automation — deferred.
- Demo site curation (deciding which demos to keep, archive, delete) — deferred; everything migrates and we sort later.
- Renaming `restaurant_sites.client_id` to `user_id` — cosmetic, deferred indefinitely.
- Splitting the new VM's app into multiple Laravel repos — KISS, defer until codebase actually warrants it.
- SSH hardening on the new VM (`PasswordAuthentication no`, deploy user with NOPASSWD sudo) — deferred.
- Automated backups and alerting on the new VM — deferred; basic `mysqldump` is fine for go-live.

## 4. Decisions made

These were the architectural forks; each was answered in the brainstorm.

| # | Question | Decision | Rationale |
|---|----------|----------|-----------|
| 1 | Migration scope: backend-only or full split? | **Full split** | Backend-only leaves the new VM tethered to sos-tech for public site rendering, defeating the portability goal. |
| 2 | DB migration: cross-host, snapshot, or replication? | **Cross-host during test, physical move at cutover** | Smallest data size, simplest cutover, single source of truth throughout testing. |
| 3 | Restaurant owner auth: separate identity, SSO from SOS Tech, or copy `clients` wholesale? | **Separate `users` table, copy password hashes from `clients`** (subset who own restaurants) | Truly portable; existing owners don't notice (bcrypt hashes work unchanged); SOS Tech clients who aren't restaurant owners don't get a MenuDirect login. |
| 4 | Marketing apex `menudirect.ca`: move to new VM, or keep on sos-tech? | **Move to new VM** | Single-purpose new VM, sos-tech retains zero restaurant code. |

Side decisions confirmed in conversation:

- **L13 on the new VM** (not L12). The restaurant code we're porting is on L12; the port handles deprecation cleanup along the way.
- **Stripe Connect:** keep using the existing SOS Tech Stripe account/platform for now. No restaurants are currently onboarded with Stripe Connect, so there's no migration pain. New restaurants will go through the same platform until you decide otherwise.
- **`archived_at` column on `restaurant_sites`:** added during the schema port so demo sites can be soft-archived later without losing data.

## 5. Target architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│  HAProxy (SSL termination, edge.sos-tech.ca)                            │
└───────────────┬─────────────────────────────────┬───────────────────────┘
                │                                 │
                │ portal.sos-tech.ca              │ menudirect.ca
                │ sosdesk.ca                      │ portal.menudirect.ca
                │ *.sos-tech.ca                   │ *.menudirect.ca
                │                                 │ (custom restaurant domains)
                ▼                                 ▼
   ┌────────────────────────────┐   ┌────────────────────────────┐
   │  Portal host (current)     │   │  MenuDirect VM             │
   │                            │   │  192.168.23.65             │
   │  /var/www/portal           │   │                            │
   │  /var/www/sos-tech         │   │  /var/www/app              │
   │   - SOS Tech client portal │   │   - L13 / PHP 8.3.31       │
   │   - SOSDesk integration    │   │   - all MenuDirect code    │
   │   - sos-tech.ca marketing  │   │   - owner portal           │
   │                            │   │   - admin (MD-only)        │
   │  MySQL: sos_portal         │   │   - public site rendering  │
   │                            │   │   - marketing apex landing │
   │  (menudirect DB dropped    │   │   - lead intake API + form │
   │   T+7 days post-cutover)   │   │                            │
   │                            │   │  MySQL: menudirect (local) │
   │                            │   │  Redis: cache/queue/session│
   │                            │   │  Supervisor: queue worker  │
   └────────────────────────────┘   └────────────────────────────┘
                                                  │
                                                  ▼
                                    ┌─────────────────────────────┐
                                    │  Shared LAN services        │
                                    │  - Mailcow (192.168.23.25)  │
                                    │     SMTP direct, no Postfix │
                                    │  - Cloudflare (DNS)         │
                                    │  - Stripe (same platform)   │
                                    │  - VOIP.MS (SMS)            │
                                    └─────────────────────────────┘
```

**Key properties:**
- Zero cross-host calls from the new VM to portal-host after cutover.
- HAProxy is the single point of routing; DNS doesn't change at cutover — only HAProxy backend rules.
- Outbound mail: Laravel → SMTP → `mail.sos-tech.ca:25` (Mailcow). STARTTLS works because the cert CN matches the hostname (resolved to 192.168.23.25 via `/etc/hosts` or LAN DNS on the new VM).

## 6. Scope: what moves, stays, deleted

### Moves to new VM (from `/var/www/portal`)

**Models (~16):** `RestaurantSite`, `RestaurantLead`, `RestaurantStaff`, `RestaurantPlan`, `RestaurantCustomDomain`, `Announcement`, `LeadActivity`, `LeadEmailTrack`, `MenuItem`, `MenuCategory`, `FoodOrder`, `FoodOrderItem`, `Order`, `OrderNotification`, `OrderAuditLog`, `Reservation`, `DemoSession`.

**Controllers (~28):**
- `Client/Restaurant*` (8) — owner portal
- `Admin/Restaurant*` + `Admin/OrdersController` (5) — MenuDirect admin
- `Staff/*` (3) — kitchen/tablet auth + dashboard + orders
- `Api/Restaurant*`, `Api/FoodOrderApiController`, `Api/StaffOrdersApiController`, `Api/Reservation*`, `Api/Catering*`, `Api/Demo*`, `Api/MenudirectLeadController`, `Api/DomainCheckController` (~12)

**Services:** `ReservationService`, `DeliveryZoneService`, Stripe Connect portion of `StripeWebhookRelayController`. (`Square/SquarePaymentService` exists in portal but no current restaurant uses Square — port only if/when verified in use; otherwise skip and clean up the references during the move.)

**Jobs:** `SendOrderNotificationsJob`, `SendReservationNotificationsJob`, `SendCateringInquiryNotificationsJob`.

**Mailables (7):** `NewFoodOrder`, `OrderConfirmation`, `OrderStatusUpdate`, `NewReservation`, `ReservationConfirmation`, `ReservationStatusUpdate`, `RestaurantWelcome`.

**Owner portal views:** `resources/views/client/restaurant/*` (~15 blade files).
**Admin views:** `resources/views/admin/restaurant/*`.

### Moves to new VM (from `/var/www/sos-tech`)

- `SampleSiteController` — public restaurant site rendering.
- `MenuDirectController` — lead form handler, 5-layer defense stack.
- `TurnstileVerifier` service (copied; the SOS Tech contact form keeps its own copy).
- `resources/views/menudirect/landing.blade.php` — marketing apex.
- `resources/views/samples/*` — 19 template directories (bistro, coastal, urban, noir, etc., plus partials including `gallery-section.blade.php`).
- `public/images/templates/*` — 19 subdirs of template assets.
- `public/images/template-previews/*`.
- `public/images/menudirect/*`.

### Moves data

- Entire `menudirect` MySQL database — all tables.
- Subset of `sos_portal.clients` (those owning ≥1 `restaurant_site`, plus Frank as admin) → new `users` table on the MenuDirect VM, with bcrypt password hashes intact and 2FA settings preserved.

### Stays on portal-host (unchanged)

- `clients` table itself (the source — only a subset is *copied* to the new VM's `users`; the SOS Tech clients table is untouched).
- All SOS Tech client management (invoices, services, billing, domains, hosting subscriptions, SOSDesk).
- Marketing site at `sos-tech.ca`.
- Cloudflare DNS sync, OpenProvider integration, all non-restaurant infrastructure.

### Deleted from `/var/www/portal` (post-cutover, T+7)

- All restaurant controllers, models, jobs, mailables, services listed above.
- Restaurant-related routes from `routes/web.php` and `routes/api.php`.
- The `menudirect` connection block in `config/database.php`.
- The `menudirect` MySQL database itself, after a final backup.

### Deleted from `/var/www/sos-tech` (post-cutover, T+7)

- `MenuDirectController`.
- `SampleSiteController`.
- Marketing apex landing blade and all sample templates.
- All restaurant-template public image assets.
- Wildcard / fallback routes for restaurant domains.
- `TurnstileVerifier` is **not** deleted from sos-tech — the SOS Tech contact form still uses it. The new VM gets its own copy. ~60 lines of duplication, no shared package needed.

### One small wrinkle handled

The portal's `POST /api/menudirect/leads` endpoint moves to the new VM immediately (Section 2A in the brainstorm). sos-tech's `MenuDirectController` (still alive during transition) POSTs to the new VM. This means one less thing to flip at cutover.

## 7. Database migration mechanics

### Two databases, two strategies

| | Restaurant data (`menudirect` DB) | Owner auth (`users` table) |
|---|---|---|
| During test phase | Cross-host: new VM connects to portal-host MySQL across LAN | Local on new VM from day 1, seeded from `clients` |
| At cutover | Dumped from portal-host, imported to new VM's local MySQL | Re-seeded for any drift |
| Post-cutover | Lives locally on new VM; portal-host copy dropped at T+7 | Authoritative on new VM |

### Phase 1 — test phase setup

The new VM uses **two named Eloquent connections**:

| Connection name | Host | Database | What it holds |
|-----------------|------|----------|---------------|
| `mysql` (default) | local 127.0.0.1 | `menudirect` (initially holds only `users`) | Owner login records |
| `menudirect` (second) | env-driven — flips at cutover | `menudirect` | All restaurant data |

(Yes, the database name is `menudirect` on both connections. During test phase they live on different hosts. At cutover they collapse onto the same local host and the data merges into one local `menudirect` database.)

**Restaurant data (cross-host during test):**

```sql
-- On portal-host MySQL:
CREATE USER 'menudirect_app'@'192.168.23.65' IDENTIFIED BY '<generated-password>';
GRANT ALL ON menudirect.* TO 'menudirect_app'@'192.168.23.65';
```

Confirm `bind-address` on portal-host MySQL allows LAN connections (`0.0.0.0` or `192.168.23.0/24` style).

On new VM `.env` — second connection block (test phase values):
```
# Default connection — local MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=menudirect
DB_USERNAME=menudirect
DB_PASSWORD=<local-password>

# Second connection — restaurant data
MENUDIRECT_DB_HOST=<portal-host-LAN-ip>
MENUDIRECT_DB_DATABASE=menudirect
MENUDIRECT_DB_USERNAME=menudirect_app
MENUDIRECT_DB_PASSWORD=<generated>
```

`config/database.php` reads `MENUDIRECT_DB_*` for the second connection.

**Users (local from day 1):**

Migration on new VM adds the `users` table to the local `menudirect` database (see Section 8 for schema).

### Phase 2 — application config split

`config/database.php` defines both connections (env-driven).

Restaurant Eloquent models declare `protected $connection = 'menudirect';` (via a `RestaurantModel` base class — single edit point, used by all 16 models). The User model uses the default connection (no override needed).

At cutover, **nothing in the model code changes**. We flip `MENUDIRECT_DB_HOST` in `.env` from portal-host's IP to `127.0.0.1`. Models that read from the `menudirect` connection silently start reading from local. The `users` table on the default connection is unaffected.

Post-cutover refactor (optional, T+30 or whenever): collapse the two connections into one since they now point at the same physical DB / same database. Cosmetic; defer.

### Phase 3 — cutover (the actual DB move)

See Section 9 (Cutover Runbook) for the full step-by-step. Summary:

1. Quiesce restaurant writes on portal side (route freeze branch).
2. Drain queues on both hosts.
3. `mysqldump --single-transaction --routines --triggers menudirect | gzip > /tmp/menudirect-cutover.sql.gz` on portal-host.
4. `scp` to new VM.
5. Import on new VM into local MySQL.
6. Run `menudirect:seed-users` once more.
7. Flip new VM `.env`: `DB_HOST=127.0.0.1`, `DB_DATABASE=menudirect`, `DB_USERNAME=menudirect`.
8. `php artisan optimize` + restart php-fpm + restart queue worker.
9. Smoke test against new VM (Host-header trick).
10. HAProxy flip.
11. External smoke test (real DNS).
12. Monitor logs ~10 min.

### Phase 4 — cleanup (T+7)

- Revoke `menudirect_app@192.168.23.65` grant on portal-host.
- Drop `menudirect` database on portal-host (after a final backup elsewhere).
- Remove the `menudirect` connection from portal's `config/database.php`.
- Delete restaurant code from both `/var/www/portal` and `/var/www/sos-tech` (separate commits per repo, clear messages).

## 8. Auth migration

### Schema for the new `users` table

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY,         -- copied verbatim from clients.id
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,         -- bcrypt hash, copied unchanged
    is_admin BOOLEAN DEFAULT FALSE,         -- carried from clients.is_admin
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (email)
);
```

**Not copied:** phone, billing address, payment terms, discount percent, admin notes, force_password_change, welcome_email_sent_at, alternate_email, fax, `never_suspend`. All of those are SOS Tech client concerns, not relevant to a restaurant owner login.

### Why preserve `clients.id` as `users.id`

`restaurant_sites.client_id` already points at `clients.id` on the portal side. By preserving the ID value when copying to the new VM, **`restaurant_sites.client_id` keeps the same value** and silently points at the local `users.id` after the move. Zero FK column rewrites required.

### `archived_at` on `restaurant_sites`

Schema change made during the port:

```sql
ALTER TABLE restaurant_sites ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
ALTER TABLE restaurant_sites ADD INDEX (archived_at);
```

Default queries SHOULD filter `WHERE archived_at IS NULL` (model global scope). Frank can later run `UPDATE restaurant_sites SET archived_at = NOW() WHERE slug IN (...)` to soft-archive demo sites without losing the data.

### The seeding command

`php artisan menudirect:seed-users` (idempotent — safe to re-run).

Spec:

```
1. Open two database connections:
   - `mysql` (local, target) — for users table writes
   - `sostech_clients` (read-only) — for clients table reads (portal-host)

2. Query: 
   SELECT DISTINCT c.* 
   FROM sostech_clients.clients c
   INNER JOIN menudirect.restaurant_sites s ON s.client_id = c.id
   UNION
   SELECT * FROM sostech_clients.clients WHERE is_admin = 1;
   (Restaurant-owning clients + all admins — so Frank gets admin access on new VM even
   if he doesn't currently own a restaurant_site.)

3. For each result row, UPSERT into `users` (matching on id):
   - id, name, email, email_verified_at, password
   - is_admin, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at
   - created_at, updated_at (copied verbatim)
   - remember_token: NOT copied (force re-login, harmless)

4. Log summary: created N, updated M, unchanged K
   Audit each change.

5. Exit code 0 if success, non-zero on any error.
```

Run cadence:
- At start of test phase (initial seed).
- Optionally during test phase if new restaurants are onboarded on portal side.
- T-1 day during cutover prep.
- T-0 immediately before HAProxy flip (Step 6 in runbook).

### 2FA preserved

Hashed secrets and recovery codes copy unchanged. Owners using TOTP on portal continue with the same authenticator app — Laravel uses the same `pragmarx/google2fa` library on both sides.

### Edge cases handled

| Case | Behavior |
|------|----------|
| Frank as admin + owner of test restaurants | One `users` row, `is_admin=true`, plus `restaurant_sites.client_id = his_id`. Works. |
| SOS Tech client who owns zero restaurants | Never copied. Uses sos-tech portal as today. No MenuDirect login created. |
| New SOS Tech client added during test phase, owns a restaurant | Caught by re-running `seed-users` at T–1 day or T–0. |
| New restaurant owner signs up *after* cutover | They register fresh at MenuDirect (when self-serve signup is built). New `users.id` will diverge from any SOS Tech `clients.id`. Two products, two accounts. Acceptable. |
| Suwanna's existing login | Her client row is copied; her password hash carries over; she logs in next day with the same credentials. |

### Post-cutover login UX

- Login URL: `https://portal.menudirect.ca/login`.
- Forgot password: handled entirely on the new VM. Reset email sent via Mailcow. Reset link uses `APP_URL=https://portal.menudirect.ca`. No cross-host calls.

## 9. Cutover runbook

### T–1 day (prep, no traffic change)

- [ ] Final code freeze: no new commits to `/var/www/portal` or `/var/www/sos-tech` touching restaurant files.
- [ ] Run `menudirect:seed-users` on new VM — catches any newly-restaurant-owning client.
- [ ] Take a full portal-host MySQL backup (not just menudirect).
- [ ] Confirm HAProxy config is ready but not activated.
- [ ] Prepare branch on portal-host: `cutover/disable-restaurant-routes` (comments out / removes all restaurant routes from `routes/web.php` and `routes/api.php`).
- [ ] Optional: heads-up to Suwanna ("MenuDirect moving servers tomorrow, brief blip expected, log out/in if anything looks off").

### T–0 (go-live, ~5–10 min total downtime)

**Step 1 — Quiesce restaurant writes on portal side**

```bash
# on portal-host
cd /var/www/portal
git checkout cutover/disable-restaurant-routes
php artisan optimize
```

**Step 2 — Drain queues**

```bash
# portal-host (briefly)
php artisan queue:work --stop-when-empty
# new VM
ssh menudirect 'systemctl stop menudirect-queue && cd /var/www/app && sudo -u www-data php artisan queue:work --stop-when-empty'
```

**Step 3 — Final dump on portal-host**

```bash
mysqldump --single-transaction --routines --triggers --skip-lock-tables \
  menudirect | gzip > /tmp/menudirect-cutover-$(date +%Y%m%d-%H%M).sql.gz
ls -lh /tmp/menudirect-cutover-*.sql.gz   # expect <50 MB
```

**Step 4 — Transport + import**

```bash
scp /tmp/menudirect-cutover-*.sql.gz menudirect:/tmp/
ssh menudirect 'cd /tmp && gunzip -c menudirect-cutover-*.sql.gz | mysql menudirect && \
  mysql menudirect -e "SELECT COUNT(*) AS sites FROM restaurant_sites; SELECT COUNT(*) AS leads FROM restaurant_leads;"'
```

Sanity-check counts match the portal-host pre-dump values.

**Step 5 — Flip new VM's `menudirect` connection to local**

```bash
ssh menudirect 'cd /var/www/app && \
  sed -i "s|^MENUDIRECT_DB_HOST=.*|MENUDIRECT_DB_HOST=127.0.0.1|" .env && \
  sed -i "s|^MENUDIRECT_DB_USERNAME=.*|MENUDIRECT_DB_USERNAME=menudirect|" .env && \
  sed -i "s|^MENUDIRECT_DB_PASSWORD=.*|MENUDIRECT_DB_PASSWORD=$(cat /root/.menudirect_db_pass)|" .env && \
  sudo -u www-data php artisan optimize && \
  systemctl restart php8.3-fpm menudirect-queue'
```

(The default `mysql` connection — pointing at local `menudirect` for `users` — is unchanged. Only the second `menudirect` connection's host flips from portal-IP to localhost.)

**Step 6 — Last `seed-users` pass**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan menudirect:seed-users'
```

**Step 7 — Smoke tests against new VM (no public traffic yet)**

```bash
curl -sS -o /dev/null -w "owner portal: %{http_code}\n"        -H "Host: portal.menudirect.ca"   http://192.168.23.65/login
curl -sS -o /dev/null -w "marketing apex: %{http_code}\n"      -H "Host: menudirect.ca"          http://192.168.23.65/
curl -sS -o /dev/null -w "test demo subdomain: %{http_code}\n" -H "Host: suwanna.menudirect.ca" http://192.168.23.65/
```

All three should be `200`. If any is not, **stop and diagnose** before flipping HAProxy.

**Step 8 — Flip HAProxy**

Frank changes HAProxy backend rules:
- `menudirect.ca` → 192.168.23.65:80
- `portal.menudirect.ca` → 192.168.23.65:80
- `*.menudirect.ca` → 192.168.23.65:80
- Custom restaurant domains (CNAMEs already at edge) → 192.168.23.65:80

Reload HAProxy. DNS is unchanged; only routing flips.

**Step 9 — External smoke test**

From off-LAN (laptop on cell network):

- `https://menudirect.ca/` → marketing apex renders.
- `https://portal.menudirect.ca/login` → owner login renders.
- `https://<any-demo>.menudirect.ca/` → restaurant site renders.
- Log in as Suwanna → place a test order → verify it lands in her dashboard.

**Step 10 — Watch logs ~10 min**

```bash
ssh menudirect 'tail -f /var/www/app/storage/logs/laravel.log'
# in another shell:
ssh menudirect 'journalctl -u menudirect-queue -f'
```

Watch for 500s, queue failures, DB errors. Quiet = cutover complete.

### T+7 days — final cleanup

```bash
# On portal-host:
mysql -e "DROP USER 'menudirect_app'@'192.168.23.65';"
mysqldump menudirect | gzip > /backups/menudirect-final-$(date +%Y%m%d).sql.gz
mysql -e "DROP DATABASE menudirect;"
# Remove `menudirect` connection from portal's config/database.php
# Delete restaurant code from portal repo (separate commit, clear message)
# Delete restaurant code from sos-tech repo (separate commit, clear message)
```

## 10. Rollback

### Pre-HAProxy-flip failure (Steps 1–7 in cutover runbook)

New VM is broken; portal is still serving (just with restaurant routes disabled).

```bash
# Restore portal routes
cd /var/www/portal
git checkout main
php artisan optimize
```

Restaurant traffic resumes on portal as before. Diagnose new VM at leisure. No data loss.

### Post-HAProxy-flip failure (Step 9)

```
1. Revert HAProxy to old routing rules → traffic back to portal-host.
2. Portal-host still has the menudirect DB intact (we don't drop it until T+7).
3. Restore portal routes: git checkout main + php artisan optimize on portal-host.
4. Minor data loss: any orders/leads/reservations that landed on the new VM
   between the HAProxy flip and the revert (<10 minutes typically).
   We can manually migrate those rows back to portal-host's menudirect
   if business-critical, or accept loss for a true emergency rollback.
```

### Hardest rollback (DB corruption discovered post-cutover)

```
1. Restore portal-host menudirect DB from the cutover-day dump (we kept it).
2. Revert HAProxy.
3. Restore portal routes.
4. Lose all writes made on new VM since cutover. Acceptable only for
   catastrophic data corruption, not minor bugs.
```

## 11. Definition of done

Cutover is considered successful when **all** of the following are true:

- [ ] `https://menudirect.ca/` renders the marketing apex (HTTP 200, page loads).
- [ ] `https://portal.menudirect.ca/login` renders, Frank can log in as admin, Suwanna can log in as owner.
- [ ] `https://suwanna.menudirect.ca/` (and any other live restaurant subdomain) renders her current menu and accepts test orders.
- [ ] A test order placed via the public site appears in Suwanna's owner dashboard.
- [ ] Lead form at `menudirect.ca/lead` accepts a submission; new row appears in `restaurant_leads` on the new VM; Frank receives the notification email.
- [ ] Queue worker (`systemctl status menudirect-queue`) is active and processing.
- [ ] No 500 errors in `storage/logs/laravel.log` for 10 minutes after the HAProxy flip.
- [ ] Portal-host `sos_portal` database is intact; SOS Tech client portal at `portal.sos-tech.ca` continues working unchanged.

## 12. Open items at cutover

None at design-doc time. All foundational decisions are made. Open items will appear in the implementation plan (separate document, written next via the `writing-plans` skill).

## 13. References

- Brainstorming session: this conversation, 2026-05-15 to 2026-05-16.
- Restaurant code topology survey: dispatched 2026-05-16 (results captured in this spec, Section 6).
- Pre-Europe discussion of the split: late April 2026 (conversation history, prior to Frank's travel).
- Account suspension exemption work (`never_suspend`): 2026-05-14 (`/var/www/portal/docs/CHANGELOG.md`).
- MenuDirect lead intake hardening (5-layer defense + Portal API): 2026-05-15 (same CHANGELOG).
