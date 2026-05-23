# Management / Provisioning API — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give `portal.sos-tech.ca` a thin, authenticated HTTP API to create owner logins, provision restaurant sites, change plans, and set site status on MenuDirect.

**Architecture:** Thin `Api\Manage\*` controllers behind a static-token + IP-allowlist middleware, all delegating to one transactional `SiteProvisioningService`. One-way push; no billing modelled here. Spec: `docs/superpowers/specs/2026-05-23-management-api-design.md`.

**Tech Stack:** Laravel 13, PHP 8.3, MySQL 8, Redis (idempotency cache), PHPUnit feature tests against a dedicated MySQL test DB.

---

## File structure

- Create `app/Http/Middleware/VerifyManagementApiToken.php` — auth (bearer secret + IP allowlist, fail-closed).
- Create `app/Services/SiteProvisioningService.php` — all operational writes (owner, site, plan, status, customer); transactional.
- Create `app/Http/Controllers/Api/Manage/OwnerController.php` — `POST /owners`.
- Create `app/Http/Controllers/Api/Manage/SiteController.php` — `POST /sites`, `PATCH /sites/{id}/plan`, `PATCH /sites/{id}/status`.
- Create `app/Http/Controllers/Api/Manage/CustomerController.php` — `POST /customers`.
- Create `app/Http/Resources/ManagedSiteResource.php`, `ManagedOwnerResource.php` — JSON shaping.
- Modify `config/services.php` — add `management` block.
- Modify `bootstrap/app.php` — register `manage.auth` middleware alias + a manage-group exception renderer for the error envelope.
- Modify `routes/api.php` — add the `/api/v1/manage` route group.
- Modify `app/Http/Controllers/SampleSiteController.php` — serve a 503 holding page for `suspended` sites.
- Modify `config/database.php` — make `menudirect`/`sostech_clients` connection driver+db env-overridable (for the MySQL test DB).
- Modify `phpunit.xml` — test DB + management API env.
- Modify `tests/TestCase.php` — base test setup (point both connections at the test DB).
- Create `tests/Feature/Manage/*Test.php` — feature tests per endpoint.
- Create `resources/views/errors/site-suspended.blade.php` — holding page.

---

## Task 0: Test harness (MySQL test DB + base TestCase)

**Files:**
- Modify: `config/database.php` (menudirect + sostech_clients connection blocks)
- Modify: `phpunit.xml`
- Modify: `tests/TestCase.php`
- Test: `tests/Feature/Manage/HarnessTest.php`

- [ ] **Step 1: Create the MySQL test database** (one-time, on the dev/CI host)

```bash
cd /var/www/app
PASS=$(grep -E '^MENUDIRECT_DB_PASSWORD=' .env | cut -d= -f2-)
mysql -u menudirect -p"$PASS" -e "CREATE DATABASE IF NOT EXISTS menudirect_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u menudirect -p"$PASS" -e "SHOW DATABASES LIKE 'menudirect_test';"
```
Expected: a row `menudirect_test`. (The `menudirect` MySQL user already has rights on `menudirect*`.)

- [ ] **Step 2: Make the `menudirect` + `sostech_clients` connections env-overridable**

In `config/database.php`, change the `menudirect` connection's `database` line so tests can redirect it. Find:
```php
'menudirect' => [
    'driver' => 'mysql',
    ...
    'database' => env('MENUDIRECT_DB_DATABASE', 'menudirect'),
```
It already reads `MENUDIRECT_DB_DATABASE` — confirm that. Do the same for `sostech_clients` (`'database' => env('SOSTECH_DB_DATABASE', 'sos_portal')`). No code change if both already use `env(...)`; otherwise wrap them in `env()`.

- [ ] **Step 3: Point tests at the test DB + set management env**

In `phpunit.xml`, inside `<php>`, replace the sqlite lines and add management vars:
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="menudirect_test"/>
<env name="MENUDIRECT_DB_DATABASE" value="menudirect_test"/>
<env name="SOSTECH_DB_DATABASE" value="menudirect_test"/>
<env name="MANAGEMENT_API_TOKEN" value="test-management-token"/>
<env name="MANAGEMENT_API_ALLOWED_IPS" value="127.0.0.1"/>
```
(Both `mysql` default and `menudirect` now resolve to `menudirect_test`, so `RefreshDatabase` migrates one DB that every model sees.)

- [ ] **Step 4: Base TestCase uses the app + CreatesApplication**

Replace `tests/TestCase.php` with:
```php
<?php
namespace Tests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use \Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
    protected function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }
}
```

- [ ] **Step 5: Write the harness sanity test**

`tests/Feature/Manage/HarnessTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\RestaurantSite;

class HarnessTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_persist_on_the_menudirect_connection(): void
    {
        $user = User::create(['name' => 'T', 'email' => 't@example.com', 'password' => bcrypt('x')]);
        $site = RestaurantSite::create([
            'client_id' => $user->id, 'slug' => 'harness-test', 'business_name' => 'Harness',
            'status' => RestaurantSite::STATUS_ACTIVE, 'plan' => RestaurantSite::PLAN_BASIC,
        ]);
        $this->assertDatabaseHas('restaurant_sites', ['slug' => 'harness-test']);
        $this->assertEquals($user->id, $site->client_id);
    }
}
```

- [ ] **Step 6: Run it**

Run: `php artisan test --filter=HarnessTest`
Expected: PASS. If a migration fails on MySQL, fix that migration before continuing (do not switch to sqlite).

- [ ] **Step 7: Commit**

```bash
git add config/database.php phpunit.xml tests/TestCase.php tests/Feature/Manage/HarnessTest.php
git commit -m "test: MySQL test-DB harness for menudirect-connection models"
```

---

## Task 1: Auth middleware + manage route group + error envelope

**Files:**
- Modify: `config/services.php`
- Create: `app/Http/Middleware/VerifyManagementApiToken.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Manage/AuthTest.php`

- [ ] **Step 1: Write the failing auth test**

`tests/Feature/Manage/AuthTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private array $hdr = ['Authorization' => 'Bearer test-management-token'];

    public function test_missing_token_is_401(): void
    {
        $this->postJson('/api/v1/manage/ping', [])->assertStatus(401)
             ->assertJsonPath('error.code', 'unauthorized');
    }

    public function test_bad_token_is_401(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer wrong'])
             ->postJson('/api/v1/manage/ping', [])->assertStatus(401);
    }

    public function test_disallowed_ip_is_403(): void
    {
        $this->withHeaders($this->hdr)->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])
             ->postJson('/api/v1/manage/ping', [])->assertStatus(403)
             ->assertJsonPath('error.code', 'ip_forbidden');
    }

    public function test_valid_token_and_ip_passes(): void
    {
        $this->withHeaders($this->hdr)->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
             ->postJson('/api/v1/manage/ping', [])->assertOk()->assertJsonPath('ok', true);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=AuthTest`
Expected: FAIL (route 404 / no middleware).

- [ ] **Step 3: Add config block**

In `config/services.php` add:
```php
'management' => [
    'token' => env('MANAGEMENT_API_TOKEN'),
    'allowed_ips' => array_filter(array_map('trim', explode(',', (string) env('MANAGEMENT_API_ALLOWED_IPS', '')))),
],
```

- [ ] **Step 4: Create the middleware**

`app/Http/Middleware/VerifyManagementApiToken.php`:
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyManagementApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.management.token');
        $provided = (string) ($request->bearerToken() ?? '');
        if ($expected === '' || !hash_equals($expected, $provided)) {
            return $this->deny('unauthorized', 'Invalid or missing API token.', 401);
        }

        $allowed = config('services.management.allowed_ips', []);
        if (empty($allowed) || !in_array($request->ip(), $allowed, true)) {
            \Log::warning('Management API IP rejected', ['ip' => $request->ip()]);
            return $this->deny('ip_forbidden', 'Source IP not allowed.', 403);
        }

        return $next($request);
    }

    private function deny(string $code, string $message, int $status): Response
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
```

- [ ] **Step 5: Register alias + manage route group**

In `bootstrap/app.php` `->withMiddleware(...)`, add to the `alias` array:
```php
"manage.auth" => \App\Http\Middleware\VerifyManagementApiToken::class,
```
In `routes/api.php` append:
```php
Route::prefix('v1/manage')->middleware(['manage.auth', 'throttle:60,1'])->group(function () {
    Route::post('/ping', fn () => response()->json(['ok' => true]))->name('api.manage.ping');
});
```

- [ ] **Step 6: Run to verify pass**

Run: `php artisan test --filter=AuthTest`
Expected: PASS (4 tests).

- [ ] **Step 7: Commit**

```bash
git add config/services.php app/Http/Middleware/VerifyManagementApiToken.php bootstrap/app.php routes/api.php tests/Feature/Manage/AuthTest.php
git commit -m "feat(manage-api): static-token + IP-allowlist auth middleware and route group"
```

---

## Task 2: SiteProvisioningService — createOwner + invite link

**Files:**
- Create: `app/Services/SiteProvisioningService.php`
- Test: `tests/Feature/Manage/CreateOwnerServiceTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Manage/CreateOwnerServiceTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SiteProvisioningService;
use App\Models\User;

class CreateOwnerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_owner_with_unusable_password_and_invite_url(): void
    {
        $svc = app(SiteProvisioningService::class);
        $r = $svc->createOwner('owner@example.com', 'Jane Doe');

        $this->assertInstanceOf(User::class, $r['owner']);
        $this->assertFalse($r['already_existed']);
        $this->assertStringContainsString('/reset-password/', $r['set_password_url']);
        $this->assertStringContainsString('owner%40example.com', $r['set_password_url']);
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com', 'is_admin' => false]);
    }

    public function test_is_idempotent_on_email(): void
    {
        $svc = app(SiteProvisioningService::class);
        $svc->createOwner('dup@example.com', 'First');
        $r = $svc->createOwner('dup@example.com', 'Second');
        $this->assertTrue($r['already_existed']);
        $this->assertSame(1, User::where('email', 'dup@example.com')->count());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=CreateOwnerServiceTest`
Expected: FAIL ("Class SiteProvisioningService not found").

- [ ] **Step 3: Implement the service (owner part)**

`app/Services/SiteProvisioningService.php`:
```php
<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class SiteProvisioningService
{
    /**
     * Create (or return existing) owner login. New owners get a one-time
     * set-password link via the existing password-reset flow; no usable
     * password is ever set or returned.
     *
     * @return array{owner: User, set_password_url: ?string, set_password_expires_at: ?string, already_existed: bool}
     */
    public function createOwner(string $email, string $name, bool $reissueInvite = false): array
    {
        $existing = User::where('email', $email)->first();
        if ($existing) {
            return [
                'owner' => $existing,
                'set_password_url' => $reissueInvite ? $this->inviteUrl($existing) : null,
                'set_password_expires_at' => null,
                'already_existed' => true,
            ];
        }

        $owner = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(Str::random(40)),
        ]);

        return [
            'owner' => $owner,
            'set_password_url' => $this->inviteUrl($owner),
            'set_password_expires_at' => now()->addHour()->toIso8601String(),
            'already_existed' => false,
        ];
    }

    private function inviteUrl(User $owner): string
    {
        $token = Password::createToken($owner);
        return route('password.reset', ['token' => $token]) . '?email=' . urlencode($owner->email);
    }
}
```

- [ ] **Step 4: Run to verify pass**

Run: `php artisan test --filter=CreateOwnerServiceTest`
Expected: PASS. (If `route('password.reset')` errors, confirm the route exists in `routes/web.php` — it does, host-scoped to portal.menudirect.ca.)

- [ ] **Step 5: Commit**

```bash
git add app/Services/SiteProvisioningService.php tests/Feature/Manage/CreateOwnerServiceTest.php
git commit -m "feat(manage-api): SiteProvisioningService.createOwner with invite link"
```

---

## Task 3: POST /owners endpoint

**Files:**
- Create: `app/Http/Controllers/Api/Manage/OwnerController.php`
- Create: `app/Http/Resources/ManagedOwnerResource.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Manage/OwnerEndpointTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Manage/OwnerEndpointTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerEndpointTest extends TestCase
{
    use RefreshDatabase;
    private function call(array $body) {
        return $this->withHeaders(['Authorization' => 'Bearer test-management-token'])
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/manage/owners', $body);
    }

    public function test_creates_owner_201_with_invite_url(): void
    {
        $this->call(['email' => 'o@example.com', 'name' => 'Owner One'])
            ->assertStatus(201)
            ->assertJsonPath('owner.email', 'o@example.com')
            ->assertJsonStructure(['owner' => ['id', 'email', 'name'], 'set_password_url', 'already_existed']);
    }

    public function test_duplicate_email_returns_200_existing(): void
    {
        $this->call(['email' => 'dup@example.com', 'name' => 'A'])->assertStatus(201);
        $this->call(['email' => 'dup@example.com', 'name' => 'B'])
            ->assertStatus(200)->assertJsonPath('already_existed', true);
    }

    public function test_invalid_email_is_422(): void
    {
        $this->call(['email' => 'not-an-email', 'name' => 'X'])
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=OwnerEndpointTest`
Expected: FAIL (404 on route).

- [ ] **Step 3: Create the resource**

`app/Http/Resources/ManagedOwnerResource.php`:
```php
<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagedOwnerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'is_admin' => (bool) $this->is_admin,
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

`app/Http/Controllers/Api/Manage/OwnerController.php`:
```php
<?php
namespace App\Http\Controllers\Api\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManagedOwnerResource;
use App\Services\SiteProvisioningService;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function __construct(private SiteProvisioningService $svc) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'send_welcome_email' => ['sometimes', 'boolean'],
            'reissue_invite' => ['sometimes', 'boolean'],
        ]);

        $r = $this->svc->createOwner($data['email'], $data['name'], (bool) ($data['reissue_invite'] ?? false));

        return response()->json([
            'owner' => new ManagedOwnerResource($r['owner']),
            'set_password_url' => $r['set_password_url'],
            'set_password_expires_at' => $r['set_password_expires_at'],
            'already_existed' => $r['already_existed'],
        ], $r['already_existed'] ? 200 : 201);
    }
}
```

- [ ] **Step 5: Add the route**

In `routes/api.php`, inside the `v1/manage` group, add:
```php
Route::post('/owners', [\App\Http\Controllers\Api\Manage\OwnerController::class, 'store'])->name('api.manage.owners.store');
```

- [ ] **Step 6: Make validation errors use the envelope**

In `bootstrap/app.php` `->withExceptions(function (Exceptions $exceptions) {`, add:
```php
$exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
    if ($request->is('api/v1/manage/*')) {
        return response()->json(['error' => [
            'code' => 'validation_failed', 'message' => $e->getMessage(), 'details' => $e->errors(),
        ]], 422);
    }
});
```

- [ ] **Step 7: Run to verify pass**

Run: `php artisan test --filter=OwnerEndpointTest`
Expected: PASS (3 tests).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/Manage/OwnerController.php app/Http/Resources/ManagedOwnerResource.php routes/api.php bootstrap/app.php tests/Feature/Manage/OwnerEndpointTest.php
git commit -m "feat(manage-api): POST /owners endpoint + validation error envelope"
```

---

## Task 4: SiteProvisioningService — provisionSite (plan map + flag sync)

**Files:**
- Modify: `app/Services/SiteProvisioningService.php`
- Test: `tests/Feature/Manage/ProvisionSiteServiceTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Manage/ProvisionSiteServiceTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SiteProvisioningService;
use App\Models\{User, RestaurantSite, RestaurantPlan};

class ProvisionSiteServiceTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $o = []): RestaurantPlan
    {
        return RestaurantPlan::create(array_merge([
            'name' => 'SiteFresh', 'slug' => 'sitefresh', 'price_monthly' => 35, 'price_annual' => 350,
            'online_ordering' => true,
        ], $o));
    }

    public function test_provisions_site_with_plan_and_synced_flags(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o@x.com', 'password' => bcrypt('x')]);
        $plan = $this->plan();
        $svc = app(SiteProvisioningService::class);

        $site = $svc->provisionSite([
            'business_name' => "Buster's Burgers", 'plan_id' => $plan->id, 'owner_id' => $owner->id,
        ]);

        $this->assertEquals('busters-burgers', $site->slug);
        $this->assertEquals($plan->id, $site->restaurant_plan_id);
        $this->assertEquals(RestaurantSite::PLAN_SELFSERVICE, $site->plan); // sitefresh -> selfservice
        $this->assertEquals(RestaurantSite::STATUS_ACTIVE, $site->status);
        $this->assertTrue((bool) $site->ordering_enabled); // synced from plan.online_ordering
    }

    public function test_duplicate_slug_throws_conflict(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o2@x.com', 'password' => bcrypt('x')]);
        $plan = $this->plan(['slug' => 'basic', 'online_ordering' => false]);
        $svc = app(SiteProvisioningService::class);
        $svc->provisionSite(['business_name' => 'Dup', 'slug' => 'dup', 'plan_id' => $plan->id, 'owner_id' => $owner->id]);

        $this->expectException(\App\Exceptions\ProvisioningConflictException::class);
        $svc->provisionSite(['business_name' => 'Dup2', 'slug' => 'dup', 'plan_id' => $plan->id, 'owner_id' => $owner->id]);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=ProvisionSiteServiceTest`
Expected: FAIL (method/exception missing).

- [ ] **Step 3: Create the conflict exception**

`app/Exceptions/ProvisioningConflictException.php`:
```php
<?php
namespace App\Exceptions;
class ProvisioningConflictException extends \RuntimeException {}
```

- [ ] **Step 4: Add `provisionSite` + helpers to the service**

Add to `SiteProvisioningService` (new `use` lines at top: `App\Models\RestaurantSite`, `App\Models\RestaurantPlan`, `App\Exceptions\ProvisioningConflictException`):
```php
public function provisionSite(array $data): RestaurantSite
{
    $plan = RestaurantPlan::findOrFail($data['plan_id']);
    $owner = $this->resolveOwner($data);

    $slug = $data['slug'] ?? Str::slug($data['business_name']);
    if (RestaurantSite::withoutGlobalScope('notArchived')->where('slug', $slug)->exists()) {
        throw new ProvisioningConflictException("Slug '{$slug}' is already taken.");
    }

    return RestaurantSite::create([
        'client_id' => $owner->id,
        'restaurant_plan_id' => $plan->id,
        'plan' => $this->mapPlanToType($plan->slug),
        'slug' => $slug,
        'business_name' => $data['business_name'],
        'status' => $data['status'] ?? RestaurantSite::STATUS_ACTIVE,
        'ordering_enabled' => (bool) $plan->online_ordering,
    ]);
}

protected function resolveOwner(array $data): User
{
    if (!empty($data['owner_id'])) {
        return User::findOrFail($data['owner_id']);
    }
    return User::where('email', $data['owner_email'] ?? '')->firstOrFail();
}

protected function mapPlanToType(string $slug): string
{
    return match ($slug) {
        'basic' => RestaurantSite::PLAN_BASIC,
        'sitefresh' => RestaurantSite::PLAN_SELFSERVICE,
        'sitefresh-pro' => RestaurantSite::PLAN_PREMIUM,
        'menudirect-max' => RestaurantSite::PLAN_MAX,
        default => RestaurantSite::PLAN_BASIC,
    };
}
```

- [ ] **Step 5: Run to verify pass**

Run: `php artisan test --filter=ProvisionSiteServiceTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/SiteProvisioningService.php app/Exceptions/ProvisioningConflictException.php tests/Feature/Manage/ProvisionSiteServiceTest.php
git commit -m "feat(manage-api): SiteProvisioningService.provisionSite with plan mapping + flag sync"
```

---

## Task 5: SiteProvisioningService — changePlan + setStatus

**Files:**
- Modify: `app/Services/SiteProvisioningService.php`
- Test: `tests/Feature/Manage/SiteStateServiceTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Manage/SiteStateServiceTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SiteProvisioningService;
use App\Models\{User, RestaurantSite, RestaurantPlan};

class SiteStateServiceTest extends TestCase
{
    use RefreshDatabase;
    private SiteProvisioningService $svc;
    private RestaurantSite $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(SiteProvisioningService::class);
        $owner = User::create(['name' => 'O', 'email' => 'o@x.com', 'password' => bcrypt('x')]);
        $basic = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150, 'online_ordering' => false]);
        $this->site = $this->svc->provisionSite(['business_name' => 'S', 'plan_id' => $basic->id, 'owner_id' => $owner->id]);
    }

    public function test_change_plan_updates_ids_and_flags(): void
    {
        $pro = RestaurantPlan::create(['name' => 'Pro', 'slug' => 'sitefresh-pro', 'price_monthly' => 59, 'price_annual' => 590, 'online_ordering' => true]);
        $site = $this->svc->changePlan($this->site, $pro->id);
        $this->assertEquals($pro->id, $site->restaurant_plan_id);
        $this->assertEquals(RestaurantSite::PLAN_PREMIUM, $site->plan);
        $this->assertTrue((bool) $site->ordering_enabled);
    }

    public function test_set_status_suspended(): void
    {
        $site = $this->svc->setStatus($this->site, 'suspended');
        $this->assertEquals(RestaurantSite::STATUS_SUSPENDED, $site->status);
        $this->assertNull($site->archived_at);
    }

    public function test_set_status_archived_sets_timestamp(): void
    {
        $site = $this->svc->setStatus($this->site, 'archived');
        $this->assertNotNull($site->fresh()->archived_at);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=SiteStateServiceTest`
Expected: FAIL (methods missing).

- [ ] **Step 3: Add `changePlan` + `setStatus`**

Add to `SiteProvisioningService`:
```php
public function changePlan(RestaurantSite $site, int $planId): RestaurantSite
{
    $plan = RestaurantPlan::findOrFail($planId);
    $site->update([
        'restaurant_plan_id' => $plan->id,
        'plan' => $this->mapPlanToType($plan->slug),
        'ordering_enabled' => (bool) $plan->online_ordering,
    ]);
    return $site->refresh();
}

public function setStatus(RestaurantSite $site, string $status): RestaurantSite
{
    if ($status === 'archived') {
        $site->update(['archived_at' => now()]);
        return $site->refresh();
    }
    $site->update(['archived_at' => null, 'status' => $status]);
    return $site->refresh();
}
```

- [ ] **Step 4: Run to verify pass**

Run: `php artisan test --filter=SiteStateServiceTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/SiteProvisioningService.php tests/Feature/Manage/SiteStateServiceTest.php
git commit -m "feat(manage-api): changePlan + setStatus on SiteProvisioningService"
```

---

## Task 6: Site endpoints (POST /sites, PATCH plan, PATCH status)

**Files:**
- Create: `app/Http/Controllers/Api/Manage/SiteController.php`
- Create: `app/Http/Resources/ManagedSiteResource.php`
- Modify: `routes/api.php`, `bootstrap/app.php` (envelope for conflict + model-not-found)
- Test: `tests/Feature/Manage/SiteEndpointTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Manage/SiteEndpointTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{User, RestaurantSite, RestaurantPlan};

class SiteEndpointTest extends TestCase
{
    use RefreshDatabase;
    private function hdr() { return $this->withHeaders(['Authorization' => 'Bearer test-management-token'])->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']); }
    private function seed(): array {
        $owner = User::create(['name' => 'O', 'email' => 'o@x.com', 'password' => bcrypt('x')]);
        $plan = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150, 'online_ordering' => false]);
        return [$owner, $plan];
    }

    public function test_provision_site_201(): void
    {
        [$owner, $plan] = $this->seed();
        $this->hdr()->postJson('/api/v1/manage/sites', [
            'business_name' => 'Busters', 'plan_id' => $plan->id, 'owner_id' => $owner->id,
        ])->assertStatus(201)->assertJsonPath('site.slug', 'busters')->assertJsonPath('site.status', 'active');
    }

    public function test_duplicate_slug_409(): void
    {
        [$owner, $plan] = $this->seed();
        $body = ['business_name' => 'Dup', 'slug' => 'dup', 'plan_id' => $plan->id, 'owner_id' => $owner->id];
        $this->hdr()->postJson('/api/v1/manage/sites', $body)->assertStatus(201);
        $this->hdr()->postJson('/api/v1/manage/sites', $body)->assertStatus(409)->assertJsonPath('error.code', 'conflict');
    }

    public function test_change_plan_and_status(): void
    {
        [$owner, $plan] = $this->seed();
        $site = RestaurantSite::create(['client_id' => $owner->id, 'restaurant_plan_id' => $plan->id, 'slug' => 's1', 'business_name' => 'S1', 'status' => 'active', 'plan' => 'basic']);
        $pro = RestaurantPlan::create(['name' => 'Pro', 'slug' => 'sitefresh-pro', 'price_monthly' => 59, 'price_annual' => 590, 'online_ordering' => true]);

        $this->hdr()->patchJson("/api/v1/manage/sites/{$site->id}/plan", ['plan_id' => $pro->id])
            ->assertOk()->assertJsonPath('site.plan', 'premium');
        $this->hdr()->patchJson("/api/v1/manage/sites/{$site->id}/status", ['status' => 'suspended'])
            ->assertOk()->assertJsonPath('site.status', 'suspended');
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=SiteEndpointTest`
Expected: FAIL (routes 404).

- [ ] **Step 3: Create the site resource**

`app/Http/Resources/ManagedSiteResource.php`:
```php
<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagedSiteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'business_name' => $this->business_name,
            'status' => $this->status,
            'plan' => $this->plan,
            'plan_id' => $this->restaurant_plan_id,
            'ordering_enabled' => (bool) $this->ordering_enabled,
            'owner_id' => $this->client_id,
            'archived_at' => $this->archived_at,
            'public_url' => $this->getPublicUrl(),
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

`app/Http/Controllers/Api/Manage/SiteController.php`:
```php
<?php
namespace App\Http\Controllers\Api\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManagedSiteResource;
use App\Models\RestaurantSite;
use App\Services\SiteProvisioningService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(private SiteProvisioningService $svc) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/'],
            'template' => ['sometimes', 'string', 'max:60'],
            'plan_id' => ['required', 'integer', 'exists:menudirect.restaurant_plans,id'],
            'owner_id' => ['required_without:owner_email', 'integer'],
            'owner_email' => ['required_without:owner_id', 'email'],
            'status' => ['sometimes', 'in:demo,active,suspended'],
        ]);
        $site = $this->svc->provisionSite($data);
        return response()->json(['site' => new ManagedSiteResource($site)], 201);
    }

    public function changePlan(Request $request, RestaurantSite $site)
    {
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:menudirect.restaurant_plans,id']]);
        $site = $this->svc->changePlan($site, $data['plan_id']);
        return response()->json(['site' => new ManagedSiteResource($site)]);
    }

    public function setStatus(Request $request, RestaurantSite $site)
    {
        $data = $request->validate(['status' => ['required', 'in:demo,active,suspended,archived']]);
        $site = $this->svc->setStatus($site, $data['status']);
        return response()->json(['site' => new ManagedSiteResource($site)]);
    }
}
```
> Note: route-model binding for `{site}` must see archived sites. Use explicit binding (Step 5) with `withoutGlobalScope`.

- [ ] **Step 5: Add routes + binding + error envelope for conflict/not-found**

In `routes/api.php` inside the manage group:
```php
Route::post('/sites', [\App\Http\Controllers\Api\Manage\SiteController::class, 'store'])->name('api.manage.sites.store');
Route::patch('/sites/{site}/plan', [\App\Http\Controllers\Api\Manage\SiteController::class, 'changePlan'])->name('api.manage.sites.plan');
Route::patch('/sites/{site}/status', [\App\Http\Controllers\Api\Manage\SiteController::class, 'setStatus'])->name('api.manage.sites.status');
```
In `routes/api.php` top (after `use`), add an explicit binding that ignores the archived scope:
```php
Route::bind('site', fn ($id) => \App\Models\RestaurantSite::withoutGlobalScope('notArchived')->findOrFail($id));
```
In `bootstrap/app.php` `withExceptions`, add renders for the manage path:
```php
$exceptions->render(function (\App\Exceptions\ProvisioningConflictException $e, $request) {
    if ($request->is('api/v1/manage/*')) return response()->json(['error' => ['code' => 'conflict', 'message' => $e->getMessage()]], 409);
});
$exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
    if ($request->is('api/v1/manage/*')) return response()->json(['error' => ['code' => 'not_found', 'message' => 'Resource not found.']], 404);
});
```

- [ ] **Step 6: Run to verify pass**

Run: `php artisan test --filter=SiteEndpointTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/Manage/SiteController.php app/Http/Resources/ManagedSiteResource.php routes/api.php bootstrap/app.php tests/Feature/Manage/SiteEndpointTest.php
git commit -m "feat(manage-api): site provision + change-plan + set-status endpoints"
```

---

## Task 7: Suspended-site holding page

**Files:**
- Create: `resources/views/errors/site-suspended.blade.php`
- Modify: `app/Http/Controllers/SampleSiteController.php` (`show` + `showByDomain`)
- Test: `tests/Feature/Manage/SuspendedSiteTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Manage/SuspendedSiteTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{User, RestaurantSite, RestaurantPlan};

class SuspendedSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_site_returns_503_holding_page(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o@x.com', 'password' => bcrypt('x')]);
        $plan = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150]);
        RestaurantSite::create(['client_id' => $owner->id, 'restaurant_plan_id' => $plan->id, 'slug' => 'suspendedco', 'business_name' => 'Suspended Co', 'status' => 'suspended', 'plan' => 'basic']);

        $this->get('http://suspendedco.menudirect.ca/')->assertStatus(503)->assertsee('temporarily unavailable', false);
    }
}
```
(Adjust the host helper to match how other `samples` tests issue subdomain requests if a pattern exists.)

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=SuspendedSiteTest`
Expected: FAIL (returns 200 or 404, not 503).

- [ ] **Step 3: Create the holding page**

`resources/views/errors/site-suspended.blade.php`:
```blade
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Temporarily unavailable</title></head>
<body style="font-family:system-ui;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;background:#f8fafc;color:#0f172a">
<div style="text-align:center;max-width:30rem;padding:2rem">
<h1 style="font-size:1.5rem;margin:0 0 .5rem">This site is temporarily unavailable</h1>
<p style="color:#475569">Please check back soon. If you're the owner, contact MenuDirect support.</p>
</div></body></html>
```

- [ ] **Step 4: Gate suspended sites in the controller**

In `app/Http/Controllers/SampleSiteController.php`, immediately after the site is resolved in both `show()` and `showByDomain()` (i.e. once `$site` / the site data is loaded and before rendering the template), add:
```php
if (($site->status ?? null) === \App\Models\RestaurantSite::STATUS_SUSPENDED) {
    return response()->view('errors.site-suspended', [], 503);
}
```
(If the controller works from an array/DTO rather than a model, compare the `status` field on that structure instead.)

- [ ] **Step 5: Run to verify pass**

Run: `php artisan test --filter=SuspendedSiteTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/errors/site-suspended.blade.php app/Http/Controllers/SampleSiteController.php tests/Feature/Manage/SuspendedSiteTest.php
git commit -m "feat(manage-api): suspended sites serve a 503 holding page"
```

---

## Task 8: POST /customers (atomic owner+site+plan)

**Files:**
- Modify: `app/Services/SiteProvisioningService.php` (add `provisionCustomer`)
- Create: `app/Http/Controllers/Api/Manage/CustomerController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Manage/CustomerEndpointTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Manage/CustomerEndpointTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{RestaurantPlan, User, RestaurantSite};

class CustomerEndpointTest extends TestCase
{
    use RefreshDatabase;
    private function hdr() { return $this->withHeaders(['Authorization' => 'Bearer test-management-token'])->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']); }

    public function test_creates_owner_and_site_atomically(): void
    {
        $plan = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150]);
        $this->hdr()->postJson('/api/v1/manage/customers', [
            'owner' => ['email' => 'new@x.com', 'name' => 'New Owner'],
            'site' => ['business_name' => 'New Resto', 'slug' => 'new-resto'],
            'plan_id' => $plan->id,
        ])->assertStatus(201)
          ->assertJsonPath('site.slug', 'new-resto')
          ->assertJsonPath('owner.email', 'new@x.com')
          ->assertJsonStructure(['owner' => ['id'], 'set_password_url', 'site' => ['id']]);

        $this->assertDatabaseHas('users', ['email' => 'new@x.com']);
        $this->assertDatabaseHas('restaurant_sites', ['slug' => 'new-resto']);
    }

    public function test_duplicate_slug_rolls_back_owner(): void
    {
        $plan = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150]);
        $existingOwner = User::create(['name' => 'X', 'email' => 'pre@x.com', 'password' => bcrypt('x')]);
        RestaurantSite::create(['client_id' => $existingOwner->id, 'restaurant_plan_id' => $plan->id, 'slug' => 'taken', 'business_name' => 'T', 'status' => 'active', 'plan' => 'basic']);

        $this->hdr()->postJson('/api/v1/manage/customers', [
            'owner' => ['email' => 'rollback@x.com', 'name' => 'RB'],
            'site' => ['business_name' => 'RB', 'slug' => 'taken'],
            'plan_id' => $plan->id,
        ])->assertStatus(409);

        $this->assertDatabaseMissing('users', ['email' => 'rollback@x.com']); // rolled back
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=CustomerEndpointTest`
Expected: FAIL.

- [ ] **Step 3: Add `provisionCustomer` to the service**

Add (top `use`: `Illuminate\Support\Facades\DB`):
```php
/** @return array{owner: User, set_password_url: ?string, site: RestaurantSite} */
public function provisionCustomer(array $owner, array $site, int $planId, ?string $status = null, bool $sendWelcome = false): array
{
    return DB::transaction(function () use ($owner, $site, $planId, $status, $sendWelcome) {
        $ownerResult = $this->createOwner($owner['email'], $owner['name'], reissueInvite: true);
        $newSite = $this->provisionSite([
            'business_name' => $site['business_name'],
            'slug' => $site['slug'] ?? null,
            'template' => $site['template'] ?? null,
            'plan_id' => $planId,
            'owner_id' => $ownerResult['owner']->id,
            'status' => $status ?? RestaurantSite::STATUS_ACTIVE,
        ]);
        return ['owner' => $ownerResult['owner'], 'set_password_url' => $ownerResult['set_password_url'], 'site' => $newSite];
    });
}
```

- [ ] **Step 4: Create the controller**

`app/Http/Controllers/Api/Manage/CustomerController.php`:
```php
<?php
namespace App\Http\Controllers\Api\Manage;

use App\Http\Controllers\Controller;
use App\Http\Resources\{ManagedOwnerResource, ManagedSiteResource};
use App\Services\SiteProvisioningService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private SiteProvisioningService $svc) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner.email' => ['required', 'email', 'max:255'],
            'owner.name' => ['required', 'string', 'max:255'],
            'site.business_name' => ['required', 'string', 'max:255'],
            'site.slug' => ['sometimes', 'string', 'regex:/^[a-z0-9\-]+$/', 'max:255'],
            'site.template' => ['sometimes', 'string', 'max:60'],
            'plan_id' => ['required', 'integer', 'exists:menudirect.restaurant_plans,id'],
            'status' => ['sometimes', 'in:demo,active,suspended'],
            'send_welcome_email' => ['sometimes', 'boolean'],
        ]);

        $r = $this->svc->provisionCustomer(
            $data['owner'], $data['site'], (int) $data['plan_id'],
            $data['status'] ?? null, (bool) ($data['send_welcome_email'] ?? false)
        );

        return response()->json([
            'owner' => new ManagedOwnerResource($r['owner']),
            'set_password_url' => $r['set_password_url'],
            'site' => new ManagedSiteResource($r['site']),
        ], 201);
    }
}
```

- [ ] **Step 5: Add the route**

```php
Route::post('/customers', [\App\Http\Controllers\Api\Manage\CustomerController::class, 'store'])->name('api.manage.customers.store');
```

- [ ] **Step 6: Run to verify pass**

Run: `php artisan test --filter=CustomerEndpointTest`
Expected: PASS (rollback test confirms atomicity).

- [ ] **Step 7: Commit**

```bash
git add app/Services/SiteProvisioningService.php app/Http/Controllers/Api/Manage/CustomerController.php routes/api.php tests/Feature/Manage/CustomerEndpointTest.php
git commit -m "feat(manage-api): atomic POST /customers (owner + site + plan)"
```

---

## Task 9: Idempotency-Key support

**Files:**
- Create: `app/Http/Middleware/IdempotencyKey.php`
- Modify: `routes/api.php` (apply to POST creates), `bootstrap/app.php` (alias)
- Test: `tests/Feature/Manage/IdempotencyTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Manage/IdempotencyTest.php`:
```php
<?php
namespace Tests\Feature\Manage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_key_does_not_create_twice(): void
    {
        $h = ['Authorization' => 'Bearer test-management-token', 'Idempotency-Key' => 'abc-123'];
        $body = ['email' => 'idem@x.com', 'name' => 'Idem'];
        $r1 = $this->withHeaders($h)->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->postJson('/api/v1/manage/owners', $body)->assertStatus(201);
        $r2 = $this->withHeaders($h)->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->postJson('/api/v1/manage/owners', $body)->assertStatus(201);
        $this->assertEquals($r1->json('owner.id'), $r2->json('owner.id'));
        $this->assertSame(1, User::where('email', 'idem@x.com')->count());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=IdempotencyTest`
Expected: FAIL (without the middleware, duplicate email path returns 200 the 2nd time, not 201 — assertion mismatch proves replay isn't happening).

- [ ] **Step 3: Create the middleware**

`app/Http/Middleware/IdempotencyKey.php`:
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        if (!$key) {
            return $next($request);
        }
        $cacheKey = 'idem:' . sha1($request->path() . '|' . $key);
        if ($cached = Cache::get($cacheKey)) {
            return response($cached['body'], $cached['status'])->header('Content-Type', 'application/json');
        }
        $response = $next($request);
        if ($response->getStatusCode() < 400) {
            Cache::put($cacheKey, ['body' => $response->getContent(), 'status' => $response->getStatusCode()], now()->addDay());
        }
        return $response;
    }
}
```

- [ ] **Step 4: Register alias + apply to the group**

In `bootstrap/app.php` alias array: `"idempotency" => \App\Http\Middleware\IdempotencyKey::class,`
In `routes/api.php`, add `idempotency` to the manage group middleware: `->middleware(['manage.auth', 'idempotency', 'throttle:60,1'])`.

- [ ] **Step 5: Run to verify pass**

Run: `php artisan test --filter=IdempotencyTest`
Expected: PASS (replay returns the cached 201).

- [ ] **Step 6: Run the full suite**

Run: `php artisan test --filter=Manage`
Expected: all Manage tests PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Middleware/IdempotencyKey.php bootstrap/app.php routes/api.php tests/Feature/Manage/IdempotencyTest.php
git commit -m "feat(manage-api): Idempotency-Key replay support"
```

---

## Task 10: Audit logging + docs + config

**Files:**
- Modify: `app/Services/SiteProvisioningService.php` (audit hooks) OR controllers — whichever matches `AuditService`'s API
- Modify: `.env.example`, `CLAUDE.md`, `README.md`, `docs/BACKLOG.md`
- Test: `tests/Feature/Manage/AuditTest.php`

- [ ] **Step 1: Inspect AuditService API**

Run: `grep -nE "public function|class " app/Services/Audit/AuditService.php | head`
Note the method to record an event and whether it requires a `User` actor (the spec wants a system "sos-portal" actor — pass null/system if supported).

- [ ] **Step 2: Write the failing audit test**

`tests/Feature/Manage/AuditTest.php` — assert an `audit_logs` row is written after a successful `POST /owners` (match columns to the real `audit_logs` schema; e.g. `action` like `manage.owner.created`).
```php
public function test_owner_creation_is_audited(): void
{
    $this->withHeaders(['Authorization' => 'Bearer test-management-token'])
        ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->postJson('/api/v1/manage/owners', ['email' => 'a@x.com', 'name' => 'A'])->assertStatus(201);
    $this->assertDatabaseHas('audit_logs', ['action' => 'manage.owner.created']);
}
```

- [ ] **Step 3: Run to verify it fails**

Run: `php artisan test --filter=AuditTest` → FAIL.

- [ ] **Step 4: Add audit calls**

In each controller action (or service method), after success call `AuditService` to record `manage.owner.created` / `manage.site.provisioned` / `manage.site.plan_changed` / `manage.site.status_changed` / `manage.customer.created` with the target id and a sanitized payload (no passwords/tokens). Use a system actor.

- [ ] **Step 5: Run to verify pass**

Run: `php artisan test --filter=AuditTest` → PASS.

- [ ] **Step 6: Docs + config**

- Add to `.env.example`: `MANAGEMENT_API_TOKEN=` and `MANAGEMENT_API_ALLOWED_IPS=`.
- `CLAUDE.md`: add a "Management API" subsection under the API/routes area (endpoints, auth, that SOS is the only caller).
- `README.md`: mention the management API under the API surface.
- `docs/BACKLOG.md`: tick Epic 1 items as done.

- [ ] **Step 7: Final full run + commit**

Run: `php artisan test`
Expected: green (Manage suite + existing tests).
```bash
git add -A
git commit -m "feat(manage-api): audit logging + docs + .env.example"
```

---

## Self-review notes (addressed)

- **Spec coverage:** owners (T2/T3), sites provision (T4/T6), change plan (T5/T6), set status (T5/T6), customers (T8), auth (T1), invite flow (T2), plan→flag sync (T4/T5), suspend holding page (T7), idempotency (T9), error envelope (T1/T6), audit (T10), config (T1/T10), tests throughout. No GET/webhooks/billing (out of scope, per spec).
- **Test-DB caveat:** if a ported migration is MySQL-only and fails on the test DB, fix the migration — do not silently skip. The test DB is real MySQL precisely to avoid sqlite incompatibility.
- **Route-model binding** for `{site}` uses `withoutGlobalScope('notArchived')` so status changes work on archived sites (T6 Step 5).
- **`mapPlanToType`** is duplicated from the (dead) admin controller intentionally; collapsing them is a flagged follow-on, not this plan.
