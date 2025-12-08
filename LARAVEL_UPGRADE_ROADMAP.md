# AbuseIO Laravel Upgrade Roadmap
## From Laravel 6 to Laravel 11

**Document Version:** 1.0
**Created:** December 2025
**Current State:** Laravel 6.20.x with PHP 7.4
**Target State:** Laravel 11.x with PHP 8.2+

---

## Executive Summary

AbuseIO is a mature abuse management system currently running on **Laravel 6.20.x** (EOL September 2022). This roadmap provides a step-by-step upgrade path to **Laravel 11**, the current stable release. The upgrade requires PHP version increases and addresses numerous breaking changes across 5 major Laravel versions.

### Current State Analysis

| Component | Current | Target |
|-----------|---------|--------|
| Laravel Framework | 6.20.x | 11.x |
| PHP Version | 7.4 | 8.2+ |
| PHPUnit | 8.x | 10.x |
| Frontend Build | Gulp + Elixir | Vite |
| Bootstrap | 3.x | 5.x (optional) |

### Risk Assessment

| Risk Level | Area |
|------------|------|
| **HIGH** | 21 AbuseIO custom packages need updates |
| **HIGH** | Route definitions use deprecated patterns |
| **MEDIUM** | Session facade usage in middleware |
| **MEDIUM** | MD5 token generation (security concern) |
| **LOW** | Frontend assets (optional upgrade) |

---

## Pre-Upgrade Checklist

Before starting the upgrade process:

- [ ] Create complete backup of the codebase
- [ ] Create complete database backup
- [ ] Set up staging/testing environment
- [ ] Ensure all 116 tests pass on current version
- [ ] Document any custom modifications
- [ ] Review all 21 AbuseIO packages for Laravel version compatibility
- [ ] Set up PHP 8.0, 8.1, and 8.2 environments for testing

---

## Phase 1: Laravel 6 → Laravel 7

### PHP Requirement
- **Current:** PHP 7.4 (compatible)
- **Required:** PHP ^7.2.5|^8.0

### 1.1 Update Dependencies

**composer.json changes:**
```json
{
    "require": {
        "laravel/framework": "^7.0",
        "laravelcollective/html": "^7.0",
        "yajra/laravel-datatables-oracle": "^9.0",
        "illuminated/helper-functions": "^7.0",
        "nunomaduro/collision": "^4.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^8.5|^9.0"
    }
}
```

### 1.2 Breaking Changes to Address

#### 1.2.1 Authentication Changes
Update `app/Http/Middleware/Authenticate.php`:
```php
// OLD (Laravel 6)
protected function redirectTo($request)
{
    if (!$request->expectsJson()) {
        return route('login');
    }
}

// NEW (Laravel 7+)
protected function redirectTo($request): ?string
{
    if (!$request->expectsJson()) {
        return route('login');
    }
    return null;
}
```

#### 1.2.2 Blade Component Changes
- `@component` syntax still works but `<x-component>` syntax introduced
- No immediate changes required

#### 1.2.3 CORS Support
Laravel 7 includes built-in CORS. Add config file:
```bash
php artisan vendor:publish --tag=cors
```

#### 1.2.4 Update Exception Handler
`app/Exceptions/Handler.php`:
```php
// Add return type
public function render($request, Throwable $exception): Response
```

### 1.3 Files to Modify

| File | Changes |
|------|---------|
| `composer.json` | Update framework to ^7.0 |
| `app/Http/Middleware/Authenticate.php` | Add return type to redirectTo() |
| `app/Exceptions/Handler.php` | Change Exception to Throwable |
| `config/cors.php` | Add new CORS configuration |

### 1.4 Testing Checklist
- [ ] Run `composer update`
- [ ] Run `php artisan migrate --pretend`
- [ ] Run `./vendor/bin/phpunit`
- [ ] Test API endpoints
- [ ] Test authentication flow
- [ ] Test all parsers

---

## Phase 2: Laravel 7 → Laravel 8

### PHP Requirement
- **Required:** PHP ^7.3|^8.0

### 2.1 Update Dependencies

**composer.json changes:**
```json
{
    "require": {
        "laravel/framework": "^8.0",
        "laravelcollective/html": "^6.2",
        "yajra/laravel-datatables-oracle": "^9.0",
        "illuminated/helper-functions": "^8.0",
        "guzzlehttp/guzzle": "^7.0",
        "nunomaduro/collision": "^5.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "fzaninotto/faker": "^1.9.1",
        "fakerphp/faker": "^1.9.1"
    }
}
```

### 2.2 Major Breaking Changes

#### 2.2.1 Model Factories (HIGH PRIORITY)

Convert class-based factories from closure-based:

**OLD** (`database/factories/UserFactory.php`):
```php
$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
    ];
});
```

**NEW**:
```php
namespace Database\Factories;

use AbuseIO\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];
    }
}
```

#### 2.2.2 Model Changes

Add `HasFactory` trait to all 20 models:

```php
// app/Models/User.php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes;
    // ...
}
```

**Models requiring updates:**
- Account, Brand, Contact, Domain, Event
- Evidence, FailedJob, Incident, Job, Netblock
- Note, Origin, Permission, PermissionRole, Role
- RoleUser, Ticket, TicketGraphPoint, User, ContactNotificationMethods

#### 2.2.3 Seeder Namespace

Update `composer.json` autoload:
```json
{
    "autoload": {
        "psr-4": {
            "AbuseIO\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    }
}
```

Rename `database/seeds` to `database/seeders`.

#### 2.2.4 Pagination Views

Laravel 8 uses Tailwind CSS by default. Keep Bootstrap:
```php
// app/Providers/AppServiceProvider.php
use Illuminate\Pagination\Paginator;

public function boot()
{
    Paginator::useBootstrap();
}
```

#### 2.2.5 Route Changes (HIGH PRIORITY)

**Current pattern** in `app/Http/routes.php`:
```php
Route::get('login', 'Auth\LoginController@showLoginForm');
Route::resource('incidents', 'IncidentsController');
```

**New pattern**:
```php
use AbuseIO\Http\Controllers\Auth\LoginController;
use AbuseIO\Http\Controllers\IncidentsController;

Route::get('login', [LoginController::class, 'showLoginForm']);
Route::resource('incidents', IncidentsController::class);
```

**Files requiring route syntax updates:**
- `app/Http/routes.php` (main routes - 207 lines)
- `app/Http/Routes/Accounts.php`
- `app/Http/Routes/Analytics.php`
- `app/Http/Routes/Contacts.php`
- `app/Http/Routes/Domains.php`
- `app/Http/Routes/Evidence.php`
- `app/Http/Routes/Gdpr.php`
- `app/Http/Routes/Incidents.php`
- `app/Http/Routes/Netblocks.php`
- `app/Http/Routes/Notes.php`
- `app/Http/Routes/Profile.php`
- `app/Http/Routes/SettingsAccounts.php`
- `app/Http/Routes/SettingsBrands.php`
- `app/Http/Routes/SettingsUsers.php`
- `app/Http/Routes/Tickets.php`
- `app/Api/Routes/*` (10 files)

#### 2.2.6 Maintenance Mode

Replace in `app/Http/Kernel.php`:
```php
// OLD
\Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,

// NEW
\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
```

#### 2.2.7 Job Batching Tables (Optional)
```bash
php artisan queue:batches-table
php artisan migrate
```

### 2.3 Files to Modify Summary

| Area | Files | Effort |
|------|-------|--------|
| Models | 20 files | Medium |
| Factories | ~10 files | High |
| Routes | 25 files | High |
| Seeders | ~5 files | Low |
| Kernel | 1 file | Low |
| Providers | 1 file | Low |

### 2.4 Testing Checklist
- [ ] Convert all factories to class-based
- [ ] Update all route definitions
- [ ] Add HasFactory to all models
- [ ] Update seeder namespaces
- [ ] Run full test suite

---

## Phase 3: Laravel 8 → Laravel 9

### PHP Requirement
- **Required:** PHP ^8.0 (MAJOR CHANGE)
- Server environment must be upgraded

### 3.1 Update Dependencies

**composer.json changes:**
```json
{
    "require": {
        "php": "^8.0",
        "laravel/framework": "^9.0",
        "laravelcollective/html": "^6.3",
        "nunomaduro/collision": "^6.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "fakerphp/faker": "^1.9.1"
    }
}
```

### 3.2 Breaking Changes

#### 3.2.1 PHP 8.0 Compatibility Updates

**Replace deprecated functionality:**

| Deprecated | Replacement |
|------------|-------------|
| `md5()` for tokens | `Str::random()` or `hash('sha256', ...)` |
| `mcrypt_*` functions | `openssl_*` functions |
| `${var}` string interpolation | `{$var}` format |

**Files using MD5 for tokens (SECURITY - Fix immediately):**
- `app/Models/Ticket.php` (line ~150)
- `app/Models/Contact.php`
- `app/Observers/TicketObserver.php`
- `app/Helpers/generatePassword.php`

#### 3.2.2 Symfony 6 Components
Laravel 9 uses Symfony 6. Ensure compatibility:
```php
// Old exception handling
catch (Exception $e)

// New - Throwable covers more cases
catch (Throwable $e)
```

#### 3.2.3 Route Group Changes

Update `app/Providers/RouteServiceProvider.php`:
```php
// OLD
Route::group(['namespace' => $this->namespace], function ($router) {
    require app_path('Http/routes.php');
});

// NEW
Route::middleware('web')
    ->group(base_path('routes/web.php'));
```

#### 3.2.4 Move Routes Files
```bash
mv app/Http/routes.php routes/web.php
mv app/Api/Routes/* routes/api/
```

#### 3.2.5 Model Accessor/Mutator Syntax (Optional but Recommended)

**OLD syntax** (still works):
```php
public function getFirstNameAttribute($value)
{
    return ucfirst($value);
}

public function setPasswordAttribute($value)
{
    $this->attributes['password'] = Hash::make($value);
}
```

**NEW syntax** (Laravel 9+):
```php
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function firstName(): Attribute
{
    return Attribute::make(
        get: fn ($value) => ucfirst($value),
    );
}

protected function password(): Attribute
{
    return Attribute::make(
        set: fn ($value) => Hash::make($value),
    );
}
```

### 3.3 Testing Checklist
- [ ] Upgrade PHP to 8.0+
- [ ] Fix all deprecation warnings
- [ ] Update string interpolation syntax
- [ ] Move route files to standard locations
- [ ] Run test suite on PHP 8.0

---

## Phase 4: Laravel 9 → Laravel 10

### PHP Requirement
- **Required:** PHP ^8.1

### 4.1 Update Dependencies

```json
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0",
        "nunomaduro/collision": "^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

### 4.2 Breaking Changes

#### 4.2.1 Minimum PHP 8.1 Features

Add return types to all methods. Example:
```php
// All controller methods
public function index(): View
public function store(Request $request): RedirectResponse
public function show(int $id): View
```

#### 4.2.2 Replace Deprecated Packages

| Current | Replacement |
|---------|-------------|
| `fzaninotto/faker` | `fakerphp/faker` |
| `kruisdraad/phpmailer` | `phpmailer/phpmailer` |
| `wpb/string-blade-compiler` | Alternative solution needed |

#### 4.2.3 Native Type Declarations

Add type hints to model properties:
```php
class User extends Authenticatable
{
    protected $table = 'users';  // Add to all models
    protected $fillable = [...]; // Already present
}
```

#### 4.2.4 Service Provider Changes

Update `app/Providers/EventServiceProvider.php`:
```php
// Remove $listen property if empty, or convert to method
protected $listen = []; // Remove if empty

// Or use shouldDiscoverEvents()
public function shouldDiscoverEvents(): bool
{
    return false;
}
```

### 4.3 Testing Configuration Update

Update `phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
</phpunit>
```

### 4.4 Testing Checklist
- [ ] Upgrade PHP to 8.1+
- [ ] Add return types to controller methods
- [ ] Update phpunit.xml configuration
- [ ] Run test suite on PHP 8.1

---

## Phase 5: Laravel 10 → Laravel 11

### PHP Requirement
- **Required:** PHP ^8.2

### 5.1 Update Dependencies

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "nunomaduro/collision": "^8.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5"
    }
}
```

### 5.2 Major Structural Changes

#### 5.2.1 Streamlined Application Structure

Laravel 11 introduces a simplified structure. New files:

**`bootstrap/app.php`** (replaces much of Kernel.php):
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \AbuseIO\Http\Middleware\Authenticate::class,
            'permission' => \AbuseIO\Http\Middleware\CheckPermission::class,
            'ash.token' => \AbuseIO\Http\Middleware\CheckAshToken::class,
            'checkaccount' => \AbuseIO\Http\Middleware\CheckAccount::class,
            'checksystemaccount' => \AbuseIO\Http\Middleware\CheckSystemAccount::class,
            'apienabled' => \AbuseIO\Http\Middleware\ApiEnabled::class,
            'checkapitoken' => \AbuseIO\Http\Middleware\CheckApiToken::class,
            'apiaccountavailable' => \AbuseIO\Http\Middleware\ApiAccountAvailable::class,
            'apisystemaccount' => \AbuseIO\Http\Middleware\ApiSystemAccount::class,
            'appendnotesubmitter' => \AbuseIO\Http\Middleware\AppendNoteSubmitter::class,
        ]);

        $middleware->web(append: [
            \AbuseIO\Http\Middleware\Locale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling
    })
    ->create();
```

#### 5.2.2 Config Files Changes

Many config files are optional in Laravel 11. Only keep customized configs.

**Required config files for AbuseIO:**
- `config/app.php` (customized)
- `config/database.php` (customized)
- `config/mail.php` (customized)
- `config/queue.php` (customized)
- `config/main.php` (custom AbuseIO config)
- `config/types.php` (custom AbuseIO config)

#### 5.2.3 Console Kernel Changes

Replace `app/Console/Kernel.php` with `routes/console.php`:
```php
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('housekeeper:run')->hourly();
Schedule::command('collector:runall')->daily();
```

#### 5.2.4 Health Route

Laravel 11 includes health endpoint:
```php
// Automatically available at /up
// Configure in bootstrap/app.php if needed
```

#### 5.2.5 Per-Second Rate Limiting

Update API rate limiting in middleware:
```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perSecond(1)->by($request->user()?->id ?: $request->ip());
});
```

### 5.3 Optional: Frontend Modernization

#### 5.3.1 Migrate to Vite

**Remove old files:**
```bash
rm gulpfile.js
rm -rf node_modules
```

**Create `vite.config.js`:**
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

**Update `package.json`:**
```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "laravel-vite-plugin": "^1.0",
        "vite": "^5.0"
    }
}
```

**Update Blade templates:**
```blade
{{-- OLD --}}
<link rel="stylesheet" type="text/css" href="{{ asset('/css/app.css') }}"/>

{{-- NEW --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### 5.4 Testing Checklist
- [ ] Upgrade PHP to 8.2+
- [ ] Restructure bootstrap/app.php
- [ ] Remove Kernel.php files (migrate to bootstrap/app.php)
- [ ] Update all config files
- [ ] Run full test suite

---

## AbuseIO Package Dependencies

### Critical Packages Requiring Updates

All 21 AbuseIO packages must be updated for Laravel 11 compatibility:

| Package | Current | Required Action |
|---------|---------|-----------------|
| `abuseio/collector-common` | 3.0.* | Update for PHP 8.2 |
| `abuseio/collector-rbl` | 3.0.* | Update for PHP 8.2 |
| `abuseio/collector-snds` | 3.0.* | Update for PHP 8.2 |
| `abuseio/hook-common` | 3.0.* | Update for PHP 8.2 |
| `abuseio/hook-delegate` | 3.0.* | Update for PHP 8.2 |
| `abuseio/hook-log` | 3.0.* | Update for PHP 8.2 |
| `abuseio/notification-common` | 3.0.* | Update for PHP 8.2 |
| `abuseio/notification-mail` | 3.0.* | Update for PHP 8.2 |
| `abuseio/parser-common` | 3.0.* | Update for PHP 8.2 |
| `abuseio/parser-*` (17 parsers) | 3.0.* | Update for PHP 8.2 |
| `abuseio/iodef` | 1.0.* | Update for PHP 8.2 |

### Third-Party Package Compatibility

| Package | Current | Laravel 11 Version |
|---------|---------|-------------------|
| `laravelcollective/html` | 6.* | Use `spatie/laravel-html` instead |
| `yajra/laravel-datatables-oracle` | 9.10.* | ^11.0 |
| `wpb/string-blade-compiler` | 6.* | **DISCONTINUED** - Find alternative |
| `barryvdh/laravel-ide-helper` | ^2.7 | ^3.0 |
| `madnest/madzipper` | ^1.0 | Check compatibility |
| `league/fractal` | 0.19.* | ^0.20 |
| `php-mime-mail-parser/php-mime-mail-parser` | 6.* | ^8.0 |

### Package Replacement Strategy

#### Replace `wpb/string-blade-compiler`
This package is discontinued. Options:
1. Use `illuminate/view` directly with custom compiler
2. Use `jenssegers/blade` for standalone Blade
3. Refactor code to avoid runtime Blade compilation

#### Replace `laravelcollective/html`
```bash
composer require spatie/laravel-html
```

Update all form usages:
```php
// OLD (Laravel Collective)
{!! Form::text('name', null, ['class' => 'form-control']) !!}

// NEW (Spatie HTML)
{{ html()->text('name')->class('form-control') }}
```

---

## Security Improvements (Do During Upgrade)

### 1. Replace MD5 Token Generation

**Files to update:**
- `app/Models/Ticket.php`
- `app/Models/Contact.php`
- `app/Observers/TicketObserver.php`
- `app/Helpers/generatePassword.php`

**Replace:**
```php
// OLD (INSECURE)
$token = md5(mt_rand());

// NEW (SECURE)
use Illuminate\Support\Str;
$token = Str::random(64);
// or
$token = hash('sha256', random_bytes(32));
```

### 2. Update Session Usage

**File:** `app/Http/Middleware/Locale.php`

```php
// OLD
Session::has('locale')
Session::get('locale')
Session::put('locale', $locale)

// NEW
$request->session()->has('locale')
$request->session()->get('locale')
$request->session()->put('locale', $locale)
```

---

## Database Migrations Cleanup

### Review Migration Files

Current migration naming issues to standardize:
```
database/migrations/
├── 2014_05_11_172927_create_permissions_table.php
├── 2014_05_11_172928_create_roles_table.php
├── ... (36 total migrations)
└── 2024_xx_xx_xxxxxx_upgrade_to_laravel_11.php (new)
```

### Create Upgrade Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add any schema changes needed for Laravel 11
        // Update failed_jobs table for new format
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->string('uuid')->after('id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
```

---

## Testing Strategy

### Test Categories (116 Total Tests)

| Category | Count | Priority |
|----------|-------|----------|
| API Tests | ~40 | HIGH |
| Console Command Tests | ~50 | HIGH |
| Controller Tests | ~5 | MEDIUM |
| Middleware Tests | 1 | MEDIUM |
| Job Tests | ~3 | MEDIUM |
| Service Tests | ~2 | LOW |
| Helper Tests | ~5 | LOW |

### Upgrade Testing Protocol

1. **Before Each Phase:**
   - Run full test suite
   - Document any failing tests
   - Check coverage percentage

2. **After Each Phase:**
   - Run full test suite
   - Compare with pre-upgrade results
   - Fix any regressions

3. **Manual Testing Checklist:**
   - [ ] User login/logout
   - [ ] Dashboard rendering
   - [ ] Ticket creation and management
   - [ ] Contact management
   - [ ] Domain/Netblock management
   - [ ] Email parsing (all parsers)
   - [ ] API endpoints (all CRUD operations)
   - [ ] Console commands
   - [ ] Queue processing
   - [ ] Notifications

---

## Rollback Plan

### Per-Phase Rollback

Each phase should be:
1. Committed to a separate branch
2. Tagged with version number
3. Documented with working state

### Emergency Rollback Steps

```bash
# If upgrade fails
git checkout previous-working-tag
composer install
php artisan migrate:rollback --step=1
php artisan cache:clear
php artisan config:clear
```

---

## Timeline Estimate

| Phase | Description | Effort |
|-------|-------------|--------|
| **Phase 1** | Laravel 6 → 7 | Low |
| **Phase 2** | Laravel 7 → 8 | High (routes, factories) |
| **Phase 3** | Laravel 8 → 9 | Medium (PHP 8.0) |
| **Phase 4** | Laravel 9 → 10 | Medium (PHP 8.1) |
| **Phase 5** | Laravel 10 → 11 | High (structure changes) |
| **Packages** | Update 21 AbuseIO packages | High |
| **Testing** | Full regression testing | Medium |
| **Frontend** | Vite migration (optional) | Medium |

**Total:** Significant effort - recommend dedicated sprint

---

## Recommended Approach

### Option A: Incremental Upgrade (Recommended)
Upgrade one major version at a time, testing thoroughly between each.

**Pros:**
- Lower risk per change
- Easier to identify issues
- Can ship intermediate versions

**Cons:**
- More total effort
- Longer timeline

### Option B: Direct Upgrade
Jump directly to Laravel 11 using Laravel Shift or similar tools.

**Pros:**
- Faster if it works
- Less total effort

**Cons:**
- Higher risk
- Harder to debug issues
- May miss subtle breaking changes

### Recommendation
Use **Option A** (Incremental) for AbuseIO due to:
- 21 custom packages that need compatibility testing
- Complex routing structure
- Critical production system
- Need to maintain stability

---

## Helpful Resources

- [Laravel Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
- [Laravel Shift](https://laravelshift.com/) - Automated upgrade tool
- [Rector PHP](https://github.com/rectorphp/rector) - Automated refactoring
- [PHPStan](https://phpstan.org/) - Static analysis for PHP 8 compatibility
- [Laravel 11 Release Notes](https://laravel.com/docs/11.x/releases)

---

## Appendix A: File Changes Summary

### High-Priority Files

| File | Phase | Changes Required |
|------|-------|------------------|
| `composer.json` | All | Update dependencies |
| `app/Http/Kernel.php` | 2, 5 | Middleware, then remove |
| `app/Http/routes.php` | 2 | Route syntax, move to routes/ |
| `app/Providers/RouteServiceProvider.php` | 2, 3 | Route configuration |
| `app/Exceptions/Handler.php` | 1, 2, 5 | Exception handling |
| `bootstrap/app.php` | 5 | Complete restructure |
| `phpunit.xml` | 4 | New format |
| 20 Model files | 2 | Add HasFactory trait |
| ~25 Route files | 2 | Controller class syntax |

### Files to Create

| File | Phase | Purpose |
|------|-------|---------|
| `config/cors.php` | 1 | CORS configuration |
| `routes/web.php` | 3 | Main routes |
| `routes/api.php` | 3 | API routes |
| `routes/console.php` | 5 | Scheduled commands |
| `vite.config.js` | 5 | Frontend build (optional) |

### Files to Remove

| File | Phase | Reason |
|------|-------|--------|
| `app/Console/Kernel.php` | 5 | Moved to bootstrap/app.php |
| `app/Http/Kernel.php` | 5 | Moved to bootstrap/app.php |
| `gulpfile.js` | 5 | Replaced by Vite |

---

## Appendix B: AbuseIO Package Upgrade Checklist

For each of the 21 AbuseIO packages:

- [ ] Check GitHub repository for updates
- [ ] Review PHP 8.2 compatibility
- [ ] Update composer.json requirements
- [ ] Run package tests
- [ ] Update for Laravel 11 service providers (if applicable)

---

*This roadmap was generated based on analysis of the AbuseIO codebase as of December 2025.*
