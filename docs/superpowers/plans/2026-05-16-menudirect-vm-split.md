# MenuDirect VM Split-off Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the MenuDirect (restaurant SaaS) codebase from `/var/www/portal` and `/var/www/sos-tech` onto a dedicated VM at 192.168.23.65, with its own MySQL, its own `users` table for owner auth, and a single-cutover HAProxy flip.

**Architecture:** Two-phase migration. Phase A–C build out the new VM in parallel with the still-live portal/sos-tech (cross-host MySQL connection lets new VM read existing restaurant data live). Phase D validates everything works on the new VM. Phase E is the actual cutover (HAProxy flip + DB import + flip new VM to local DB). Phase F is post-cutover cleanup at T+7.

**Tech Stack:** Laravel 13.9.0, PHP 8.3.31, MySQL 8.0.45, Redis 7.0.15, Nginx 1.24.0, Supervisor (queue worker via systemd), HAProxy (external SSL termination).

**Spec reference:** `/var/www/app/docs/superpowers/specs/2026-05-16-menudirect-vm-split-design.md`

---

## File Structure Overview

### New VM (`/var/www/app`) — files this plan creates/modifies

```
app/
├── Models/
│   ├── User.php                        (create — owner login model)
│   ├── RestaurantModel.php             (create — base class, sets connection)
│   ├── RestaurantSite.php              (port from portal)
│   ├── MenuItem.php                    (port)
│   ├── MenuCategory.php                (port)
│   ├── FoodOrder.php                   (port)
│   ├── FoodOrderItem.php               (port)
│   ├── Order.php                       (port)
│   ├── OrderNotification.php           (port)
│   ├── OrderAuditLog.php               (port)
│   ├── Reservation.php                 (port)
│   ├── RestaurantLead.php              (port)
│   ├── RestaurantStaff.php             (port)
│   ├── RestaurantPlan.php              (port)
│   ├── RestaurantCustomDomain.php      (port)
│   ├── Announcement.php                (port)
│   ├── LeadActivity.php                (port)
│   ├── LeadEmailTrack.php              (port)
│   └── DemoSession.php                 (port)
├── Http/Controllers/
│   ├── Auth/                           (Laravel default)
│   ├── Client/Restaurant*Controller    (port — 8 controllers)
│   ├── Admin/Restaurant*Controller     (port — 5 controllers)
│   ├── Staff/                          (port — 3 controllers)
│   ├── Api/Restaurant*                 (port — ~12 controllers)
│   ├── PublicSite/SampleSiteController (port from sos-tech)
│   └── Public/MenuDirectController     (port from sos-tech — lead form)
├── Services/
│   ├── ReservationService.php          (port)
│   ├── DeliveryZoneService.php         (port)
│   ├── TurnstileVerifier.php           (port from sos-tech)
│   └── Stripe/StripeConnectService.php (port the restaurant-relevant parts)
├── Jobs/
│   ├── SendOrderNotificationsJob.php   (port)
│   ├── SendReservationNotificationsJob.php (port)
│   └── SendCateringInquiryNotificationsJob.php (port)
├── Mail/
│   ├── NewFoodOrder.php                (port)
│   ├── OrderConfirmation.php           (port)
│   ├── OrderStatusUpdate.php           (port)
│   ├── NewReservation.php              (port)
│   ├── ReservationConfirmation.php     (port)
│   ├── ReservationStatusUpdate.php     (port)
│   └── RestaurantWelcome.php           (port)
└── Console/Commands/
    └── SeedUsersFromClients.php        (create — menudirect:seed-users)

config/database.php                     (modify — add menudirect + sostech_clients connections)
config/services.php                     (modify — add turnstile, stripe connect keys)
routes/web.php                          (modify — public site + marketing + owner portal + admin)
routes/api.php                          (modify — restaurant API endpoints)
database/migrations/
    ├── 2026_05_16_000001_create_users_table.php          (create)
    └── 2026_05_17_000001_add_archived_at_to_restaurant_sites.php (create — schema change)
resources/views/
    ├── public-site/                    (port samples/ → renamed for clarity)
    ├── client/restaurant/              (port owner portal views)
    ├── admin/restaurant/               (port admin views)
    └── menudirect/landing.blade.php    (port marketing apex)
public/images/
    ├── templates/                      (port 19 subdirs)
    ├── template-previews/              (port)
    └── menudirect/                     (port)
.env                                    (modify — add MENUDIRECT_DB_*, SOSTECH_DB_* connections, TURNSTILE_*, STRIPE_*)
```

### Portal host (`/var/www/portal`) — modifications for transition + cleanup

```
routes/web.php          (E1: comment out restaurant routes — cutover branch)
routes/api.php          (E1: comment out restaurant routes — cutover branch)
config/database.php     (F1: remove menudirect connection at T+7)
app/Models/*            (F1: delete restaurant models at T+7)
app/Http/Controllers/*  (F1: delete restaurant controllers at T+7)
... (etc — full restaurant code deletion at T+7)
```

### Sos-tech (`/var/www/sos-tech`) — cleanup at T+7

```
routes/web.php                  (F2: delete restaurant routes)
app/Http/Controllers/SampleSiteController.php  (F2: delete)
app/Http/Controllers/MenuDirectController.php  (F2: delete)
resources/views/samples/        (F2: delete)
resources/views/menudirect/     (F2: delete)
public/images/templates/        (F2: delete)
... (etc)
```

### Portal-host MySQL — changes

- A1: Create grant `menudirect_app@192.168.23.65` (test phase).
- A1: Create grant `sostech_reader@192.168.23.65` (read-only on `sos_portal.clients`, for seed-users).
- F1 (T+7): Drop both grants. Drop `menudirect` database.

---

## Phase A — New VM Database Setup

### Task A1: Portal-host MySQL grants for cross-host access

**Files:** None (MySQL admin commands)

- [ ] **Step 1: Generate two passwords**

```bash
# On portal-host:
MD_PASS=$(openssl rand -hex 32)
SC_PASS=$(openssl rand -hex 32)
echo "MENUDIRECT_PASS=$MD_PASS"
echo "SOSTECH_PASS=$SC_PASS"
# Save these — they go in new VM .env in Task A3
```

Expected: two 64-char hex strings printed.

- [ ] **Step 2: Verify portal-host MySQL allows LAN binding**

```bash
grep -E "^bind-address" /etc/mysql/mysql.conf.d/mysqld.cnf
```

Expected: `bind-address = 0.0.0.0` or commented out. If it's `127.0.0.1`, change to `0.0.0.0` and `systemctl restart mysql`.

- [ ] **Step 3: Create the two grants**

```bash
mysql -uroot <<SQL
CREATE USER 'menudirect_app'@'192.168.23.65' IDENTIFIED BY '$MD_PASS';
GRANT ALL ON menudirect.* TO 'menudirect_app'@'192.168.23.65';
CREATE USER 'sostech_reader'@'192.168.23.65' IDENTIFIED BY '$SC_PASS';
GRANT SELECT ON sos_portal.clients TO 'sostech_reader'@'192.168.23.65';
FLUSH PRIVILEGES;
SQL
```

- [ ] **Step 4: Verify connectivity from new VM**

```bash
PORTAL_IP=$(hostname -I | awk '{print $1}')
ssh menudirect "mysql -h $PORTAL_IP -u menudirect_app -p$MD_PASS menudirect -e 'SELECT COUNT(*) AS sites FROM restaurant_sites;'"
ssh menudirect "mysql -h $PORTAL_IP -u sostech_reader -p$SC_PASS sos_portal -e 'SELECT COUNT(*) AS clients FROM clients;'"
```

Expected: row counts returned (sites ≥ 11, clients > 0). If "Access denied", check bind-address and password.

- [ ] **Step 5: Commit a record of the grants** (operational notes file on portal-host)

```bash
cat > /root/menudirect-migration-grants.txt <<EOF
Cross-host grants for MenuDirect VM split (created $(date -I)):
  menudirect_app@192.168.23.65 / password: <see new VM .env MENUDIRECT_DB_PASSWORD>
  sostech_reader@192.168.23.65 / password: <see new VM .env SOSTECH_DB_PASSWORD>
Revoke at T+7 post-cutover:
  DROP USER 'menudirect_app'@'192.168.23.65';
  DROP USER 'sostech_reader'@'192.168.23.65';
EOF
chmod 600 /root/menudirect-migration-grants.txt
```

---

### Task A2: Define both Eloquent connections on new VM

**Files:**
- Modify: `/var/www/app/config/database.php`
- Modify: `/var/www/app/.env`

- [ ] **Step 1: Add the two new connections in `config/database.php`**

Inside the `'connections' => [ ... ]` array (alongside the existing `mysql` entry), add:

```php
        'menudirect' => [
            'driver' => 'mysql',
            'host' => env('MENUDIRECT_DB_HOST', '127.0.0.1'),
            'port' => env('MENUDIRECT_DB_PORT', '3306'),
            'database' => env('MENUDIRECT_DB_DATABASE', 'menudirect'),
            'username' => env('MENUDIRECT_DB_USERNAME', 'forge'),
            'password' => env('MENUDIRECT_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],

        'sostech_clients' => [
            'driver' => 'mysql',
            'host' => env('SOSTECH_DB_HOST', '127.0.0.1'),
            'port' => env('SOSTECH_DB_PORT', '3306'),
            'database' => env('SOSTECH_DB_DATABASE', 'sos_portal'),
            'username' => env('SOSTECH_DB_USERNAME', 'forge'),
            'password' => env('SOSTECH_DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
```

- [ ] **Step 2: Append env vars (use the passwords from A1)**

```bash
ssh menudirect 'cat >> /var/www/app/.env <<EOF

# MenuDirect restaurant data — cross-host during test phase, flipped to localhost at cutover
MENUDIRECT_DB_HOST=<PORTAL_HOST_IP>
MENUDIRECT_DB_PORT=3306
MENUDIRECT_DB_DATABASE=menudirect
MENUDIRECT_DB_USERNAME=menudirect_app
MENUDIRECT_DB_PASSWORD=<MD_PASS-FROM-A1>

# SOS Tech clients table — read-only access during test + seeding phase; removed at T+7
SOSTECH_DB_HOST=<PORTAL_HOST_IP>
SOSTECH_DB_PORT=3306
SOSTECH_DB_DATABASE=sos_portal
SOSTECH_DB_USERNAME=sostech_reader
SOSTECH_DB_PASSWORD=<SC_PASS-FROM-A1>
EOF'
```

Replace `<PORTAL_HOST_IP>`, `<MD_PASS-FROM-A1>`, `<SC_PASS-FROM-A1>` with actual values.

- [ ] **Step 3: Rebuild config cache and verify**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan config:clear && \
  sudo -u www-data php artisan optimize 2>&1 | tail -6 && \
  sudo -u www-data php artisan tinker --execute="echo \DB::connection(\"menudirect\")->select(\"SELECT COUNT(*) AS c FROM restaurant_sites\")[0]->c . \"\n\"; echo \DB::connection(\"sostech_clients\")->select(\"SELECT COUNT(*) AS c FROM clients\")[0]->c . \"\n\";"'
```

Expected: two counts printed (sites + clients).

- [ ] **Step 4: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add config/database.php && git commit -m "feat(db): add menudirect + sostech_clients cross-host connections

Test-phase wiring so new VM can read restaurant data from portal-host MySQL
and seed users from sos_portal.clients. Cutover flips MENUDIRECT_DB_HOST to
local; T+7 cleanup removes sostech_clients connection entirely."'
```

---

### Task A3: Migration for new `users` table

**Files:**
- Create: `/var/www/app/database/migrations/2026_05_16_120000_create_users_table.php`

- [ ] **Step 1: Check for Laravel's default `users` migration**

```bash
ssh menudirect 'ls /var/www/app/database/migrations/ | grep users'
```

Expected: a file like `0001_01_01_000000_create_users_table.php` (Laravel scaffold default). If present, we **modify** it; if absent, we **create**.

- [ ] **Step 2: Replace its content with the MenuDirect users schema**

```bash
ssh menudirect 'cat > /var/www/app/database/migrations/0001_01_01_000000_create_users_table.php <<'\''PHP'\''
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("users", function (Blueprint \$table) {
            \$table->id();
            \$table->string("name")->nullable();
            \$table->string("email")->unique();
            \$table->timestamp("email_verified_at")->nullable();
            \$table->string("password");
            \$table->boolean("is_admin")->default(false);
            \$table->text("two_factor_secret")->nullable();
            \$table->text("two_factor_recovery_codes")->nullable();
            \$table->timestamp("two_factor_confirmed_at")->nullable();
            \$table->rememberToken();
            \$table->timestamps();
            \$table->index("email");
        });

        Schema::create("password_reset_tokens", function (Blueprint \$table) {
            \$table->string("email")->primary();
            \$table->string("token");
            \$table->timestamp("created_at")->nullable();
        });

        Schema::create("sessions", function (Blueprint \$table) {
            \$table->string("id")->primary();
            \$table->foreignId("user_id")->nullable()->index();
            \$table->string("ip_address", 45)->nullable();
            \$table->text("user_agent")->nullable();
            \$table->longText("payload");
            \$table->integer("last_activity")->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("sessions");
        Schema::dropIfExists("password_reset_tokens");
        Schema::dropIfExists("users");
    }
};
PHP'
```

- [ ] **Step 3: Run the migration**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan migrate --force 2>&1 | tail -10'
```

Expected: `Migration ran` lines for users, password_reset_tokens, sessions.

- [ ] **Step 4: Verify table exists with correct columns**

```bash
ssh menudirect 'mysql menudirect -e "DESCRIBE users;"'
```

Expected: id, name, email, email_verified_at, password, is_admin, two_factor_*, remember_token, created_at, updated_at.

- [ ] **Step 5: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add database/migrations/0001_01_01_000000_create_users_table.php && git commit -m "feat(db): users table for MenuDirect owner login

Schema mirrors the subset of sos_portal.clients needed for auth.
Preserves clients.id as users.id so restaurant_sites.client_id FK
remains valid after seed-users copies records over."'
```

---

### Task A4: `RestaurantModel` base class

**Files:**
- Create: `/var/www/app/app/Models/RestaurantModel.php`

- [ ] **Step 1: Create the base class**

```bash
ssh menudirect 'cat > /var/www/app/app/Models/RestaurantModel.php <<'\''PHP'\''
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class RestaurantModel extends Model
{
    /**
     * All restaurant-domain models use the menudirect connection.
     *
     * During test phase this is the cross-host portal-host DB; at cutover
     * (Phase E) the MENUDIRECT_DB_HOST env var flips to 127.0.0.1 and
     * Eloquent transparently switches to local without any model code change.
     */
    protected $connection = "menudirect";
}
PHP'
```

- [ ] **Step 2: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Models/RestaurantModel.php && git commit -m "feat: RestaurantModel base class pinning connection to menudirect

Single edit point for all 16 restaurant models — change the \$connection
property here only. Env-driven host means cutover requires no model code
changes."'
```

---

### Task A5: `menudirect:seed-users` artisan command

**Files:**
- Create: `/var/www/app/app/Console/Commands/SeedUsersFromClients.php`

- [ ] **Step 1: Generate the command via artisan**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan make:command SeedUsersFromClients 2>&1 | tail -3'
```

- [ ] **Step 2: Replace its contents**

```bash
ssh menudirect 'cat > /var/www/app/app/Console/Commands/SeedUsersFromClients.php <<'\''PHP'\''
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedUsersFromClients extends Command
{
    protected $signature = "menudirect:seed-users {--dry-run : Show what would change without writing}";
    protected $description = "Upsert MenuDirect users from SOS Tech clients (those who own restaurants, plus admins)";

    public function handle(): int
    {
        $dryRun = $this->option("dry-run");
        $this->info($dryRun ? "DRY RUN — no writes" : "Seeding users…");

        // Step 1: get distinct client_ids that own restaurants (from menudirect connection)
        $ownerIds = DB::connection("menudirect")
            ->table("restaurant_sites")
            ->whereNotNull("client_id")
            ->distinct()
            ->pluck("client_id")
            ->toArray();

        // Step 2: pull those clients + all admins from sostech_clients connection.
        // Two separate connections — no cross-host JOIN — works in both test and
        // post-cutover topologies.
        $clients = DB::connection("sostech_clients")
            ->table("clients")
            ->where(function ($q) use ($ownerIds) {
                $q->whereIn("id", $ownerIds)->orWhere("is_admin", 1);
            })
            ->get([
                "id", "name", "email", "email_verified_at", "password",
                "is_admin", "two_factor_secret", "two_factor_recovery_codes",
                "two_factor_confirmed_at", "created_at", "updated_at",
            ]);

        $created = $updated = $unchanged = 0;

        foreach ($clients as $c) {
            $existing = DB::table("users")->where("id", $c->id)->first();

            $payload = [
                "id" => $c->id,
                "name" => $c->name,
                "email" => $c->email,
                "email_verified_at" => $c->email_verified_at,
                "password" => $c->password,
                "is_admin" => (bool) $c->is_admin,
                "two_factor_secret" => $c->two_factor_secret,
                "two_factor_recovery_codes" => $c->two_factor_recovery_codes,
                "two_factor_confirmed_at" => $c->two_factor_confirmed_at,
                "created_at" => $c->created_at,
                "updated_at" => $c->updated_at,
            ];

            if (!$existing) {
                if (!$dryRun) DB::table("users")->insert($payload);
                $created++;
                $this->line("  + {$c->email} (id={$c->id})");
            } elseif ($this->differs((array) $existing, $payload)) {
                if (!$dryRun) DB::table("users")->where("id", $c->id)->update($payload);
                $updated++;
                $this->line("  ~ {$c->email} (id={$c->id})");
            } else {
                $unchanged++;
            }
        }

        $this->info("");
        $this->info("Summary: created={$created}, updated={$updated}, unchanged={$unchanged}, total=" . count($clients));

        return self::SUCCESS;
    }

    protected function differs(array $existing, array $new): bool
    {
        foreach (["name","email","password","is_admin","two_factor_secret","two_factor_recovery_codes","two_factor_confirmed_at"] as $k) {
            if (($existing[$k] ?? null) != ($new[$k] ?? null)) return true;
        }
        return false;
    }
}
PHP'
```

- [ ] **Step 3: Run dry-run to see what would happen**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan menudirect:seed-users --dry-run 2>&1 | tail -30'
```

Expected: lines starting with `+` (new) for each restaurant owner + admins, finishing with `Summary: created=N, updated=0, unchanged=0`.

- [ ] **Step 4: Run for real**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan menudirect:seed-users 2>&1 | tail -30'
```

Expected: same N, now created.

- [ ] **Step 5: Verify in DB**

```bash
ssh menudirect 'mysql menudirect -e "SELECT id, name, email, is_admin FROM users ORDER BY id;"'
```

Expected: rows including Frank (id 2 likely, is_admin=1) and Suwanna and any demo owners. Password hashes are present but not displayed.

- [ ] **Step 6: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Console/Commands/SeedUsersFromClients.php && git commit -m "feat: menudirect:seed-users command

Upserts restaurant-owning SOS Tech clients (plus admins) into local users
table. Idempotent — safe to re-run during test phase and at cutover."'
```

---

## Phase B — Port restaurant code from portal

### Task B1: Port all 16 restaurant models

**Files:**
- Create: 16 files under `/var/www/app/app/Models/` (see File Structure above)

- [ ] **Step 1: Copy model files from portal-host to new VM**

Run from portal-host:

```bash
for m in RestaurantSite MenuItem MenuCategory FoodOrder FoodOrderItem Order \
         OrderNotification OrderAuditLog Reservation RestaurantLead \
         RestaurantStaff RestaurantPlan RestaurantCustomDomain Announcement \
         LeadActivity LeadEmailTrack DemoSession; do
  scp -q /var/www/portal/app/Models/${m}.php menudirect:/var/www/app/app/Models/${m}.php
done
ssh menudirect 'chown www-data:www-data /var/www/app/app/Models/*.php && ls /var/www/app/app/Models/'
```

Expected: all 17 files (16 + RestaurantModel.php) listed.

- [ ] **Step 2: Replace `extends Model` with `extends RestaurantModel`**

```bash
ssh menudirect 'cd /var/www/app/app/Models && \
  for f in RestaurantSite.php MenuItem.php MenuCategory.php FoodOrder.php FoodOrderItem.php Order.php OrderNotification.php OrderAuditLog.php Reservation.php RestaurantLead.php RestaurantStaff.php RestaurantPlan.php RestaurantCustomDomain.php Announcement.php LeadActivity.php LeadEmailTrack.php DemoSession.php; do
    sed -i "s|^use Illuminate\\\\Database\\\\Eloquent\\\\Model;|use App\\\\Models\\\\RestaurantModel;|" "$f"
    sed -i "s|extends Model$|extends RestaurantModel|" "$f"
    sed -i "/protected \$connection = .menudirect.;/d" "$f"
  done'
```

This:
- Replaces `use Illuminate\Database\Eloquent\Model;` with `use App\Models\RestaurantModel;`
- Replaces `extends Model` with `extends RestaurantModel`
- Removes any inline `protected $connection = 'menudirect';` declarations (now handled by base class)

- [ ] **Step 3: Spot-check one model**

```bash
ssh menudirect 'head -20 /var/www/app/app/Models/RestaurantSite.php'
```

Expected: `use App\Models\RestaurantModel;` and `class RestaurantSite extends RestaurantModel`.

- [ ] **Step 4: Verify models load and query works**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan tinker --execute="echo \App\Models\RestaurantSite::count() . \" sites\n\"; echo \App\Models\RestaurantLead::count() . \" leads\n\"; echo \App\Models\MenuItem::count() . \" menu items\n\";" 2>&1 | grep -v "psysh"'
```

Expected: counts printed matching portal-host's data.

- [ ] **Step 5: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Models/ && git commit -m "feat: port 16 restaurant models from portal

All inherit from RestaurantModel base which pins the menudirect connection.
Tests via tinker confirm cross-host read works (4282 leads, 12+ sites)."'
```

---

### Task B2: Add `archived_at` to `restaurant_sites`

**Files:**
- Create: `/var/www/app/database/migrations/2026_05_16_130000_add_archived_at_to_restaurant_sites.php`

- [ ] **Step 1: Create the migration**

```bash
ssh menudirect 'cat > /var/www/app/database/migrations/2026_05_16_130000_add_archived_at_to_restaurant_sites.php <<'\''PHP'\''
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = "menudirect";

    public function up(): void
    {
        Schema::connection("menudirect")->table("restaurant_sites", function (Blueprint $table) {
            $table->timestamp("archived_at")->nullable()->after("updated_at")->index();
        });
    }

    public function down(): void
    {
        Schema::connection("menudirect")->table("restaurant_sites", function (Blueprint $table) {
            $table->dropColumn("archived_at");
        });
    }
};
PHP'
```

- [ ] **Step 2: Run the migration**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan migrate --path=database/migrations/2026_05_16_130000_add_archived_at_to_restaurant_sites.php --force 2>&1 | tail -3'
```

Note: this migration runs against the cross-host `menudirect` connection — it adds the column to portal-host's MySQL. That's fine; the change persists through the cutover dump+import.

- [ ] **Step 3: Verify**

```bash
ssh menudirect 'mysql -h <PORTAL_IP> -u menudirect_app -p<MD_PASS> menudirect -e "SHOW COLUMNS FROM restaurant_sites LIKE \"archived_at\";"'
```

Expected: one row showing `archived_at` as nullable timestamp.

- [ ] **Step 4: Add global scope on `RestaurantSite` model to filter archived**

Add to `/var/www/app/app/Models/RestaurantSite.php` after the namespace/use lines, inside the class body, near the top:

```php
    protected static function booted(): void
    {
        static::addGlobalScope("notArchived", function ($query) {
            $query->whereNull("archived_at");
        });
    }
```

Use Edit tool to add this. (Skip if RestaurantSite already has a `booted` method — merge instead.)

- [ ] **Step 5: Verify scope works**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan tinker --execute="echo \App\Models\RestaurantSite::count() . \" active\n\"; echo \App\Models\RestaurantSite::withoutGlobalScope(\"notArchived\")->count() . \" all\n\";" 2>&1 | grep -v psysh'
```

Expected: same number for both (no sites archived yet), but the global scope is in effect.

- [ ] **Step 6: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add database/migrations/ app/Models/RestaurantSite.php && git commit -m "feat(db): add restaurant_sites.archived_at + global scope

Enables soft-archiving of demo sites later without losing data."'
```

---

### Task B3: Port restaurant services

**Files:**
- Create: `/var/www/app/app/Services/ReservationService.php`
- Create: `/var/www/app/app/Services/DeliveryZoneService.php`
- (Skip Square/SquarePaymentService — not in use by any current restaurant)
- Modify: Stripe Connect bits from `StripeWebhookRelayController` will move with that controller in B4

- [ ] **Step 1: Copy services**

```bash
scp -q /var/www/portal/app/Services/ReservationService.php menudirect:/var/www/app/app/Services/
scp -q /var/www/portal/app/Services/DeliveryZoneService.php menudirect:/var/www/app/app/Services/
ssh menudirect 'chown www-data:www-data /var/www/app/app/Services/*.php && ls /var/www/app/app/Services/'
```

- [ ] **Step 2: Quick lint check**

```bash
ssh menudirect 'cd /var/www/app && php -l app/Services/ReservationService.php && php -l app/Services/DeliveryZoneService.php'
```

Expected: `No syntax errors detected` for both.

- [ ] **Step 3: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Services/ && git commit -m "feat: port ReservationService and DeliveryZoneService

Square service explicitly not ported — no current restaurant uses it."'
```

---

### Task B4: Port jobs and mailables

**Files:** 3 jobs + 7 mailables under `/var/www/app/app/Jobs/` and `/var/www/app/app/Mail/`

- [ ] **Step 1: Copy jobs and mailables**

```bash
for j in SendOrderNotificationsJob SendReservationNotificationsJob SendCateringInquiryNotificationsJob; do
  scp -q /var/www/portal/app/Jobs/${j}.php menudirect:/var/www/app/app/Jobs/
done

for m in NewFoodOrder OrderConfirmation OrderStatusUpdate NewReservation \
         ReservationConfirmation ReservationStatusUpdate RestaurantWelcome; do
  scp -q /var/www/portal/app/Mail/${m}.php menudirect:/var/www/app/app/Mail/
done

ssh menudirect 'chown www-data:www-data /var/www/app/app/Jobs/*.php /var/www/app/app/Mail/*.php && ls /var/www/app/app/Jobs/ && ls /var/www/app/app/Mail/'
```

- [ ] **Step 2: Copy any Blade views these mailables reference**

```bash
# These mailables typically reference views in resources/views/emails/
scp -qr /var/www/portal/resources/views/emails menudirect:/var/www/app/resources/views/
ssh menudirect 'chown -R www-data:www-data /var/www/app/resources/views/emails'
```

- [ ] **Step 3: Lint all moved files**

```bash
ssh menudirect 'cd /var/www/app && for f in app/Jobs/*.php app/Mail/*.php; do php -l "$f" || exit 1; done | tail -20'
```

Expected: `No syntax errors detected in <each file>`.

- [ ] **Step 4: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Jobs/ app/Mail/ resources/views/emails && git commit -m "feat: port 3 jobs + 7 mailables + email templates"'
```

---

### Task B5: Port owner portal controllers

**Files:** 8 controllers under `/var/www/app/app/Http/Controllers/Client/`

- [ ] **Step 1: Copy controllers**

```bash
ssh menudirect 'mkdir -p /var/www/app/app/Http/Controllers/Client'
for c in RestaurantSiteController RestaurantMenuController RestaurantOrdersController \
         RestaurantReservationsController RestaurantStaffController \
         RestaurantPaymentsController RestaurantCateringController \
         RestaurantAnnouncementController; do
  scp -q /var/www/portal/app/Http/Controllers/Client/${c}.php menudirect:/var/www/app/app/Http/Controllers/Client/
done
ssh menudirect 'chown -R www-data:www-data /var/www/app/app/Http/Controllers/Client && ls /var/www/app/app/Http/Controllers/Client/'
```

- [ ] **Step 2: Adjust namespaces (already `App\Http\Controllers\Client` — no change needed) and verify**

```bash
ssh menudirect 'head -5 /var/www/app/app/Http/Controllers/Client/RestaurantSiteController.php'
```

Expected: `namespace App\Http\Controllers\Client;`.

- [ ] **Step 3: Lint check**

```bash
ssh menudirect 'cd /var/www/app && for f in app/Http/Controllers/Client/*.php; do php -l "$f" || exit 1; done | tail -10'
```

- [ ] **Step 4: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Http/Controllers/Client/ && git commit -m "feat: port 8 owner-portal restaurant controllers"'
```

---

### Task B6: Port admin and staff controllers

**Files:** 5 admin + 3 staff controllers

- [ ] **Step 1: Copy admin controllers**

```bash
ssh menudirect 'mkdir -p /var/www/app/app/Http/Controllers/Admin /var/www/app/app/Http/Controllers/Staff'
for c in RestaurantSitesController RestaurantLeadsController OrdersController DomainsController; do
  scp -q /var/www/portal/app/Http/Controllers/Admin/${c}.php menudirect:/var/www/app/app/Http/Controllers/Admin/
done
# Note: DomainsController has BOTH SOS Tech domain mgmt AND restaurant custom-domain handling.
# Take it as-is for now; we'll prune SOS Tech-specific methods in Phase F cleanup.
for c in StaffAuthController StaffDashboardController StaffOrdersController; do
  scp -q /var/www/portal/app/Http/Controllers/Staff/${c}.php menudirect:/var/www/app/app/Http/Controllers/Staff/
done
ssh menudirect 'chown -R www-data:www-data /var/www/app/app/Http/Controllers/Admin /var/www/app/app/Http/Controllers/Staff'
```

- [ ] **Step 2: Lint check both directories**

```bash
ssh menudirect 'cd /var/www/app && for f in app/Http/Controllers/Admin/*.php app/Http/Controllers/Staff/*.php; do php -l "$f" || exit 1; done | tail -10'
```

- [ ] **Step 3: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Http/Controllers/Admin app/Http/Controllers/Staff && git commit -m "feat: port admin + staff controllers"'
```

---

### Task B7: Port API controllers

**Files:** ~12 controllers under `/var/www/app/app/Http/Controllers/Api/`

- [ ] **Step 1: Copy API controllers**

```bash
ssh menudirect 'mkdir -p /var/www/app/app/Http/Controllers/Api'
for c in RestaurantApiController FoodOrderApiController StaffOrdersApiController \
         ReservationApiController CateringApiController DemoController \
         DemoKitchenController MenudirectLeadController StripeWebhookRelayController \
         DomainCheckController; do
  scp -q /var/www/portal/app/Http/Controllers/Api/${c}.php menudirect:/var/www/app/app/Http/Controllers/Api/
done
ssh menudirect 'chown -R www-data:www-data /var/www/app/app/Http/Controllers/Api && ls /var/www/app/app/Http/Controllers/Api/'
```

- [ ] **Step 2: Lint check**

```bash
ssh menudirect 'cd /var/www/app && for f in app/Http/Controllers/Api/*.php; do php -l "$f" || exit 1; done | tail -15'
```

- [ ] **Step 3: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Http/Controllers/Api && git commit -m "feat: port restaurant API controllers"'
```

---

### Task B8: Port owner portal + admin views

**Files:** ~15 owner views + admin views

- [ ] **Step 1: Copy view directories**

```bash
ssh menudirect 'mkdir -p /var/www/app/resources/views/client /var/www/app/resources/views/admin'
scp -qr /var/www/portal/resources/views/client/restaurant menudirect:/var/www/app/resources/views/client/
scp -qr /var/www/portal/resources/views/admin/restaurant menudirect:/var/www/app/resources/views/admin/
scp -qr /var/www/portal/resources/views/layouts menudirect:/var/www/app/resources/views/ 2>/dev/null || echo "(layouts may not exist on portal — skip if so)"
ssh menudirect 'chown -R www-data:www-data /var/www/app/resources/views/ && ls /var/www/app/resources/views/client/restaurant/ | head'
```

- [ ] **Step 2: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add resources/views/ && git commit -m "feat: port owner portal + admin restaurant Blade views"'
```

---

### Task B9: Port restaurant routes into routes/web.php and routes/api.php

**Files:**
- Modify: `/var/www/app/routes/web.php`
- Modify: `/var/www/app/routes/api.php`

- [ ] **Step 1: Extract restaurant routes from portal `routes/web.php`**

Inspect portal's restaurant route blocks (typically under `Route::middleware('auth')->prefix('client')` for owner portal, under `Route::middleware('admin')` for admin, plus standalone routes for restaurant frontends). Manually copy these blocks into `/var/www/app/routes/web.php`.

**Action:** SSH into portal-host, identify the route blocks:

```bash
grep -n -B1 -A30 "restaurant\|Restaurant\|food-order\|reservation" /var/www/portal/routes/web.php | head -100
```

Then copy the relevant blocks into `/var/www/app/routes/web.php` (in the existing file's appropriate section — after `Route::get('/', ...)` for owner portal, etc.).

- [ ] **Step 2: Same exercise for `routes/api.php`**

```bash
grep -n -B1 -A20 "restaurant\|Restaurant\|food-order\|reservation\|demo-kitchen\|menudirect" /var/www/portal/routes/api.php | head -120
```

Copy the restaurant API route blocks into `/var/www/app/routes/api.php`.

- [ ] **Step 3: Run route:list to verify**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan route:list --path=restaurant 2>&1 | tail -30'
```

Expected: routes listed; no controller-not-found errors.

- [ ] **Step 4: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add routes/ && git commit -m "feat: port restaurant routes (web + api)"'
```

---

## Phase C — Port public-facing code from sos-tech

### Task C1: Port public-site controllers and Turnstile

**Files:**
- Create: `/var/www/app/app/Http/Controllers/PublicSite/SampleSiteController.php`
- Create: `/var/www/app/app/Http/Controllers/Public/MenuDirectController.php`
- Create: `/var/www/app/app/Services/TurnstileVerifier.php`

- [ ] **Step 1: Copy and adjust namespaces**

```bash
ssh menudirect 'mkdir -p /var/www/app/app/Http/Controllers/PublicSite /var/www/app/app/Http/Controllers/Public'
scp -q /var/www/sos-tech/app/Http/Controllers/SampleSiteController.php menudirect:/var/www/app/app/Http/Controllers/PublicSite/SampleSiteController.php
scp -q /var/www/sos-tech/app/Http/Controllers/MenuDirectController.php menudirect:/var/www/app/app/Http/Controllers/Public/MenuDirectController.php
scp -q /var/www/sos-tech/app/Services/TurnstileVerifier.php menudirect:/var/www/app/app/Services/TurnstileVerifier.php

# Adjust namespaces:
ssh menudirect '
  sed -i "s|^namespace App\\\\Http\\\\Controllers;|namespace App\\\\Http\\\\Controllers\\\\PublicSite;|" /var/www/app/app/Http/Controllers/PublicSite/SampleSiteController.php
  sed -i "s|^namespace App\\\\Http\\\\Controllers;|namespace App\\\\Http\\\\Controllers\\\\Public;|" /var/www/app/app/Http/Controllers/Public/MenuDirectController.php
  # SampleSiteController extends Controller — add a use statement:
  sed -i "/^namespace App\\\\Http\\\\Controllers\\\\PublicSite;/a\\nuse App\\\\Http\\\\Controllers\\\\Controller;" /var/www/app/app/Http/Controllers/PublicSite/SampleSiteController.php
  sed -i "/^namespace App\\\\Http\\\\Controllers\\\\Public;/a\\nuse App\\\\Http\\\\Controllers\\\\Controller;" /var/www/app/app/Http/Controllers/Public/MenuDirectController.php
  chown -R www-data:www-data /var/www/app/app/Http/Controllers /var/www/app/app/Services'
```

- [ ] **Step 2: Important — update `SampleSiteController` to use local models instead of Portal API**

This is the **big change** for C1. The original `SampleSiteController` calls Portal's API (`GET /api/restaurant/{slug}`) to fetch data. On the new VM, it should query local Eloquent models directly:

Replace the HTTP calls with Eloquent queries. Specifically, in `SampleSiteController.php`, find any block matching:

```php
$response = Http::get("{$portalUrl}/api/restaurant/{$slug}");
```

And replace with:

```php
$site = \App\Models\RestaurantSite::with(["menuItems", "menuCategories"])
    ->where("slug", $slug)
    ->firstOrFail();
```

Use the Read+Edit tools to walk the file and convert each API call. Use `RestaurantSite::query()->where("custom_domain", $domain)` for domain lookups. The data shape is the same.

- [ ] **Step 3: Update `MenuDirectController` lead form**

The portal API call inside `MenuDirectController::submitLead()` (the `Http::post()` block) is no longer needed — on the new VM, the controller writes directly to the `RestaurantLead` model:

```php
use App\Models\RestaurantLead;

// Replace the Http::post() block:
RestaurantLead::create([
    "source" => "web_form",
    "business_name" => strip_tags($validated["restaurant_name"]),
    "owner_name" => strip_tags($validated["contact_name"]),
    "email" => $validated["email"],
    "phone" => $validated["phone"] ?? null,
    "notes" => isset($validated["message"]) ? strip_tags($validated["message"]) : null,
    "status" => "new",
    "priority" => "medium",
    "tags" => ["menudirect.ca", "ip:" . $request->ip()],
]);
```

- [ ] **Step 4: Lint check**

```bash
ssh menudirect 'cd /var/www/app && for f in app/Http/Controllers/PublicSite/*.php app/Http/Controllers/Public/*.php app/Services/TurnstileVerifier.php; do php -l "$f" || exit 1; done'
```

- [ ] **Step 5: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add app/Http/Controllers/ app/Services/ && git commit -m "feat: port public-site + lead controllers from sos-tech

SampleSiteController: replaced cross-host Portal API calls with local
Eloquent queries. MenuDirectController: writes directly to RestaurantLead
instead of POSTing to Portal API. TurnstileVerifier copied as-is."'
```

---

### Task C2: Port template directories and public assets

**Files:** 19 template subdirs + image assets

- [ ] **Step 1: Copy sample templates and partials**

```bash
ssh menudirect 'mkdir -p /var/www/app/resources/views/public-site'
scp -qr /var/www/sos-tech/resources/views/samples/* menudirect:/var/www/app/resources/views/public-site/
scp -qr /var/www/sos-tech/resources/views/menudirect menudirect:/var/www/app/resources/views/
ssh menudirect 'chown -R www-data:www-data /var/www/app/resources/views/public-site /var/www/app/resources/views/menudirect && ls /var/www/app/resources/views/public-site/ | head -20'
```

Expected: 19 template directories (bistro, coastal, etc.) plus partials and layout.

- [ ] **Step 2: Copy public image assets**

```bash
ssh menudirect 'mkdir -p /var/www/app/public/images'
scp -qr /var/www/sos-tech/public/images/templates menudirect:/var/www/app/public/images/
scp -qr /var/www/sos-tech/public/images/template-previews menudirect:/var/www/app/public/images/
scp -qr /var/www/sos-tech/public/images/menudirect menudirect:/var/www/app/public/images/
ssh menudirect 'chown -R www-data:www-data /var/www/app/public/images && du -sh /var/www/app/public/images/*'
```

Expected: three subdirectories totaling some MBs.

- [ ] **Step 3: Update view references in SampleSiteController**

Original sos-tech `SampleSiteController` references views like `samples.bistro.show`. After the move, they're at `public-site.bistro.show`. Use sed:

```bash
ssh menudirect 'sed -i "s|samples\\.|public-site.|g" /var/www/app/app/Http/Controllers/PublicSite/SampleSiteController.php'
```

- [ ] **Step 4: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add resources/views/ public/images/ app/Http/Controllers/PublicSite/SampleSiteController.php && git commit -m "feat: port public-site templates (19 designs) and image assets

Views moved from samples/ to public-site/ for clarity. SampleSiteController
updated to reference the new path. Image assets copied verbatim."'
```

---

### Task C3: Port public-site routes (wildcard + apex + fallback)

**Files:**
- Modify: `/var/www/app/routes/web.php`

- [ ] **Step 1: Add routes to `routes/web.php`**

Append the following at the appropriate place in `/var/www/app/routes/web.php`. Use the Edit tool. Insert BEFORE any final catch-all:

```php
// Marketing apex
Route::domain("menudirect.ca")->group(function () {
    Route::view("/", "menudirect.landing")->name("menudirect.home");
    Route::post("/lead", [\App\Http\Controllers\Public\MenuDirectController::class, "submitLead"])->name("menudirect.lead");
    Route::post("/try-demo", [\App\Http\Controllers\Public\MenuDirectController::class, "createDemo"])->name("menudirect.try-demo");
});

// Restaurant subdomains
Route::domain("{slug}.menudirect.ca")->group(function () {
    Route::get("/", [\App\Http\Controllers\PublicSite\SampleSiteController::class, "show"])->name("public.show");
    Route::get("/menu", [\App\Http\Controllers\PublicSite\SampleSiteController::class, "menu"])->name("public.menu");
    // Add any other public-site routes from sos-tech's web.php here.
});

// Custom domain fallback — resolve via RestaurantCustomDomain model
Route::fallback([\App\Http\Controllers\PublicSite\SampleSiteController::class, "showByDomain"]);
```

- [ ] **Step 2: Verify routes registered**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan route:list 2>&1 | grep -E "menudirect|public" | head -20'
```

Expected: marketing apex routes + subdomain routes + fallback present.

- [ ] **Step 3: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add routes/web.php && git commit -m "feat: register public-site routes (apex + subdomain + custom domain fallback)"'
```

---

### Task C4: Port lead intake API endpoint

The portal currently hosts `POST /api/menudirect/leads` (added in our 2026-05-15 hardening work). It needs to move to the new VM since `RestaurantLead` writes will land in the new VM's local DB.

**Files:**
- Modify: `/var/www/app/routes/api.php`
- Modify: `/var/www/app/config/services.php`
- Verify: `/var/www/app/app/Http/Controllers/Api/MenudirectLeadController.php` (already ported in B7)

- [ ] **Step 1: Add route to `routes/api.php`**

Append:

```php
// MenuDirect lead intake — bearer-token authenticated; called by sos-tech.ca (transition)
// and by self after Phase E (when marketing apex is hosted here)
Route::post("/menudirect/leads", [\App\Http\Controllers\Api\MenudirectLeadController::class, "store"])
    ->middleware("throttle:30,1")
    ->name("api.menudirect.leads.store");
```

- [ ] **Step 2: Add config entry**

In `/var/www/app/config/services.php`, add:

```php
    "menudirect" => [
        "intake_token" => env("MENUDIRECT_INTAKE_TOKEN"),
    ],
    "turnstile" => [
        "site_key" => env("TURNSTILE_SITE_KEY"),
        "secret_key" => env("TURNSTILE_SECRET_KEY"),
    ],
```

- [ ] **Step 3: Add to `.env` — reuse existing token from sos-tech**

```bash
PORTAL_TOKEN=$(grep "^MENUDIRECT_INTAKE_TOKEN=" /var/www/portal/.env | cut -d= -f2-)
ssh menudirect "echo 'MENUDIRECT_INTAKE_TOKEN=$PORTAL_TOKEN' >> /var/www/app/.env"
ssh menudirect "echo '# Turnstile (placeholders — fill once Cloudflare site is set up)' >> /var/www/app/.env"
ssh menudirect "echo '# TURNSTILE_SITE_KEY=' >> /var/www/app/.env"
ssh menudirect "echo '# TURNSTILE_SECRET_KEY=' >> /var/www/app/.env"
```

- [ ] **Step 4: Cache rebuild + smoke test**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan optimize && \
  echo "=== test with curl ===" && \
  curl -sS -o /dev/stdout -w "\nHTTP %{http_code}\n" -X POST http://127.0.0.1/api/menudirect/leads \
    -H "Authorization: Bearer '"$PORTAL_TOKEN"'" \
    -H "Accept: application/json" -H "Content-Type: application/json" \
    -d "{\"restaurant_name\":\"Plan Test\",\"contact_name\":\"Tester\",\"email\":\"plan@test.com\",\"submitter_ip\":\"127.0.0.1\"}"'
```

Expected: `HTTP 201` and `{"ok":true,"lead_id":...}`.

- [ ] **Step 5: Clean up the test row**

```bash
ssh menudirect 'mysql -h <PORTAL_IP> -u menudirect_app -p<MD_PASS> menudirect -e "DELETE FROM restaurant_leads WHERE email = \"plan@test.com\";"'
```

- [ ] **Step 6: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add routes/api.php config/services.php && git commit -m "feat: port menudirect lead intake API endpoint

Same bearer-token shared with sos-tech (transition phase). After cutover,
this endpoint serves both the marketing apex form (hosted here) and any
external integrations."'
```

---

### Task C5: Update sos-tech to POST to new VM for leads

**Files:**
- Modify: `/var/www/sos-tech/.env`

This is a one-line change that flips sos-tech's lead form POSTs to land on the new VM immediately (per spec Section 2A — "lead intake API moves immediately, one less thing to flip at cutover").

- [ ] **Step 1: Update sos-tech's portal URL config to point at new VM**

```bash
# On portal-host:
sed -i 's|^PORTAL_API_URL=.*|PORTAL_API_URL=http://192.168.23.65|' /var/www/sos-tech/.env
grep "^PORTAL_API_URL=" /var/www/sos-tech/.env
```

Expected: `PORTAL_API_URL=http://192.168.23.65`.

- [ ] **Step 2: Rebuild sos-tech config**

```bash
cd /var/www/sos-tech && php artisan config:clear && php artisan config:cache
```

- [ ] **Step 3: Submit a test lead via sos-tech (it should land on new VM)**

```bash
# Inspect new VM's lead count before:
BEFORE=$(ssh menudirect 'mysql -h <PORTAL_IP> -u menudirect_app -p<MD_PASS> menudirect -sN -e "SELECT COUNT(*) FROM restaurant_leads;"')
echo "Before: $BEFORE"

# Submit via sos-tech using its actual production URL (uses real flow):
curl -sS -X POST "https://menudirect.ca/lead" \
  -H "Accept: text/html" \
  --data-urlencode "_token=<get-csrf-from-GET-/-first>" \
  --data-urlencode "restaurant_name=Plan e2e test" \
  --data-urlencode "contact_name=Plan Tester" \
  --data-urlencode "email=planE2E@test.com" \
  --data-urlencode "_form_token=$(php -r 'echo base64_encode(time()-5);')" \
  -L > /dev/null

AFTER=$(ssh menudirect 'mysql -h <PORTAL_IP> -u menudirect_app -p<MD_PASS> menudirect -sN -e "SELECT COUNT(*) FROM restaurant_leads;"')
echo "After: $AFTER"
# Expected: AFTER = BEFORE + 1
```

If the flow doesn't increment, troubleshoot before continuing.

- [ ] **Step 4: Clean up test row**

```bash
ssh menudirect 'mysql -h <PORTAL_IP> -u menudirect_app -p<MD_PASS> menudirect -e "DELETE FROM restaurant_leads WHERE email = \"planE2E@test.com\";"'
```

- [ ] **Step 5: Commit on portal-host side** (this is a non-restaurant change, just a config flip)

```bash
cd /var/www/sos-tech && git add .env && git commit -m "config(menudirect): point lead intake to new VM (192.168.23.65)

Per migration spec Section 2A — lead API moves immediately to reduce
cutover steps."
```

(If sos-tech doesn't track .env in git, just note the change in your operational log.)

---

## Phase D — Test phase validation

### Task D1: Smoke-test the new VM via Host-header tricks

**Files:** None (operational verification)

- [ ] **Step 1: Hit each major route**

```bash
echo "=== marketing apex ==="
curl -sS -o /dev/null -w "%{http_code}\n" -H "Host: menudirect.ca" http://192.168.23.65/

echo "=== marketing apex lead form GET ==="
curl -sS -H "Host: menudirect.ca" http://192.168.23.65/ | grep -E "honeypot|_form_token|menudirect.lead" | head -3

echo "=== owner portal login ==="
curl -sS -o /dev/null -w "%{http_code}\n" -H "Host: portal.menudirect.ca" http://192.168.23.65/login

echo "=== sample restaurant subdomain ==="
SAMPLE_SLUG=$(ssh menudirect 'mysql -h <PORTAL_IP> -u menudirect_app -p<MD_PASS> menudirect -sN -e "SELECT slug FROM restaurant_sites LIMIT 1;"')
curl -sS -o /dev/null -w "%{http_code}\n" -H "Host: ${SAMPLE_SLUG}.menudirect.ca" http://192.168.23.65/
```

Expected: all four return `200`. If any returns 5xx, investigate logs at `/var/www/app/storage/logs/laravel.log`.

- [ ] **Step 2: Tail logs while testing**

```bash
ssh menudirect 'tail -n 100 /var/www/app/storage/logs/laravel.log'
```

Expected: no ERROR entries during the smoke tests.

---

### Task D2: Validate owner login flow end-to-end

**Files:** None (operational test)

- [ ] **Step 1: Verify Suwanna's user record exists**

```bash
ssh menudirect 'mysql menudirect -e "SELECT id, name, email, is_admin FROM users WHERE email LIKE \"%suwanna%\" OR id = 2;"'
```

Expected: Suwanna's row + Frank's admin row.

- [ ] **Step 2: Attempt login programmatically**

```bash
# Get CSRF from /login:
COOKIES=$(mktemp)
TOKEN=$(curl -sS -c $COOKIES -b $COOKIES -H "Host: portal.menudirect.ca" http://192.168.23.65/login | grep -oP 'name="_token" value="\K[^"]+')

# Post credentials (use Frank's known login on portal):
curl -sS -c $COOKIES -b $COOKIES -H "Host: portal.menudirect.ca" \
  -X POST http://192.168.23.65/login \
  -d "_token=$TOKEN&email=frankkahle@gmail.com&password=<your-portal-password>" \
  -L -o /dev/null -w "HTTP %{http_code}\nFinal URL: %{url_effective}\n"
rm $COOKIES
```

Expected: HTTP 200 (or 302 redirect to dashboard). If 422 (validation fail) the password didn't match — check `users.password` hash matches `clients.password`.

---

### Task D3: Validate public restaurant site rendering

**Files:** None

- [ ] **Step 1: Pull each live restaurant subdomain via Host header**

```bash
ssh menudirect 'mysql menudirect -sN -e "SELECT slug FROM restaurant_sites WHERE archived_at IS NULL;"' | while read SLUG; do
  CODE=$(curl -sS -o /dev/null -w "%{http_code}" -H "Host: ${SLUG}.menudirect.ca" http://192.168.23.65/)
  echo "$SLUG -> $CODE"
done
```

Expected: all return `200`.

- [ ] **Step 2: Spot-check page content for one site (Suwanna's)**

```bash
curl -sS -H "Host: suwanna.menudirect.ca" http://192.168.23.65/ | grep -E "<title>|<h1>" | head -5
```

Expected: title and headings render with Suwanna's actual restaurant name (not "MenuDirect" or default).

---

### Task D4: Validate order placement flow

**Files:** None

- [ ] **Step 1: Submit a test order via API on the new VM**

```bash
# Place a test order on Suwanna's site (or any active site)
SAMPLE_SLUG="suwanna"  # adjust to actual live slug
ssh menudirect "curl -sS -o /tmp/order.json -w 'HTTP %{http_code}\n' -X POST http://127.0.0.1/api/restaurant/${SAMPLE_SLUG}/orders \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{\"customer_name\":\"Plan Test\",\"customer_email\":\"plan-order@test.com\",\"customer_phone\":\"5065550100\",\"items\":[{\"menu_item_id\":1,\"quantity\":1}],\"order_type\":\"pickup\"}'"
ssh menudirect 'cat /tmp/order.json | python3 -m json.tool | head -10'
```

Expected: HTTP 200/201 with order token returned. If 422 (validation), tweak the payload to match the actual API contract.

- [ ] **Step 2: Verify order landed in DB**

```bash
ssh menudirect 'mysql menudirect -e "SELECT id, customer_name, order_type, status, created_at FROM food_orders WHERE customer_email = \"plan-order@test.com\";"'
```

Expected: one row.

- [ ] **Step 3: Verify queue job ran (notification email)**

```bash
ssh menudirect 'journalctl -u menudirect-queue -n 20 --no-pager | tail -10'
```

Expected: a SendOrderNotificationsJob processed entry.

- [ ] **Step 4: Clean up the test order**

```bash
ssh menudirect 'mysql menudirect -e "DELETE FROM food_orders WHERE customer_email = \"plan-order@test.com\";"'
```

---

### Task D5: Validate `seed-users` is current

**Files:** None

- [ ] **Step 1: Final seed-users dry run before cutover prep**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan menudirect:seed-users --dry-run | tail -10'
```

Expected: `Summary: created=0, updated=0, unchanged=N` — no drift since initial seed.

- [ ] **Step 2: If any changes, run for real**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan menudirect:seed-users | tail -5'
```

---

## Phase E — Cutover

### Task E1: T-1 day prep

**Files:**
- Create: portal-host branch `cutover/disable-restaurant-routes`

- [ ] **Step 1: Take a full portal-host MySQL backup**

```bash
mysqldump --all-databases | gzip > /backups/portal-host-pre-cutover-$(date +%Y%m%d).sql.gz
ls -lh /backups/portal-host-pre-cutover-*.sql.gz
```

- [ ] **Step 2: On portal-host, create the route-freeze branch**

```bash
cd /var/www/portal
git checkout -b cutover/disable-restaurant-routes
# Comment out / remove restaurant route blocks in routes/web.php and routes/api.php
# (Identify with: grep -n -B1 -A1 "restaurant\|Restaurant\|food-order\|reservation\|demo-kitchen" routes/web.php routes/api.php)
# Use Edit tool to wrap them in `if (false) { ... }` or simply comment.
git add routes/
git commit -m "cutover: disable restaurant routes during MenuDirect VM migration

To be merged at T-0 just before the dump. Reverts cleanly to main if rollback needed."
git checkout main
# Don't merge yet — just have the branch ready.
```

- [ ] **Step 3: Final pre-cutover `seed-users`**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan menudirect:seed-users | tail -5'
```

- [ ] **Step 4: Confirm HAProxy config draft is ready**

Frank confirms manually that the new HAProxy backend definitions for menudirect.ca, portal.menudirect.ca, *.menudirect.ca pointing at 192.168.23.65:80 are written and ready to activate (not activated yet).

---

### Task E2: T-0 cutover sequence (~5–10 min downtime)

This is the critical path. Execute in order; do NOT proceed to Step N+1 if Step N's expected output didn't appear.

- [ ] **Step 1: Quiesce portal restaurant writes**

```bash
cd /var/www/portal && git checkout cutover/disable-restaurant-routes && php artisan optimize
```

Expected: optimize output, no errors.

- [ ] **Step 2: Drain queues**

```bash
cd /var/www/portal && php artisan queue:work --stop-when-empty
ssh menudirect 'systemctl stop menudirect-queue && cd /var/www/app && sudo -u www-data php artisan queue:work --stop-when-empty'
```

Expected: queues drain, no pending jobs.

- [ ] **Step 3: Final dump on portal-host**

```bash
DUMPFILE=/tmp/menudirect-cutover-$(date +%Y%m%d-%H%M).sql.gz
mysqldump --single-transaction --routines --triggers --skip-lock-tables menudirect | gzip > $DUMPFILE
ls -lh $DUMPFILE
```

Expected: file <50MB.

- [ ] **Step 4: Transport and import on new VM**

```bash
scp $DUMPFILE menudirect:/tmp/
ssh menudirect "cd /tmp && gunzip -c $(basename $DUMPFILE) | mysql menudirect && mysql menudirect -e 'SELECT COUNT(*) AS sites FROM restaurant_sites; SELECT COUNT(*) AS leads FROM restaurant_leads;'"
```

Expected: counts match portal-host's pre-dump values.

- [ ] **Step 5: Flip new VM's menudirect connection to local**

```bash
LOCAL_PASS=$(ssh menudirect 'cat /root/.menudirect_db_pass')
ssh menudirect "cd /var/www/app && \
  sed -i 's|^MENUDIRECT_DB_HOST=.*|MENUDIRECT_DB_HOST=127.0.0.1|' .env && \
  sed -i 's|^MENUDIRECT_DB_USERNAME=.*|MENUDIRECT_DB_USERNAME=menudirect|' .env && \
  sed -i 's|^MENUDIRECT_DB_PASSWORD=.*|MENUDIRECT_DB_PASSWORD=$LOCAL_PASS|' .env && \
  sudo -u www-data php artisan optimize && \
  systemctl restart php8.3-fpm menudirect-queue"
```

Expected: optimize succeeds; php-fpm + queue worker restart cleanly.

- [ ] **Step 6: Final seed-users pass**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan menudirect:seed-users | tail -5'
```

Expected: `Summary: created=0 or low, updated=0, unchanged=N`.

- [ ] **Step 7: Smoke test new VM via Host headers (no public traffic yet)**

```bash
for HOST in "menudirect.ca" "portal.menudirect.ca" "suwanna.menudirect.ca"; do
  CODE=$(curl -sS -o /dev/null -w "%{http_code}" -H "Host: $HOST" http://192.168.23.65/)
  echo "$HOST: $CODE"
done
```

Expected: all `200`. **If any failure, STOP — do not flip HAProxy. Diagnose first.**

- [ ] **Step 8: Frank flips HAProxy**

Frank manually updates HAProxy backend rules:
- `menudirect.ca` → 192.168.23.65:80
- `portal.menudirect.ca` → 192.168.23.65:80
- `*.menudirect.ca` → 192.168.23.65:80
- Custom restaurant domains → 192.168.23.65:80

Reload HAProxy.

- [ ] **Step 9: External smoke test (from off-LAN)**

From a laptop on cell network:

```
https://menudirect.ca/         → marketing apex renders
https://portal.menudirect.ca/login   → owner login page
https://suwanna.menudirect.ca/  → Suwanna's restaurant site
```

Log in as Suwanna. Place a real test order. Verify it appears in her dashboard.

- [ ] **Step 10: Watch logs for 10 min**

```bash
ssh menudirect 'tail -f /var/www/app/storage/logs/laravel.log'
# In another shell:
ssh menudirect 'journalctl -u menudirect-queue -f'
```

Watch for 500s, queue failures, DB errors. If quiet → cutover complete. Mark Definition-of-Done items in the spec.

---

## Phase F — Post-cutover cleanup (T+7)

Wait at least 7 days after a successful cutover. Sanity check daily that no surprises emerged.

### Task F1: Revoke cross-host MySQL grants on portal-host

**Files:** None

- [ ] **Step 1: Take a final backup of portal-host's menudirect**

```bash
mysqldump --single-transaction menudirect | gzip > /backups/menudirect-final-$(date +%Y%m%d).sql.gz
ls -lh /backups/menudirect-final-*.sql.gz
```

- [ ] **Step 2: Drop the grants**

```bash
mysql -e "DROP USER 'menudirect_app'@'192.168.23.65';"
mysql -e "DROP USER 'sostech_reader'@'192.168.23.65';"
```

- [ ] **Step 3: Drop the menudirect database**

```bash
mysql -e "DROP DATABASE menudirect;"
mysql -e "SHOW DATABASES;" | grep -c menudirect
```

Expected: `0` (database gone).

- [ ] **Step 4: Remove the operational notes file**

```bash
rm /root/menudirect-migration-grants.txt
```

---

### Task F2: Remove `menudirect` connection from portal

**Files:**
- Modify: `/var/www/portal/config/database.php`

- [ ] **Step 1: Delete the menudirect connection block**

In `/var/www/portal/config/database.php`, remove the `'menudirect' => [ ... ]` block from `'connections' => [...]`.

- [ ] **Step 2: Verify portal still boots**

```bash
cd /var/www/portal && php artisan config:clear && php artisan optimize 2>&1 | tail -10 && php artisan route:list | wc -l
```

Expected: optimize succeeds, route count similar to before.

- [ ] **Step 3: Commit**

```bash
cd /var/www/portal && git add config/database.php && git commit -m "cleanup(menudirect): remove cross-host connection definition

T+7 post-cutover cleanup. MenuDirect database has been dropped on this
host; the connection definition is no longer referenced by any code."
```

---

### Task F3: Delete restaurant code from portal

**Files:** All restaurant-related files in `/var/www/portal/app/`

- [ ] **Step 1: Delete models**

```bash
cd /var/www/portal
git rm app/Models/RestaurantSite.php app/Models/MenuItem.php \
  app/Models/MenuCategory.php app/Models/FoodOrder.php \
  app/Models/FoodOrderItem.php app/Models/Order.php \
  app/Models/OrderNotification.php app/Models/OrderAuditLog.php \
  app/Models/Reservation.php app/Models/RestaurantLead.php \
  app/Models/RestaurantStaff.php app/Models/RestaurantPlan.php \
  app/Models/RestaurantCustomDomain.php app/Models/Announcement.php \
  app/Models/LeadActivity.php app/Models/LeadEmailTrack.php \
  app/Models/DemoSession.php
```

- [ ] **Step 2: Delete controllers**

```bash
cd /var/www/portal
git rm app/Http/Controllers/Client/Restaurant*.php
git rm app/Http/Controllers/Admin/Restaurant*.php
git rm app/Http/Controllers/Admin/OrdersController.php
git rm -r app/Http/Controllers/Staff
git rm app/Http/Controllers/Api/Restaurant*.php app/Http/Controllers/Api/FoodOrder* \
       app/Http/Controllers/Api/StaffOrders* app/Http/Controllers/Api/Reservation* \
       app/Http/Controllers/Api/Catering* app/Http/Controllers/Api/Demo* \
       app/Http/Controllers/Api/MenudirectLeadController.php \
       app/Http/Controllers/Api/StripeWebhookRelayController.php \
       app/Http/Controllers/Api/DomainCheckController.php
```

- [ ] **Step 3: Delete jobs, mailables, services**

```bash
cd /var/www/portal
git rm app/Jobs/SendOrderNotificationsJob.php app/Jobs/SendReservationNotificationsJob.php \
       app/Jobs/SendCateringInquiryNotificationsJob.php
git rm app/Mail/NewFoodOrder.php app/Mail/OrderConfirmation.php app/Mail/OrderStatusUpdate.php \
       app/Mail/NewReservation.php app/Mail/ReservationConfirmation.php \
       app/Mail/ReservationStatusUpdate.php app/Mail/RestaurantWelcome.php
git rm app/Services/ReservationService.php app/Services/DeliveryZoneService.php
```

- [ ] **Step 4: Delete views**

```bash
cd /var/www/portal
git rm -r resources/views/client/restaurant resources/views/admin/restaurant
```

- [ ] **Step 5: Remove restaurant routes from portal**

In `/var/www/portal/routes/web.php` and `routes/api.php`, delete the route blocks added during the original restaurant feature work. (The `cutover/disable-restaurant-routes` branch already commented them; this completes the deletion.)

```bash
cd /var/www/portal && git checkout main
# Edit web.php and api.php to remove restaurant route blocks entirely
git add routes/
```

- [ ] **Step 6: Verify portal still boots and tests still pass**

```bash
cd /var/www/portal && php artisan config:clear && php artisan optimize && php artisan test 2>&1 | tail -20
```

Expected: optimize succeeds, tests pass (or only known-irrelevant failures).

- [ ] **Step 7: Commit**

```bash
cd /var/www/portal && git commit -m "cleanup(menudirect): remove all restaurant code from portal

T+7 post-cutover cleanup. All restaurant functionality now lives at
/var/www/app on 192.168.23.65. Portal retains SOS Tech client management,
SOSDesk integration, and infrastructure tooling."
```

---

### Task F4: Delete restaurant code from sos-tech

**Files:** Restaurant-related files in `/var/www/sos-tech/`

- [ ] **Step 1: Delete restaurant controllers and Turnstile**

```bash
cd /var/www/sos-tech
git rm app/Http/Controllers/SampleSiteController.php
git rm app/Http/Controllers/MenuDirectController.php
# NOTE: TurnstileVerifier stays — sos-tech contact form still uses it.
```

- [ ] **Step 2: Delete templates and assets**

```bash
cd /var/www/sos-tech
git rm -r resources/views/samples
git rm -r resources/views/menudirect
git rm -r public/images/templates public/images/template-previews public/images/menudirect
```

- [ ] **Step 3: Remove restaurant routes from sos-tech**

In `/var/www/sos-tech/routes/web.php`, delete the `Route::domain("menudirect.ca")` block, the `Route::domain("{slug}.menudirect.ca")` block, and the `Route::fallback()` line that resolves custom restaurant domains. Edit using the Edit tool.

```bash
git add routes/web.php
```

- [ ] **Step 4: Verify sos-tech still boots**

```bash
cd /var/www/sos-tech && php artisan config:clear && php artisan optimize 2>&1 | tail -10
curl -sS -o /dev/null -w "%{http_code}\n" http://localhost/  # SOS Tech site still serves
```

Expected: 200.

- [ ] **Step 5: Commit**

```bash
cd /var/www/sos-tech && git commit -m "cleanup(menudirect): remove restaurant rendering + lead form

T+7 post-cutover cleanup. All MenuDirect surfaces now at
portal.menudirect.ca and *.menudirect.ca, served by the dedicated
MenuDirect VM. sos-tech retains the SOS Tech marketing site and contact
form (Turnstile defender stays since the contact form still uses it)."
```

---

### Task F5: Remove sostech_clients connection from new VM

**Files:**
- Modify: `/var/www/app/config/database.php`
- Modify: `/var/www/app/.env`

- [ ] **Step 1: Drop the sostech_clients block from config/database.php**

```bash
ssh menudirect 'cd /var/www/app'
# Edit /var/www/app/config/database.php — remove the 'sostech_clients' => [...] block.
```

- [ ] **Step 2: Remove SOSTECH_DB_* from .env**

```bash
ssh menudirect 'cd /var/www/app && sed -i "/^SOSTECH_DB_/d" .env && grep "^SOSTECH" .env || echo "all removed"'
```

Expected: `all removed`.

- [ ] **Step 3: Delete seed-users command**

```bash
ssh menudirect 'cd /var/www/app && git rm app/Console/Commands/SeedUsersFromClients.php'
```

- [ ] **Step 4: Rebuild + verify**

```bash
ssh menudirect 'cd /var/www/app && sudo -u www-data php artisan optimize 2>&1 | tail -5'
```

- [ ] **Step 5: Commit**

```bash
ssh menudirect 'cd /var/www/app && git add -A && git commit -m "cleanup(menudirect): remove sostech_clients connection and seed-users

T+7 post-cutover cleanup. Cross-host clients access is no longer needed —
new restaurant owners register directly when self-serve signup ships."
```

---

## Definition of Done

The migration is complete when **all** of the following are true after Phase F:

- [ ] `https://menudirect.ca/` renders the marketing apex, lead form works, leads land in new VM's `restaurant_leads`.
- [ ] `https://portal.menudirect.ca/login` accepts both Frank (admin) and Suwanna (owner) logins.
- [ ] All live restaurant subdomains render and accept test orders end-to-end.
- [ ] Order notification emails (via Mailcow) arrive at the right addresses.
- [ ] Reservation flow works end-to-end.
- [ ] No 500 errors in `/var/www/app/storage/logs/laravel.log` for 7+ days post-cutover.
- [ ] `/var/www/portal/app/` contains no restaurant-related files.
- [ ] `/var/www/sos-tech/app/Http/Controllers/` contains no `SampleSiteController` or `MenuDirectController`.
- [ ] Portal-host MySQL has no `menudirect` database and no `192.168.23.65` grants.
- [ ] New VM `.env` has no `SOSTECH_DB_*` entries.

---

## Spec coverage check

Each spec section is implemented:

- §5 (Target architecture) — realized via Phases A–E.
- §6 (Scope: moves/stays/deleted) — Phases B/C move code; Phase F deletes from origin.
- §7 (DB migration mechanics) — A2 sets up connections, B/C use them, E flips them.
- §8 (Auth migration) — A3 (users table), A5 (seed-users), A6 + E2.6 (seeding runs).
- §9 (Cutover runbook) — Phase E mirrors the spec section directly.
- §10 (Rollback) — Reference Phase E rollback notes (in spec).
- §11 (Definition of done) — Mirrored above.

Open items: none.
