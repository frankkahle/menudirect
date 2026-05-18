# MenuDirect VM — Operational Handoff

> **For the local Claude Code agent on this VM:** This is the canonical front-door doc. Read it once at the start of any session before doing work. Deeper details are in `docs/superpowers/specs/` and `docs/superpowers/plans/`.

---

## What this VM is

This is **MenuDirect** — a restaurant SaaS that lets independent Canadian restaurants run a modern website with built-in online ordering, without the 25-30% commissions of Skip / DoorDash / Uber Eats.

It was **migrated off the SOS Tech monorepo** on 2026-05-18 and now lives on its own dedicated VM at `192.168.23.65` (LAN address; public via HAProxy at `edge.sos-tech.ca`). All the heavy lifting of that cutover is finished; this VM is the new home.

**Operator:** Frank Kahle (SOS Technical Services, Hampton NB)
**Frank's role here:** admin + sole MenuDirect operator. Restaurant owners are SaaS customers.

### What lives where (post-cutover landscape)

| Host | Role |
|---|---|
| **menudirect.ca** | Marketing apex + lead form + sitemaps + llms.txt — this VM |
| **portal.menudirect.ca** | Owner portal (restaurant owners log in to manage their site) — this VM |
| **\{slug\}.menudirect.ca** | Public restaurant sites — this VM |
| **Custom restaurant domains** (`burgersatbusters.ca`, `snowssoftserve.ca`, `snowsicecream.ca`) | Same restaurant sites, custom domain — this VM (via fallback route) |
| **portal.sos-tech.ca** | SOS Tech client portal for hosting/email/domain customers — **separate**, still on the old portal-host. **Do not touch.** |
| **sos-tech.ca** | SOS Tech marketing site — **separate**, still on the old portal-host. **Do not touch.** |
| **HAProxy** | At a separate host. Routes the above hostnames to the right backend. Cloudflare in front for SSL/DDoS. |

---

## Stack

| | |
|---|---|
| OS | Ubuntu 24.04 LTS |
| PHP | 8.3.31, FPM |
| Laravel | 13.9.0 |
| MySQL | 8.0.45 (dedicated local instance, `menudirect` DB) |
| Redis | 7.0.15 (cache, sessions, queue) |
| Nginx | 1.24.0 |
| Queue worker | systemd: `menudirect-queue` (auto-restart) |
| Scheduler | `/etc/cron.d/menudirect` runs `schedule:run` every minute |
| Mail outbound | SMTP to `mail.sos-tech.ca:25` (Mailcow, 192.168.23.25) — no Postfix layer |
| Telephony / SMS | VOIP.MS API (existing SOS Tech account) |
| HAProxy upstream | Yes — this app sits behind HAProxy which terminates SSL. `bootstrap/app.php` trusts `X-Forwarded-*` headers. |

---

## Layout

```
/var/www/app/                        # The Laravel app
├── app/
│   ├── Models/
│   │   ├── User.php                 # Owner login model (Auth)
│   │   ├── RestaurantModel.php      # Base class — pins to "menudirect" DB connection
│   │   ├── RestaurantSite.php       # Has SoftDeletes + notArchived global scope + observer
│   │   ├── MenuItem, MenuCategory, FoodOrder, FoodOrderItem,
│   │   │   Reservation, RestaurantLead, RestaurantStaff,
│   │   │   RestaurantPlan, RestaurantCustomDomain, Announcement,
│   │   │   LeadActivity, LeadEmailTrack, DemoSession,
│   │   │   OrderNotification, OrderAuditLog,
│   │   │   CateringInquiry, CateringPackage, DeliveryZone,
│   │   │   HelpArticle, HelpTour, HelpTourCompletion,
│   │   │   ShiftCloseout, HolidayHour       # All extend RestaurantModel
│   │   └── AuditLog.php             # Extends Illuminate Model (default mysql, not menudirect)
│   ├── Http/Controllers/
│   │   ├── Auth/                    # LoginController + PasswordResetController (ported from portal)
│   │   ├── Client/Restaurant*       # 9 owner-portal controllers
│   │   ├── Client/Traits/           # AuthorizesRestaurantSite + ClearsRestaurantSiteCache
│   │   ├── Admin/Restaurant*        # 2 admin controllers (sites + leads)
│   │   ├── Staff/                   # 3 staff/kitchen tablet controllers
│   │   ├── Api/                     # 10 restaurant API controllers
│   │   ├── SampleSiteController.php # Renders {slug}.menudirect.ca + custom domains
│   │   ├── MenuDirectController.php # Marketing apex lead form (5-layer defense)
│   │   └── SitemapController.php    # /sitemap.xml + /sitemap-restaurants.xml + IndexNow
│   ├── Http/Middleware/
│   │   ├── AdminMiddleware.php      # Checks is_admin
│   │   ├── StaffAuth.php            # Staff tablet auth (separate guard)
│   │   ├── VerifyCaptcha.php        # Rate-limit-based CAPTCHA for login attempts
│   │   └── TrustProxies (built-in)  # HAProxy header trust set in bootstrap/app.php
│   ├── Observers/
│   │   └── RestaurantSiteObserver.php  # Auto SEO: cache-bust + IndexNow ping on changes
│   ├── Jobs/
│   │   ├── SendOrderNotificationsJob, SendReservationNotificationsJob,
│   │   │   SendCateringInquiryNotificationsJob
│   │   └── NotifySearchEnginesJob.php  # IndexNow / Bing on restaurant changes
│   ├── Mail/                        # 7 mailables — order + reservation + catering + welcome
│   └── Services/
│       ├── ReservationService.php
│       ├── DeliveryZoneService.php
│       ├── TurnstileVerifier.php    # Cloudflare Turnstile (env-driven; off if no keys)
│       └── Audit/AuditService.php   # use User as Client (aliased — formerly required SOS Tech Client model)
├── resources/views/
│   ├── auth/                        # login.blade.php (with show-pwd toggle), forgot/reset, 2fa-challenge
│   ├── layouts/app.blade.php        # Clean MenuDirect layout (NOT the portal-era SOS-Tech one)
│   ├── client/restaurant/           # 20 owner-portal views
│   ├── admin/restaurant/            # 5 admin views
│   ├── staff/                       # Kitchen tablet UI
│   ├── samples/                     # Public restaurant site renderer
│   │   ├── layout.blade.php         # SEO meta tags + OG + canonical
│   │   ├── partials/schema.blade.php  # JSON-LD Restaurant structured data
│   │   ├── partials/                # gallery, hours, ordering, etc.
│   │   └── templates/               # 26 designs (bistro, coastal, urban, noir, etc.)
│   ├── menudirect/landing.blade.php # Marketing apex page with lead form
│   ├── components/                  # x-help-icon, x-recrop-button, etc.
│   ├── emails/                      # Restaurant order/reservation/catering email templates
│   └── seo/robots-restaurant.blade.php  # Per-subdomain robots.txt template
├── routes/
│   ├── web.php                      # Host-scoped: apex / www / portal / {slug} / fallback for custom domains
│   ├── api.php                      # /api/menudirect/leads (bearer-auth), /api/indexnow/submit, restaurant data + ordering + staff
│   └── console.php
├── config/
│   ├── database.php                 # menudirect (default mysql) + sostech_clients (cross-host, read-only)
│   ├── services.php                 # turnstile, indexnow, menudirect intake token, portal URL
│   └── samples.php                  # Demo/sample site config
├── database/migrations/             # users + audit_logs + archived_at + web_form source + ...
├── public/
│   ├── robots.txt                   # Apex robots.txt — AI bots explicitly allowed
│   ├── llms.txt                     # MenuDirect AI discovery profile
│   ├── 54a1d9cc35b4435b08d254864fc043aa.txt  # IndexNow ownership verification
│   ├── js/cart.js, reservation-widget.js
│   ├── images/templates/            # 19 subdirs of restaurant template imagery
│   ├── images/template-previews/    # Gallery preview shots
│   ├── images/menudirect/           # MenuDirect branding
│   └── storage -> ../storage/app/public  # symlinked
├── storage/
│   ├── app/public/restaurants/      # Restaurant logos, cover photos, menu item photos, gallery (~116MB)
│   └── logs/laravel.log
├── docs/
│   ├── superpowers/specs/2026-05-16-menudirect-vm-split-design.md  # Approved spec
│   └── superpowers/plans/2026-05-16-menudirect-vm-split.md         # Step-by-step plan
├── .env                             # Production config — never commit
├── bootstrap/app.php                # Middleware aliases (admin, staff.auth), trust proxies
└── CLAUDE.md                        # This file
```

---

## Database

Single local MySQL database: **`menudirect`** on `127.0.0.1`. User `menudirect`, password in `/root/.menudirect_db_pass` (chmod 600).

Tables of note:
- `users` — owner login records, populated by `menudirect:seed-users` from `sos_portal.clients` (subset that owns restaurants + admins). Frank's record is `id=1, is_admin=true, email=frank@sos-tech.ca`. **2FA cleared on all migrated users** (APP_KEY differs from portal — encrypted secrets can't be decrypted).
- `restaurant_sites` — the restaurants. Has `archived_at` (soft-archive) + `deleted_at` (soft-delete). Global scope `notArchived` filters out archived by default.
- `restaurant_custom_domains` — many-to-one with restaurant_sites. Primary domain is what `getPublicUrl()` returns.
- `restaurant_leads` — 4282+ lead records (mostly from `gnb_inspections` source, some from `web_form`).
- `migrations` — Laravel migration tracker. Has one manually-inserted entry for `2025_10_20_102945_create_audit_logs_table` (the table was created during the cutover before the migration could run cleanly).
- `audit_logs` — all auth events + admin actions.

### Eloquent connections

`config/database.php` defines:
- **`mysql`** (default) — local — used by `User`, `AuditLog`, anything that defaults
- **`menudirect`** — also local now (was cross-host during the test phase; flipped to `127.0.0.1` at cutover) — used by all `RestaurantModel` subclasses
- **`sostech_clients`** — cross-host to portal-host's `sos_portal` DB, used **only** by the `menudirect:seed-users` command. **Removed at T+7 cleanup.**

After the spec's "post-cutover refactor" item is done, `mysql` and `menudirect` should collapse to one connection. Deferred for now since both point at the same DB — no harm.

---

## Auth model

Owners log in at `https://portal.menudirect.ca/login` with their portal-era email + password (bcrypt hash was copied from `sos_portal.clients` during seed-users).

- **Frank** (id=1) = `frank@sos-tech.ca`, `is_admin=true` — sees all sites, can manage any.
- **Suwanna / Donna** (id=54) = `donna.b.watkins@gmail.com`, owner of `suwanna-thai`.
- **Bee Ty** (id=65) = `berenthamaety@gmail.com`, manager on `suwanna-thai` (Donna's daughter).
- **Kim Rowe** (id=66) = `tiajarowe@yahoo.ca`, manager on `suwanna-thai` (Donna's restaurant manager).
- **Tracey** (id=58) = `Tracey@wecanhelp.ca`, demo owner (burgers-at-busters + snows-soft-serve).
- A test `demo-MXTJyUt3U95Y@demo.menudirect.ca` account exists.

**Admin sees all sites** — replaces the portal-era client impersonation workflow. `RestaurantSitePolicy::manage` returns true if `$user->is_admin`. `RestaurantSiteController::index` shows all sites when admin; owner's + co-managed when not.

### Multi-admin per restaurant (added 2026-05-18)

`restaurant_site_user` pivot table — many-to-many between users and restaurant sites, with a `role` enum (`owner` / `manager`). Both roles get full management access today; the distinction is informational/audit-only.

Relations:
- `RestaurantSite::managers()` — `BelongsToMany(User::class, 'restaurant_site_user')->withPivot('role')`
- `User::managedRestaurants()` — inverse
- `User::ownedRestaurants()` — legacy `client_id` (HasMany)

`RestaurantSitePolicy::manage` checks **three** paths in order:
1. `$user->is_admin` → true
2. Legacy `$site->client_id === $user->id` → true
3. `$site->managers()->where('users.id', $user->id)->exists()` → true

When adding a new restaurant team member: `php` shell or admin UI → create the User → insert pivot row with their role. Trigger `Password::sendResetLink([...email])` so they set their own password on first login (Mailcow handles delivery).

Current multi-admin sites:
- **suwanna-thai** (id=8) — Donna (owner), Bee Ty (manager), Kim Rowe (manager).

**2FA is intentionally disabled** for all migrated users. Re-enabling would require either:
- Copying portal's `APP_KEY` to this VM (security tradeoff: key in two places), OR
- Building a fresh 2FA enrollment screen so users opt back in with new-VM-encrypted secrets.

Both deferred.

**Login UI** has a click-to-show password toggle (vanilla JS, no Alpine dependency). Same toggle is also on the portal.sos-tech.ca login page.

---

## SEO / AIO — fully automated

**This is set-and-forget.** When Frank adds, renames, archives, or restores a restaurant, the system reacts on its own:

1. `RestaurantSiteObserver` listens to `created`, `updated`, `deleted`, `restored` events on `App\Models\RestaurantSite`.
2. On any event: busts `sitemap.restaurants.xml`, `sitemap.site.{slug}.xml`, `restaurant_site:{slug}`, and `custom_domain:*` caches.
3. On created/restored/updated-with-watched-column-change (slug, status, custom_domain, archived_at, name): queues `NotifySearchEnginesJob`.
4. The job POSTs to `api.indexnow.org/IndexNow` with the restaurant's URLs grouped by host. IndexNow notifies Bing, Yandex, Naver in one shot.
5. **Coalesce key** (60 sec) prevents spam on rapid edits.

Google doesn't accept programmatic URL submission for general content — they pull our sitemap on their own schedule (~hours). The 10-minute sitemap cache TTL + the observer's cache-bust on every change means Google always sees fresh content on their next pass.

### SEO URLs served
| URL | Content |
|---|---|
| `https://menudirect.ca/sitemap.xml` | Sitemap index |
| `https://menudirect.ca/sitemap-marketing.xml` | Apex marketing page |
| `https://menudirect.ca/sitemap-restaurants.xml` | All active restaurant subdomains — **only menudirect.ca URLs** (cross-domain URLs are forbidden by sitemaps.org spec) |
| `https://menudirect.ca/robots.txt` | AI bot allowlist + `Sitemap:` directive |
| `https://menudirect.ca/llms.txt` | MenuDirect AI discovery profile (4.5KB) |
| `https://menudirect.ca/54a1d9cc35b4435b08d254864fc043aa.txt` | IndexNow ownership verification |
| `https://menudirect.ca/api/indexnow/submit` | POST (throttle:5,1) — manually force a re-ping |
| `https://{slug}.menudirect.ca/sitemap.xml` | Per-restaurant sitemap (single URL) |
| `https://{slug}.menudirect.ca/robots.txt` | Per-restaurant robots — AI bots allowed |

### JSON-LD Restaurant schema
Already emitting on every restaurant page via `samples/partials/schema.blade.php` (PHP array + `json_encode()`, **never** Blade in JSON). 22 templates include it. Each page gets:
- Restaurant name, address (PostalAddress), telephone, email
- OpeningHoursSpecification (parsed from human-readable hours)
- Menu / MenuSection / MenuItem with prices
- servesCuisine, priceRange, logo / image

### Custom domain canonicalization
`RestaurantSite::getPublicUrl()`:
1. If `custom_domain` column set → use that
2. Otherwise if primary `restaurant_custom_domains` row → use that
3. Else fallback to `https://{slug}.menudirect.ca`

The rendered page emits `<link rel="canonical" href="{getPublicUrl()}">`. So when Google discovers `burgers-at-busters.menudirect.ca` via the sitemap and visits, it sees `<canonical>` pointing at `burgersatbusters.ca` and indexes the custom domain instead. The sitemap stays compliant with the cross-domain rule, but Google still indexes the right canonical.

---

## Public restaurant rendering

`{slug}.menudirect.ca/` → `Route::domain('{slug}.menudirect.ca')` → `SampleSiteController::show($slug)`.

The controller:
1. Checks `config/samples.php` for static sample sites (legacy)
2. Falls through to `fetchFromPortalApi($slug)` — which currently calls `http://127.0.0.1/api/restaurant/{slug}` (self-call via Nginx catch-all)
3. Renders the matching template (`samples/templates/{template-name}.blade.php`) with the data

**Self-call architecture** is kept for now — the SampleSiteController's caching layer (1-hour Cache::remember) sits in front of the API call. Could be refactored to direct Eloquent queries for a perf gain, but it's not blocking anything.

**Custom-domain restaurants** (`burgersatbusters.ca`, etc.) hit `Route::fallback()` which calls `SampleSiteController::showByDomain()` — that resolves domain → slug via `RestaurantCustomDomain` and delegates to `show($slug)`.

---

## Lead intake (5-layer defense)

`menudirect.ca/lead` POST → `MenuDirectController::submitLead()`:
1. **Rate limit**: 3/IP/hour
2. **Honeypot** (`name="website"` hidden field — bots fill, humans don't)
3. **Time check** (`_form_token` with `base64_encode(time())`; <3s rejects)
4. **Content filter**: URL shorteners, spam keywords, disposable email domains
5. **Cloudflare Turnstile** (env-driven; off when `TURNSTILE_SITE_KEY` not set — still off today)

All five fire silently — bots get a fake "Thank you" page rather than an error, so they don't learn what tripped them. Failed defenses log via `Log::warning()`.

Then the controller:
- Sends Frank an email (always, even if API call fails — never lose a lead)
- POSTs to `https://portal.menudirect.ca/api/menudirect/leads` (self-call) with bearer token `MENUDIRECT_INTAKE_TOKEN`
- Lead lands in `restaurant_leads` with `source='web_form'`

---

## Sos-tech.ca relationship

- **Lead form** still lives on sos-tech.ca/menudirect.ca → POSTs to new VM's intake API. Sos-tech's `PORTAL_API_URL=http://192.168.23.65`.
- **Legacy `sos-tech.ca/s/{slug}` URLs** → 301 redirect to `{slug}.menudirect.ca` (preserves Google ranking).
- **Portal restaurant routes** (`portal.sos-tech.ca/client/restaurant*`, `/staff*`, `/admin/restaurant*`, etc.) → 302 redirect to equivalent path on `portal.menudirect.ca`.
- Everything else on portal.sos-tech.ca and sos-tech.ca (hosting, email, domain, billing) is untouched — **do not redirect or modify** those.

If something doesn't work on a redirected URL, the fix is here on this VM, not on portal.

---

## Operational commands

### After ANY `.env` change
```bash
cd /var/www/app && sudo -u www-data php artisan config:clear && sudo -u www-data php artisan optimize
systemctl restart php8.3-fpm menudirect-queue
```

### After ANY code change (controllers/models/views)
```bash
cd /var/www/app && sudo -u www-data php artisan optimize
# If editing views, also:
sudo -u www-data php artisan view:clear
# If touching a class that PHP-FPM has cached:
systemctl restart php8.3-fpm
```

### Clear Redis (sessions + sitemap caches + queue)
```bash
redis-cli FLUSHDB
```
Use sparingly — this logs out all owner sessions.

### Trigger a manual IndexNow ping
```bash
curl -X POST https://menudirect.ca/api/indexnow/submit
```
Returns `{"submitted": N, "indexnow_status": 200}`.

### Rebuild seed-users (catch any new SOS Tech client who started owning a restaurant)
```bash
cd /var/www/app && sudo -u www-data php artisan menudirect:seed-users
```
**Note**: this command requires the `sostech_clients` cross-host connection. **Remove this command at T+7 cleanup.**

### Tail logs
```bash
tail -f /var/www/app/storage/logs/laravel.log
journalctl -u menudirect-queue -f
tail -f /var/log/nginx/access.log
```

### Service status
```bash
systemctl status nginx php8.3-fpm mysql redis-server menudirect-queue
```

---

## Deferred items (do NOT do without explicit Frank approval)

These are tracked but intentionally not done — don't proactively pick them up.

1. **T+7 cutover cleanup** (target: 2026-05-25)
   - Revoke portal-host MySQL grants: `menudirect_app@192.168.23.65`, `sostech_reader@192.168.23.65`
   - Drop `menudirect` database on portal-host (keep a final backup)
   - Remove `menudirect` + `sostech_clients` connection from this VM's `config/database.php`
   - Remove `menudirect:seed-users` command from this VM
   - Delete restaurant code from `/var/www/portal/app/` (on portal-host)
   - Delete restaurant code from `/var/www/sos-tech/` (on portal-host) — except `TurnstileVerifier` which the SOS Tech contact form still uses
   - Drop `SOSTECH_DB_*` env vars from this VM's `.env`

2. **2FA re-enablement** — requires decision on APP_KEY sharing vs re-enrollment UI

3. **Stripe Connect** — no restaurant currently uses Stripe Connect. Stripe Connect platform is shared with SOS Tech for now. Defer the platform-split decision until self-serve signup ships.

4. **MenuDirect billing** — restaurant SaaS plan billing stays in SOS Tech portal (KISS). Frank manually adds each new customer there. Building native Stripe Subscriptions on this VM is deferred until self-serve signup is wanted.

5. **Self-serve restaurant signup** at `portal.menudirect.ca/register` — deferred. Currently Frank creates restaurants manually via `/admin/restaurant/create`.

6. **Admin observability** — Netdata is on this VM (per spec). Set up alerting (Pushover / Discord / SMS) post-cutover.

7. **Automated nightly backups** — manual `mysqldump` and rsync work today. Set up restic with a remote target.

8. **Collapse `mysql` and `menudirect` Eloquent connections** into one — cosmetic. Defer.

9. **SSH hardening** — `PasswordAuthentication no` on this VM. Per Frank: not now.

10. **Demo site curation** — Frank will decide which demo sites to archive (using the new `archived_at` column) once he's settled in.

---

## Known gotchas

These bit us during the migration and could bite again — keep them in mind:

- **CRLF line endings break `sed` matchers**. Specifically `RestaurantCustomDomain.php` came over with CRLF. If you sed-edit something and the change doesn't take effect, suspect line endings: `tr -d '\r' < file > file.tmp && mv file.tmp file`. (Or check git status — git will warn about CRLF.)

- **`Cache::remember()` cannot cache Symfony Response objects** — they don't round-trip through Redis serialization (you get `__PHP_Incomplete_Class` on cache hit). Cache the *string* payload and wrap in `response()` each call. This was the cause of Google's sitemap 500s during initial SEO setup.

- **OPcache holds old code after edits**. After modifying any class file, run `systemctl restart php8.3-fpm` to reload. The CLI (`php artisan ...`) has a separate OPcache, so a CLI tinker test passing doesn't prove the web path works yet — always test via HTTP after FPM restart.

- **Eloquent global scope `notArchived` on RestaurantSite** filters `WHERE archived_at IS NULL` by default. To see all sites including archived, use `RestaurantSite::withoutGlobalScope('notArchived')`. SoftDeletes also filters `deleted_at`.

- **Route precedence by domain**: Laravel matches `Route::domain()` declarations in order. `{slug}.menudirect.ca` wildcards would happily swallow `portal.menudirect.ca` if declared first. The current order in `routes/web.php` puts explicit hosts first, wildcard last. Don't reorder.

- **Cross-domain URLs in sitemap.xml are forbidden** by sitemaps.org spec. The `menudirect.ca` sitemap must list ONLY `*.menudirect.ca` URLs. Custom-domain canonicalization happens via `<link rel="canonical">` on the rendered page, not in the sitemap.

- **HAProxy bot rule** (`bad_bots_ext`) returns **403** for curl/wget when the request has `CF-Connecting-IP`. To verify Cloudflare-proxied custom-domain URLs externally, pass `-A "Mozilla/5.0 ..."`. The `*.menudirect.ca` subdomains aren't behind Cloudflare proxy so plain curl works there.

- **Storage files (logos, cover photos, gallery, menu item photos)** live at `/var/www/app/storage/app/public/restaurants/`. The `public/storage` symlink → `storage/app/public` (done via `php artisan storage:link`). If images 404 after a deploy, suspect the symlink got blown away.

- **AuditService `Client` aliasing**: AuditService was written assuming `App\Models\Client` exists (SOS Tech's clients table). On this VM, there's no `Client` model — `use App\Models\User as Client;` aliases it. Any future AuditService method that adds `Client $param` will work without rewrites, because `Client` resolves to `User` here.

- **Frank's uncommitted changes on the portal-host** repo are extensive (~70+ files modified, ~30+ untracked). When making changes there, commit only the specific files you touched — `git add` should be explicit, never `git add -A`.

- **Stale migrations table entries** — some migrations were applied at cutover via direct SQL (e.g. `archived_at` column was on the imported `menudirect` dump but never tracked in this VM's `migrations` table). If `php artisan migrate` errors with "Column already exists" / "Table already exists," manually mark the migration as run: `INSERT IGNORE INTO migrations (migration, batch) VALUES ('<file_basename_without_ext>', 99);`. Tracked stale entries are in batch 99.

---

## Recent change history (most recent first)

```
a98213d feat(rbac): multi-admin per restaurant via restaurant_site_user pivot
d85eeae docs: comprehensive operational handoff for local Claude Code agent
a9a04e3 feat(seo): auto cache-bust + IndexNow ping on restaurant lifecycle
188510f fix(seo): menudirect.ca sitemap lists only menudirect.ca URLs
c383e1e fix(seo): sitemap controllers cache the XML string not the Response object
895bdfc feat(seo): SitemapController, llms.txt, robots.txt with AI bot allowlist, IndexNow
19e372f fix(urls): restaurant URLs use slug.menudirect.ca subdomain + custom domain support
2e12bad fix(routes): add missing locations + payments + delivery-zones route blocks
62ff49d fix(auth): name the POST /two-factor-challenge route as two-factor.verify
e8d28b0 feat(auth): show/hide password toggle on MenuDirect login screen
a36f770 feat: admin can manage any restaurant site (replaces portal-era impersonation)
6dd373c fix(config): alias menudirect_intake_token under services.portal
a9cf920 fix: D2 login flow — full end-to-end working
1d5ad81 fix: D1 smoke-test cleanup — missing pieces caught during validation
5b0c113 fix(routes): move {slug}.menudirect.ca group after portal.menudirect.ca
e1e33e0 feat: port 8 missing restaurant models
be686a7 feat(routes): scope routes by host domain
3f973af feat(public-site): port restaurant templates + marketing apex + assets
3c4a3e5 feat(public-site): port SampleSiteController, MenuDirectController, TurnstileVerifier
bf07a7a feat: port restaurant routes + auth scaffolding
3b5d923 feat: port restaurant Blade views
099a901 feat: port restaurant API controllers
0d49e3b feat: port admin + staff restaurant controllers
db79d3e feat: port 8 owner-portal restaurant controllers
5a2cdb3 feat: port 3 jobs + 7 mailables + restaurant email templates
328cb5f feat: port ReservationService and DeliveryZoneService
291c020 feat(db): archived_at on restaurant_sites + global scope
03b928a feat: port 16 restaurant models from portal
7de7e36 feat: menudirect:seed-users command
6a22a77 feat: RestaurantModel base class pinning connection to menudirect
cd24173 feat(db): add MenuDirect-specific columns to users table
faf8799 feat(db): add menudirect + sostech_clients cross-host connections
c646523 Initial commit: Laravel 13.9 scaffold + MenuDirect VM split design spec
```

Run `git log --oneline` for the always-current list.

---

## Reference docs

- **Approved spec**: `docs/superpowers/specs/2026-05-16-menudirect-vm-split-design.md` — full design with architecture diagram, scope, DB mechanics, cutover sequence, deferred items.
- **Implementation plan**: `docs/superpowers/plans/2026-05-16-menudirect-vm-split.md` — step-by-step plan (31 tasks across 6 phases) that drove the cutover.

These are detailed and authoritative; consult them when in doubt about *why* something is structured a particular way.

---

## When in doubt

1. **Read the spec** (`docs/superpowers/specs/2026-05-16-menudirect-vm-split-design.md`) — it explains the architectural choices.
2. **Check the gotchas section above** before assuming something is broken.
3. **Test on this VM via HTTP** with a real browser UA before declaring something fixed — CLI / tinker tests can mislead because of OPcache separation between CLI and FPM.
4. **Don't touch portal.sos-tech.ca or sos-tech.ca** — they're separate concerns. Modifications there must be explicitly requested by Frank.
5. **Frank is the sole operator AND the sole MenuDirect admin** (`is_admin=true`). When he says "I want to do X for restaurants," he means as admin via portal.menudirect.ca, NOT impersonating a specific owner.
