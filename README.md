# MenuDirect

**Commission-free online ordering and websites for independent Canadian restaurants.**

MenuDirect is a restaurant SaaS that gives independent restaurants a modern website with built-in
online ordering, reservations, and catering — without the 25–30% commissions charged by delivery
platforms like Skip the Dishes, DoorDash, or Uber Eats. Restaurants keep their orders, their
customer data, and their direct relationship with diners.

Operated by [SOS Technical Services](https://sos-tech.ca) (Hampton, NB). This repository is the
Laravel application that powers the marketing site, the owner portal, and every public restaurant
site. It was split out of the SOS Tech monorepo onto its own VM in May 2026.

---

## Architecture

The application is multi-tenant by **hostname** — a single Laravel codebase serves several roles,
routed by `Route::domain(...)` in `routes/web.php`:

| Host | Role |
|---|---|
| `menudirect.ca` | Marketing apex — lead form, sitemaps, `robots.txt`, `llms.txt` |
| `www.menudirect.ca` | Redirects to the apex |
| `portal.menudirect.ca` | Owner portal — owners manage their site (`/client/*`) plus admin tools (`/admin/*`) |
| `{slug}.menudirect.ca` | Public restaurant sites (e.g. `suwanna-thai.menudirect.ca`) |
| Custom domains | The same restaurant sites on the restaurant's own domain (e.g. `burgersatbusters.ca`), resolved via `Route::fallback()` |

In production the app sits behind **HAProxy** (SSL termination) with **Cloudflare** in front for
DDoS mitigation. The app trusts `X-Forwarded-*` headers (`bootstrap/app.php`). Public restaurant
sites are rendered by `SampleSiteController`; the marketing apex by `MenuDirectController`.

> Operational details (deploy steps, service layout, known gotchas) live in **[`CLAUDE.md`](CLAUDE.md)**.

---

## Tech stack

| | |
|---|---|
| Language | PHP 8.3 |
| Framework | Laravel 13 |
| Database | MySQL 8 |
| Cache / sessions / queue | Redis 7 |
| Web server | Nginx + PHP-FPM |
| Front end | Blade, Vite, Tailwind CSS v4, Alpine.js |
| Auth | Session auth + Laravel Sanctum (API) |
| Mail | SMTP (Mailcow) |
| SMS / telephony | VOIP.MS API |

---

## Features

**Restaurant websites**
- 26 mobile-first templates with per-restaurant brand colours
- Photo galleries, hours, location/maps, social links
- Schema.org `Restaurant` JSON-LD on every page
- Custom domain support with automatic `<link rel="canonical">`

**Online ordering**
- Menu editor — categories, items, modifiers, dietary badges, photos, drag-to-reorder
- Pay-at-pickup model, scheduled pre-orders
- Distance-based delivery zones with auto-calculated fees
- SMS + email order notifications; kitchen/server tablet UI

**Reservations & catering**
- Built-in reservation system plus OpenTable / Resy / email integrations
- Catering inquiry intake

**Owner portal**
- Per-restaurant multi-admin via the `restaurant_site_user` pivot, plus a legacy single-owner
  path (`restaurant_sites.client_id`)
- A platform admin sees and manages every site (replaces the old impersonation workflow)

**Marketing & growth**
- Lead intake with a 5-layer spam defense (rate limit, honeypot, time-check, content filter,
  Cloudflare Turnstile)
- Automated SEO/AIO: sitemaps, IndexNow pings on restaurant changes, `llms.txt` AI-discovery
  profile, AI-bot-friendly `robots.txt`

---

## Repository structure

```
app/
├── Http/Controllers/
│   ├── Client/          # Owner-portal controllers (+ Traits: AuthorizesRestaurantSite, ClearsRestaurantSiteCache)
│   ├── Admin/           # Admin site + lead management
│   ├── Staff/           # Kitchen / server tablet
│   ├── Api/             # Restaurant data, ordering, staff APIs
│   ├── SampleSiteController.php   # Renders {slug}.menudirect.ca + custom domains
│   ├── MenuDirectController.php   # Marketing apex + lead form
│   └── SitemapController.php      # Sitemaps + IndexNow
├── Http/Middleware/     # AdminMiddleware, StaffAuth, VerifyCaptcha, SecurityHeaders
├── Models/              # RestaurantModel base + ~30 domain models
├── Observers/           # RestaurantSiteObserver (auto cache-bust + IndexNow)
├── Jobs/                # Order/reservation/catering notifications, NotifySearchEnginesJob
└── Services/            # Reservation, DeliveryZone, Turnstile, Audit
resources/views/
├── client/ admin/ staff/    # Portal UIs
├── samples/templates/       # 26 public restaurant designs
└── menudirect/              # Marketing apex
config/                  # database, services, samples, images, demo, restaurant_templates, ...
routes/                  # web.php (host-scoped), api.php, console.php
```

---

## Local development

Requires PHP 8.3, Composer, Node 20+, MySQL 8, and Redis.

```bash
git clone git@github.com:frankkahle/menudirect.git
cd menudirect

composer install
npm install

cp .env.example .env
php artisan key:generate

# Point the DB connection at your local MySQL in .env, then:
php artisan migrate

npm run build            # or `npm run dev` for HMR
php artisan serve
```

Because routing is **host-based**, map the hostnames locally (e.g. in `/etc/hosts`) to test the
different roles:

```
127.0.0.1  menudirect.ca portal.menudirect.ca demo-bistro.menudirect.ca
```

Then browse `http://portal.menudirect.ca:8000/login`, etc.

---

## Configuration

Configuration is environment-driven. Key `config/` files:

- `database.php` — the `menudirect` MySQL connection (and a read-only `sostech_clients` link used
  only by the `menudirect:seed-users` command during migration)
- `services.php` — Turnstile, IndexNow, Mapbox, portal intake token
- `images.php` — image aspect ratios for the cropper (logo / cover / gallery / menu items)
- `restaurant_templates.php` — template catalog metadata
- `samples.php`, `demo.php` — demo/sample-site behaviour
- `security.php` — `CSP_ENFORCE` flag for the Content-Security-Policy header

Secrets live in `.env` only and are never committed.

---

## Background processing

- **Queue worker** (`menudirect-queue` systemd unit) processes notification jobs and IndexNow pings.
- **Scheduler** (`/etc/cron.d/menudirect`) runs `php artisan schedule:run` every minute.
- **SEO automation:** `RestaurantSiteObserver` busts the relevant sitemap/site caches and queues
  `NotifySearchEnginesJob` (IndexNow → Bing/Yandex/Naver) whenever a restaurant is created,
  updated, archived, or restored.

---

## Security

- HTTPS-only with HSTS (terminated at the edge); `SecurityHeaders` middleware adds
  Referrer-Policy, Permissions-Policy, COOP, and a Content-Security-Policy (Report-Only until
  front-end assets are fully bundled locally; flip `CSP_ENFORCE=true` to enforce).
- Session cookies are `Secure` + `HttpOnly`; CSRF protection on all state-changing requests.
- Lead and auth endpoints are rate-limited; login is gated by a CAPTCHA after repeated attempts.
- Vulnerability reports: see [`/.well-known/security.txt`](public/.well-known/security.txt).

---

## Deployment & operations

This app runs on a dedicated VM behind HAProxy/Cloudflare. Deploys are git-based; after pulling
code run `php artisan optimize` and reload PHP-FPM (OPcache holds compiled classes). After `.env`
changes, `config:clear && optimize`, then restart `php8.3-fpm` and `menudirect-queue`.

Full operational runbook — service layout, cache management, IndexNow nudges, the migration
gotchas, and the list of deferred work — is in **[`CLAUDE.md`](CLAUDE.md)**.

---

## Status & ownership

Live and serving paying restaurants in New Brunswick, Canada. Migrated to its own VM on
2026-05-18; some platform features (self-serve signup, native billing, Stripe Connect split) are
intentionally deferred — see the "Deferred items" section of `CLAUDE.md`.

Proprietary — © SOS Technical Services. Not for redistribution.
