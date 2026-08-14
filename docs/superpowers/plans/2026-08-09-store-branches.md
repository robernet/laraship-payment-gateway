# Store Branches Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Introduce a `Branch` layer between `Store` and its POS terminals/operators, and make the Branch the operational scope for POS auth and shifts.

**Architecture:** `Store` becomes a merchant/company container; a new `Branch` (belongs to one store) owns POS terminals, operators, and shifts. All schema changes are additive (fresh start — no backfill). API auth (`/pos/login`, `/pos/device-login`) and `POST /shifts` key off `branch_id` and a synthetic `branch:{hashid}` token ability instead of `store:`. Admin panel gains a Branch CRUD resource; the POS + Operators panels move from the Store show page to the Branch show page.

**Tech Stack:** Laravel 11 / PHP 8.3, Laraship module system, Sanctum tokens, Yajra DataTables, Fractal transformers, PHPUnit (real MySQL test DB).

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-09-store-branches-operational-scope-design.md`. Every task's requirements implicitly include it.
- Identifiers cross the API as **Hashid strings** — never raw BIGINT. Use `hashids()->encode/decode`, `$model->hashed_id`, `Model::findByHash($hash)`.
- Run tests with `./vendor/bin/phpunit tests/Feature/PaymentGateway` (there is **no** `php artisan test` in this environment). Filter one test: `./vendor/bin/phpunit --filter=testMethodName tests/Feature/PaymentGateway/<File>.php`.
- Tests hit a **real** MySQL DB (`laraship_payment_gateway_test`); `LazilyRefreshDatabase` runs migrations, **not** seeders. Admin permissions are therefore not seeded in tests — admin pages are verified manually as the super user (id 1), who bypasses permission checks.
- `store_id` stays `NOT NULL` on `paymentgateway_pos` and `paymentgateway_shifts`; new rows set `store_id = branch.store_id` (denormalized copy). Mark that line with the `// ponytail:` comment from the spec.
- Legacy `paymentgateway_operator_stores` + `OperatorStore` are left untouched. New assignments use a new `paymentgateway_operator_branches` pivot + `OperatorBranch` model.
- Commit after each task. Conventional commits, scope `PaymentGateway`. Do **not** `git add -A` — stage only the files each task lists (the working tree carries unrelated uncommitted changes). Append the two trailers from the repo's commit convention.
- Follow existing module conventions exactly (clone the Store/Pos siblings). Do not add dependencies or restructure unrelated code.

---

### Task 1: Branch schema, models, and relations

**Files:**
- Create: `backend/Corals/modules/PaymentGateway/database/migrations/2026_08_09_100001_create_paymentgateway_branches_table.php`
- Create: `backend/Corals/modules/PaymentGateway/database/migrations/2026_08_09_100002_create_paymentgateway_operator_branches_table.php`
- Create: `backend/Corals/modules/PaymentGateway/database/migrations/2026_08_09_100003_add_branch_id_to_paymentgateway_pos_table.php`
- Create: `backend/Corals/modules/PaymentGateway/database/migrations/2026_08_09_100004_add_branch_id_to_paymentgateway_shifts_table.php`
- Create: `backend/Corals/modules/PaymentGateway/Models/Branch.php`
- Create: `backend/Corals/modules/PaymentGateway/Models/OperatorBranch.php`
- Create: `backend/Corals/modules/PaymentGateway/Transformers/BranchTransformer.php`
- Create: `backend/Corals/modules/PaymentGateway/Transformers/BranchPresenter.php`
- Create: `backend/Corals/modules/PaymentGateway/Services/BranchService.php`
- Modify: `backend/Corals/modules/PaymentGateway/Models/Store.php` (add `branches()`)
- Modify: `backend/Corals/modules/PaymentGateway/Models/Pos.php` (add `branch()`)
- Modify: `backend/Corals/modules/PaymentGateway/Models/Shift.php` (add `branch()`)
- Modify: `backend/Corals/modules/PaymentGateway/config/paymentgateway.php` (add `branch` entry)
- Test: `backend/tests/Feature/PaymentGateway/BranchModelTest.php`

**Interfaces:**
- Produces:
  - `Branch` model (`paymentgateway_branches`): `store()` belongsTo Store; `terminals()` hasMany Pos on `branch_id`; `operators()` belongsToMany User through `paymentgateway_operator_branches`. Traits give `hashed_id`, `findByHash()`, `getHashedIdAttribute()`, `getShowURL()`, `getIdentifier()`.
  - `OperatorBranch` model (`paymentgateway_operator_branches`).
  - `Store::branches()` hasMany Branch; `Pos::branch()` belongsTo Branch; `Shift::branch()` belongsTo Branch.
  - config key `paymentgateway.models.branch` → `resource_url` = `branches`, `presenter` = `BranchPresenter::class`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\OperatorBranch;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BranchModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    // Same dynamic-module bootstrap override PosDeviceAuthTest uses.
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';

        try {
            \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..')->load();

            $pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']),
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD']
            );
            $stmt = $pdo->prepare("
                INSERT INTO modules (code, enabled, installed, load_order, provider, folder, type, created_at, updated_at)
                VALUES ('corals-paymentgateway', 1, 1, 0, :provider, 'PaymentGateway', 'module', NOW(), NOW())
                ON DUPLICATE KEY UPDATE enabled = 1, provider = VALUES(provider)
            ");
            $stmt->execute(['provider' => \Corals\Modules\PaymentGateway\PaymentGatewayServiceProvider::class]);
        } catch (\PDOException $e) {
            // modules table doesn't exist yet - skip.
        }

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    #[Test]
    public function a_branch_belongs_to_a_store_and_owns_terminals_and_operators()
    {
        $store = Store::create(['name' => 'Acme Co']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Downtown']);

        $pos = Pos::create([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'name' => 'Till 1',
            'code' => 'BR-TILL-1',
        ]);

        $operator = User::create([
            'name' => 'Op One',
            'email' => 'op-one@example.test',
            'password' => 'secret-password',
        ]);
        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id]);

        $this->assertTrue($store->branches->contains($branch));
        $this->assertSame($store->id, $branch->store->id);
        $this->assertTrue($branch->terminals->contains($pos));
        $this->assertTrue($branch->operators->pluck('id')->contains($operator->id));
    }
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `./vendor/bin/phpunit --filter=a_branch_belongs_to_a_store_and_owns_terminals_and_operators tests/Feature/PaymentGateway/BranchModelTest.php`
Expected: FAIL — class `Branch` not found / table `paymentgateway_branches` missing.

- [ ] **Step 3: Create the four migrations**

`2026_08_09_100001_create_paymentgateway_branches_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('paymentgateway_stores');
            $table->string('name');
            $table->text('properties')->nullable();
            $table->auditable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_branches');
    }
}
```

`2026_08_09_100002_create_paymentgateway_operator_branches_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentgatewayOperatorBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('paymentgateway_operator_branches', function (Blueprint $table) {
            $table->id();
            // users.id is a plain INT (unsigned), not BIGINT - matches operator_stores.
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreignId('branch_id')->constrained('paymentgateway_branches');
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentgateway_operator_branches');
    }
}
```

`2026_08_09_100003_add_branch_id_to_paymentgateway_pos_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToPaymentgatewayPosTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_pos', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('store_id')->constrained('paymentgateway_branches');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_pos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
}
```

`2026_08_09_100004_add_branch_id_to_paymentgateway_shifts_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToPaymentgatewayShiftsTable extends Migration
{
    public function up()
    {
        Schema::table('paymentgateway_shifts', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('store_id')->constrained('paymentgateway_branches');
        });
    }

    public function down()
    {
        Schema::table('paymentgateway_shifts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
}
```

- [ ] **Step 4: Create `Branch.php`**

```php
<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;
use Corals\Modules\PaymentGateway\Traits\ApiHashTrait;
use Corals\User\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends BaseModel
{
    use ApiHashTrait;
    use PresentableTrait;
    use LogsActivity;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'paymentgateway.models.branch';

    protected $casts = [
        'properties' => 'json',
    ];

    protected $table = 'paymentgateway_branches';

    protected $guarded = ['id'];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function terminals()
    {
        return $this->hasMany(Pos::class, 'branch_id');
    }

    /**
     * Users allowed to log in at this branch via POST /pos/login, through the
     * paymentgateway_operator_branches pivot.
     */
    public function operators()
    {
        return $this->belongsToMany(User::class, 'paymentgateway_operator_branches', 'branch_id', 'user_id')
            ->withTimestamps();
    }
}
```

- [ ] **Step 5: Create `OperatorBranch.php`**

```php
<?php

namespace Corals\Modules\PaymentGateway\Models;

use Corals\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: which branches a POS operator is allowed to log in against.
 * Internal auth-scoping table, not an API resource - plain Eloquent, not BaseModel.
 */
class OperatorBranch extends Model
{
    protected $table = 'paymentgateway_operator_branches';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
```

- [ ] **Step 6: Create `BranchTransformer.php` and `BranchPresenter.php`**

`BranchTransformer.php`:
```php
<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\Branch;

class BranchTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.branch.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param Branch $branch
     * @return array
     * @throws \Throwable
     */
    public function transform(Branch $branch)
    {
        $transformedArray = [
            'id' => $branch->hashed_id,
            'name' => HtmlElement('a', ['href' => $branch->getShowURL()], $branch->name),
            'store' => $branch->store?->name,
            'created_at' => format_date($branch->created_at),
            'updated_at' => format_date($branch->updated_at),
            'action' => $this->actions($branch),
        ];

        return parent::transformResponse($transformedArray);
    }
}
```

`BranchPresenter.php`:
```php
<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\FractalPresenter;

class BranchPresenter extends FractalPresenter
{
    /**
     * @return BranchTransformer
     */
    public function getTransformer($extras = [])
    {
        return new BranchTransformer($extras);
    }
}
```

- [ ] **Step 7: Create `BranchService.php`**

```php
<?php

namespace Corals\Modules\PaymentGateway\Services;

use Corals\Foundation\Services\BaseServiceClass;

class BranchService extends BaseServiceClass
{
}
```

- [ ] **Step 8: Wire relations + config**

Add to `Store.php` (after `operators()`):
```php
    public function branches()
    {
        return $this->hasMany(Branch::class, 'store_id');
    }
```

Add to `Pos.php` (after `store()`):
```php
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
```

Add to `Shift.php` (after `store()`):
```php
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
```

Add to `config/paymentgateway.php` `models` array (keep alphabetical-ish, place before `invoice`):
```php
        'branch' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\BranchPresenter::class,
            'resource_url' => 'branches',
        ],
```

- [ ] **Step 9: Run the test, verify it passes**

Run: `./vendor/bin/phpunit --filter=a_branch_belongs_to_a_store_and_owns_terminals_and_operators tests/Feature/PaymentGateway/BranchModelTest.php`
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/database/migrations/2026_08_09_100001_create_paymentgateway_branches_table.php \
        backend/Corals/modules/PaymentGateway/database/migrations/2026_08_09_100002_create_paymentgateway_operator_branches_table.php \
        backend/Corals/modules/PaymentGateway/database/migrations/2026_08_09_100003_add_branch_id_to_paymentgateway_pos_table.php \
        backend/Corals/modules/PaymentGateway/database/migrations/2026_08_09_100004_add_branch_id_to_paymentgateway_shifts_table.php \
        backend/Corals/modules/PaymentGateway/Models/Branch.php \
        backend/Corals/modules/PaymentGateway/Models/OperatorBranch.php \
        backend/Corals/modules/PaymentGateway/Transformers/BranchTransformer.php \
        backend/Corals/modules/PaymentGateway/Transformers/BranchPresenter.php \
        backend/Corals/modules/PaymentGateway/Services/BranchService.php \
        backend/Corals/modules/PaymentGateway/Models/Store.php \
        backend/Corals/modules/PaymentGateway/Models/Pos.php \
        backend/Corals/modules/PaymentGateway/Models/Shift.php \
        backend/Corals/modules/PaymentGateway/config/paymentgateway.php \
        backend/tests/Feature/PaymentGateway/BranchModelTest.php
git commit -m "feat(PaymentGateway): add Branch model + schema under Store"
```

---

### Task 2: Operator login scoped to branch (`POST /pos/login`)

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/Http/Controllers/API/PosAuthController.php` (`login`)
- Test: `backend/tests/Feature/PaymentGateway/BranchAuthTest.php` (new)

**Interfaces:**
- Consumes: `Branch`, `OperatorBranch` (Task 1).
- Produces: `/pos/login` accepts `{email, password, branch_id}`; issues token abilities `payment:lookup`, `payment:collect`, `transaction:read-own`, `shift:manage`, `branch:{hashid}`; response `{token, abilities, branch_id, store_id}`.

- [ ] **Step 1: Write the failing test**

Create `BranchAuthTest.php` with the same `createApplication()` + `apiUrl()` helpers as `BranchModelTest`/`PosDeviceAuthTest` (copy them verbatim), plus:

```php
    #[Test]
    public function an_operator_assigned_to_a_branch_logs_in_and_gets_a_branch_scoped_token()
    {
        $store = Store::create(['name' => 'Login Co']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Branch A']);

        $operator = User::create([
            'name' => 'Assigned Op',
            'email' => 'assigned-op@example.test',
            'password' => 'secret-password',
        ]);
        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id]);

        $response = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'assigned-op@example.test',
            'password' => 'secret-password',
            'branch_id' => $branch->getHashedIdAttribute(),
        ]);

        $response->assertStatus(200);
        $this->assertSame($branch->getHashedIdAttribute(), $response->json('data.branch_id'));
        $this->assertContains('branch:' . $branch->getHashedIdAttribute(), $response->json('data.abilities'));
    }

    #[Test]
    public function an_operator_not_assigned_to_the_branch_is_rejected()
    {
        $store = Store::create(['name' => 'Reject Co']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Branch B']);

        User::create([
            'name' => 'Unassigned Op',
            'email' => 'unassigned-op@example.test',
            'password' => 'secret-password',
        ]);

        $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'unassigned-op@example.test',
            'password' => 'secret-password',
            'branch_id' => $branch->getHashedIdAttribute(),
        ])->assertStatus(422);
    }
```

Import `Branch`, `OperatorBranch`, `Store`, `User` at the top.

- [ ] **Step 2: Run it, verify it fails**

Run: `./vendor/bin/phpunit --filter=an_operator_assigned_to_a_branch_logs_in_and_gets_a_branch_scoped_token tests/Feature/PaymentGateway/BranchAuthTest.php`
Expected: FAIL — login still expects `store_id`, no `branch_id` in response.

- [ ] **Step 3: Rewrite `login()`**

Replace the body of `PosAuthController@login` with (swap imports: drop `OperatorStore`/`Store`, add `Branch`/`OperatorBranch`):
```php
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'branch_id' => ['required'],
        ]);

        $user = User::query()->where('email', $request->get('email'))->first();

        if (!$user || !Hash::check($request->get('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
        }

        $branch = Branch::findByHash($request->get('branch_id'));

        if (!$branch) {
            throw ValidationException::withMessages(['branch_id' => [trans('Corals::messages.errors.not_found')]]);
        }

        $isAssigned = OperatorBranch::query()
            ->where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->exists();

        if (!$isAssigned) {
            throw ValidationException::withMessages(['branch_id' => ['This operator is not assigned to the requested branch.']]);
        }

        $abilities = [
            'payment:lookup',
            'payment:collect',
            'transaction:read-own',
            'shift:manage',
            'branch:' . $branch->getHashedIdAttribute(),
        ];

        $token = $user->createToken('pos-operator', $abilities);

        return apiResponse([
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'branch_id' => $branch->getHashedIdAttribute(),
            'store_id' => $branch->store?->getHashedIdAttribute(),
        ]);
    }
```
Update the method docblock: `store:{hashid}` → `branch:{hashid}`.

- [ ] **Step 4: Run both tests, verify pass**

Run: `./vendor/bin/phpunit tests/Feature/PaymentGateway/BranchAuthTest.php`
Expected: both PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/Http/Controllers/API/PosAuthController.php \
        backend/tests/Feature/PaymentGateway/BranchAuthTest.php
git commit -m "feat(PaymentGateway): scope operator login to a branch"
```

---

### Task 3: Device login carries a branch ability (`POST /pos/device-login`)

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/Http/Controllers/API/PosAuthController.php` (`deviceLogin`)
- Test: `backend/tests/Feature/PaymentGateway/BranchAuthTest.php` (add a case)

**Interfaces:**
- Consumes: `Pos::branch()` (Task 1).
- Produces: `/pos/device-login` token abilities include `branch:{hashid}` (from `pos.branch`) **and** `pos:{hashid}`; response `{token, abilities, branch_id, store_id}`. A POS with no branch is rejected (`422`).

- [ ] **Step 1: Write the failing test** (add to `BranchAuthTest`)

```php
    #[Test]
    public function device_login_issues_a_branch_scoped_token()
    {
        $store = Store::create(['name' => 'Device Co']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Device Branch']);

        $pos = Pos::create([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'name' => 'Device Till',
            'code' => 'DEV-TILL-1',
        ]);
        $plainSecret = $pos->regenerateDeviceSecret();

        $response = $this->postJson($this->apiUrl('pos/device-login'), [
            'code' => 'DEV-TILL-1',
            'device_secret' => $plainSecret,
        ]);

        $response->assertStatus(200);
        $this->assertSame($branch->getHashedIdAttribute(), $response->json('data.branch_id'));
        $this->assertContains('branch:' . $branch->getHashedIdAttribute(), $response->json('data.abilities'));
        $this->assertContains('pos:' . $pos->getHashedIdAttribute(), $response->json('data.abilities'));
    }
```

Import `Pos` at the top.

- [ ] **Step 2: Run it, verify it fails**

Run: `./vendor/bin/phpunit --filter=device_login_issues_a_branch_scoped_token tests/Feature/PaymentGateway/BranchAuthTest.php`
Expected: FAIL — no `branch_id` / `branch:` ability.

- [ ] **Step 3: Rewrite `deviceLogin()`** — replace the `$store = $pos->store;` block onward with:
```php
        $branch = $pos->branch;

        if (!$branch) {
            throw ValidationException::withMessages(['code' => ['This terminal is not assigned to a branch.']]);
        }

        $abilities = [
            'payment:lookup',
            'payment:collect',
            'transaction:read-own',
            'shift:manage',
            'branch:' . $branch->getHashedIdAttribute(),
            'pos:' . $pos->getHashedIdAttribute(),
        ];

        $token = $pos->createToken('pos-device', $abilities);

        return apiResponse([
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
            'branch_id' => $branch->getHashedIdAttribute(),
            'store_id' => $branch->store?->getHashedIdAttribute(),
        ]);
```
Update the method docblock: `store:{hashid}` → `branch:{hashid}`.

- [ ] **Step 4: Run it, verify it passes**

Run: `./vendor/bin/phpunit --filter=device_login_issues_a_branch_scoped_token tests/Feature/PaymentGateway/BranchAuthTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/Http/Controllers/API/PosAuthController.php \
        backend/tests/Feature/PaymentGateway/BranchAuthTest.php
git commit -m "feat(PaymentGateway): device login issues a branch-scoped token"
```

---

### Task 4: Open shift against a branch (`POST /shifts`)

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/Http/Requests/ShiftRequest.php` (`store_id` → `branch_id`)
- Modify: `backend/Corals/modules/PaymentGateway/Http/Controllers/API/ShiftsController.php` (`store`)
- Modify: `backend/Corals/modules/PaymentGateway/Transformers/API/ShiftTransformer.php` (add `branch_id`)
- Modify: `backend/tests/Feature/PaymentGateway/PosDeviceAuthTest.php` (branch-scope the existing flow)

**Interfaces:**
- Consumes: `branch:{hashid}` token ability (Tasks 2–3), `Branch` (Task 1).
- Produces: `POST /shifts` accepts `{branch_id}`; requires a matching `branch:{hashid}` ability (`403` otherwise); records `branch_id` + `store_id = branch.store_id`. Shift resource exposes `branch_id`.

- [ ] **Step 1: Update the existing flow test to fail against current code**

In `PosDeviceAuthTest.php`:
- Import `Branch` and `OperatorBranch`; drop the `OperatorStore` import.
- Change `makePos()` to take a `Branch` and set both ids:
```php
    private function makePos(Branch $branch, string $code): array
    {
        $pos = Pos::create([
            'store_id' => $branch->store_id,
            'branch_id' => $branch->id,
            'name' => 'Terminal ' . $code,
            'code' => $code,
        ]);
        $plainSecret = $pos->regenerateDeviceSecret();

        return [$pos, $plainSecret];
    }
```
- In each test, create a branch after the store and pass it to `makePos`, e.g.:
```php
        $store = Store::create(['name' => 'Device Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);
        [$pos, $plainSecret] = $this->makePos($branch, 'TERM-001');
```
- Change every shift-open call from `['store_id' => $store->getHashedIdAttribute()]` to `['branch_id' => $branch->getHashedIdAttribute()]`.
- In `an_operator_token_cannot_close_a_shift_opened_by_a_device`: replace `OperatorStore::create(['user_id' => $operator->id, 'store_id' => $store->id])` with `OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id])`, and the operator login body `store_id` → `branch_id` with `$branch->getHashedIdAttribute()`.
- Add an assertion in the happy-path test that the shift recorded the branch:
```php
        $this->assertSame($branch->getHashedIdAttribute(), $openShift->json('data.branch_id'));
        $this->assertDatabaseHas('paymentgateway_shifts', [
            'id' => Shift::findByHash($shiftHashid)->id,
            'branch_id' => $branch->id,
            'store_id' => $store->id,
        ]);
```

- [ ] **Step 2: Run it, verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/PaymentGateway/PosDeviceAuthTest.php`
Expected: FAIL — shift open still reads `store_id`; no `branch_id` in response.

- [ ] **Step 3: Update `ShiftRequest`** — in the `isStore()` block change `'store_id'` to `'branch_id'`:
```php
        if ($this->isStore()) {
            $rules = array_merge($rules, [
                'branch_id' => ['required'],
            ]);
        }
```

- [ ] **Step 4: Update `ShiftsController@store`** — swap `Store` for `Branch` and the ability check:
```php
            $branch = Branch::findByHash($request->get('branch_id'));

            if (!$branch) {
                throw ValidationException::withMessages(['branch_id' => [trans('Corals::messages.errors.not_found')]]);
            }

            $token = $request->user()->currentAccessToken();

            abort_if(
                !$token || !$token->can('branch:' . $branch->getHashedIdAttribute()),
                403,
                'This token is not scoped to the requested branch.'
            );

            $identity = $request->user() instanceof Pos
                ? ['pos_id' => $request->user()->id]
                : ['operator_id' => $request->user()->id];

            $shift = $this->shiftService->store($request, Shift::class, array_merge([
                'branch_id' => $branch->id,
                // ponytail: store_id denormalized from branch.store_id to avoid a
                // column-modify migration + report refactor; drop it and join
                // through branch when it becomes a maintenance burden.
                'store_id' => $branch->store_id,
                'opened_at' => now(),
            ], $identity));
```
Replace the `use ...\Models\Store;` import with `use ...\Models\Branch;`. Update the method docblock (`store` → `branch`).

- [ ] **Step 5: Update `ShiftTransformer`** — add `branch_id` above `store_id`:
```php
            'branch_id' => $shift->branch?->hashed_id,
            'store_id' => $shift->store?->hashed_id,
```

- [ ] **Step 6: Run the flow + auth tests, verify pass**

Run: `./vendor/bin/phpunit tests/Feature/PaymentGateway/PosDeviceAuthTest.php tests/Feature/PaymentGateway/BranchAuthTest.php`
Expected: all PASS.

- [ ] **Step 7: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/Http/Requests/ShiftRequest.php \
        backend/Corals/modules/PaymentGateway/Http/Controllers/API/ShiftsController.php \
        backend/Corals/modules/PaymentGateway/Transformers/API/ShiftTransformer.php \
        backend/tests/Feature/PaymentGateway/PosDeviceAuthTest.php
git commit -m "feat(PaymentGateway): open shifts against a branch"
```

---

### Task 5: Admin — Branch CRUD resource

Admin views are verified manually as the super user (id 1); there is no admin HTTP test harness in this repo. Keep every file a faithful clone of its Store sibling.

**Files:**
- Create: `backend/Corals/modules/PaymentGateway/Http/Requests/BranchRequest.php`
- Create: `backend/Corals/modules/PaymentGateway/Policies/BranchPolicy.php`
- Create: `backend/Corals/modules/PaymentGateway/DataTables/BranchesDataTable.php`
- Create: `backend/Corals/modules/PaymentGateway/Http/Controllers/BranchesController.php`
- Create: `backend/Corals/modules/PaymentGateway/resources/views/branches/index.blade.php`
- Create: `backend/Corals/modules/PaymentGateway/resources/views/branches/create_edit.blade.php`
- Create: `backend/Corals/modules/PaymentGateway/resources/views/branches/show.blade.php`
- Modify: `backend/Corals/modules/PaymentGateway/Providers/PaymentGatewayAuthServiceProvider.php`
- Modify: `backend/Corals/modules/PaymentGateway/database/seeds/PaymentGatewayPermissionsDatabaseSeeder.php`
- Modify: `backend/Corals/modules/PaymentGateway/database/seeds/PaymentGatewayMenuDatabaseSeeder.php`
- Modify: `backend/Corals/modules/PaymentGateway/routes/web.php`
- Modify: `backend/Corals/modules/PaymentGateway/routes/paymentgateway_breadcrumbs.php`
- Modify: `backend/Corals/modules/PaymentGateway/resources/lang/en/module.php`
- Modify: `backend/Corals/modules/PaymentGateway/resources/lang/en/attributes.php`

**Interfaces:**
- Consumes: `Branch`, `BranchService`, `BranchTransformer`, config `paymentgateway.models.branch` (Task 1).
- Produces: resourceful `branches` admin routes + `BranchesController` with `assignOperator` / `removeOperator` (writing `operator_branches`). Consumed by Task 6's Store/Branch views.

- [ ] **Step 1: `BranchRequest.php`** (clone of `StoreRequest`, `store` field required, `store_id` on create)

```php
<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\Branch;

class BranchRequest extends BaseRequest
{
    public function authorize()
    {
        $this->setModel(Branch::class);

        return $this->isAuthorized();
    }

    public function rules()
    {
        $this->setModel(Branch::class);
        $rules = parent::rules();

        if ($this->isUpdate() || $this->isStore()) {
            $rules = array_merge($rules, [
                'name' => ['required', 'string', 'max:255'],
            ]);
        }

        if ($this->isStore()) {
            $rules = array_merge($rules, [
                'store_id' => ['required'],
            ]);
        }

        return $rules;
    }
}
```

- [ ] **Step 2: `BranchPolicy.php`** (clone of `StorePolicy`, s/store/branch/)

```php
<?php

namespace Corals\Modules\PaymentGateway\Policies;

use Corals\Foundation\Policies\BasePolicy;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\User\Models\User;

class BranchPolicy extends BasePolicy
{
    protected $administrationPermission = 'Administrations::admin.paymentgateway';

    public function view(User $user)
    {
        return $user->can('PaymentGateway::branch.view');
    }

    public function create(User $user)
    {
        return $user->can('PaymentGateway::branch.create');
    }

    public function update(User $user, Branch $branch)
    {
        return $user->can('PaymentGateway::branch.update');
    }

    public function destroy(User $user, Branch $branch)
    {
        return $user->can('PaymentGateway::branch.delete');
    }
}
```

- [ ] **Step 3: `BranchesDataTable.php`** (clone of `StoresDataTable`, add a `store` column)

```php
<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Transformers\BranchTransformer;
use Yajra\DataTables\EloquentDataTable;

class BranchesDataTable extends BaseDataTable
{
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.branch.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new BranchTransformer());
    }

    public function query(Branch $model)
    {
        return $model->newQuery()->with('store');
    }

    protected function getColumns()
    {
        return [
            'id' => ['visible' => false],
            'name' => ['title' => trans('PaymentGateway::attributes.branch.name')],
            'store' => ['title' => trans('PaymentGateway::attributes.branch.store_id'), 'searchable' => false, 'orderable' => false],
            'created_at' => ['title' => trans('Corals::attributes.created_at')],
            'updated_at' => ['title' => trans('Corals::attributes.updated_at')],
        ];
    }
}
```

- [ ] **Step 4: `BranchesController.php`** (clone of `StoresController`; operator methods write `operator_branches`; `show` loads terminals + assignable users)

```php
<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Facades\Hashids;
use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\BranchesDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\BranchRequest;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\OperatorBranch;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Services\BranchService;
use Corals\User\Models\User;
use Illuminate\Http\Request;

class BranchesController extends BaseController
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;

        $this->resource_url = config('paymentgateway.models.branch.resource_url');

        $this->resource_model = new Branch();

        $this->title = trans('PaymentGateway::module.branch.title');
        $this->title_singular = trans('PaymentGateway::module.branch.title_singular');

        parent::__construct();
    }

    public function index(BranchRequest $request, BranchesDataTable $dataTable)
    {
        return $dataTable->render('PaymentGateway::branches.index');
    }

    public function create(BranchRequest $request)
    {
        $branch = new Branch();
        $stores = Store::query()->orderBy('name')->get();

        // Pre-select the store when arriving from a Store's Branches panel.
        $selectedStoreId = $request->get('store_id');

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::branches.create_edit')->with(compact('branch', 'stores', 'selectedStoreId'));
    }

    public function store(BranchRequest $request)
    {
        try {
            $store = Store::findByHash($request->get('store_id'));

            abort_if(!$store, 404);

            $branch = $this->branchService->store($request, Branch::class, ['store_id' => $store->id]);

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Branch::class, 'store');
        }

        return redirectTo(isset($branch) ? $branch->getShowURL() : $this->resource_url);
    }

    public function show(BranchRequest $request, Branch $branch)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $branch->getIdentifier()]),
            'showModel' => $branch,
        ]);

        // Users not yet assigned as operators here - the "Add operator" select.
        // ponytail: loads all unassigned users; add search/autocomplete when the
        // user base outgrows a plain <select>.
        $assignableUsers = User::query()
            ->whereNotIn('id', $branch->operators()->pluck('users.id'))
            ->orderBy('name')
            ->get();

        return view('PaymentGateway::branches.show')->with(compact('branch', 'assignableUsers'));
    }

    public function edit(BranchRequest $request, Branch $branch)
    {
        $stores = Store::query()->orderBy('name')->get();

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.update_title', ['title' => $branch->getIdentifier()]),
        ]);

        return view('PaymentGateway::branches.create_edit')->with(compact('branch', 'stores'));
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        try {
            $store = Store::findByHash($request->get('store_id'));

            abort_if(!$store, 404);

            $this->branchService->update($request, $branch, ['store_id' => $store->id]);

            flash(trans('Corals::messages.success.updated', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Branch::class, 'update');
        }

        return redirectTo($branch->getShowURL());
    }

    public function destroy(BranchRequest $request, Branch $branch)
    {
        try {
            $this->branchService->destroy($request, $branch);

            $message = [
                'level' => 'success',
                'message' => trans('Corals::messages.success.deleted', ['item' => $this->title_singular]),
            ];
        } catch (\Exception $exception) {
            log_exception($exception, Branch::class, 'destroy');
            $message = ['level' => 'error', 'message' => $exception->getMessage()];
        }

        return response()->json($message);
    }

    /**
     * Assign a User as an operator of this branch (lets them POST /pos/login
     * against it). Not a BranchRequest: that form request treats every POST as
     * a "create" and would check branch.create instead of branch.update.
     */
    public function assignOperator(Request $request, Branch $branch)
    {
        $this->authorize('update', $branch);

        $request->validate(['user_id' => ['required']]);

        $userId = Hashids::decode($request->get('user_id'))[0] ?? null;

        abort_if(!$userId || !User::query()->whereKey($userId)->exists(), 404);

        OperatorBranch::firstOrCreate(['user_id' => $userId, 'branch_id' => $branch->id]);

        flash(trans('PaymentGateway::module.branch.operator_assigned'))->success();

        return back();
    }

    /**
     * Remove a User's operator access to this branch.
     */
    public function removeOperator(Branch $branch, string $user)
    {
        $this->authorize('update', $branch);

        $userId = Hashids::decode($user)[0] ?? null;

        abort_if(!$userId, 404);

        $branch->operators()->detach($userId);

        flash(trans('PaymentGateway::module.branch.operator_removed'))->success();

        return back();
    }
}
```

- [ ] **Step 5: `branches/index.blade.php`** — copy `stores/index.blade.php` verbatim, then replace every `store`/`Store` token with `branch`/`Branch` (breadcrumb name `paymentgateway_branches`, DataTable variable, `create` route `branches.create`, title strings). Read `stores/index.blade.php` first and mirror its structure exactly.

- [ ] **Step 6: `branches/create_edit.blade.php`** — like `stores/create_edit.blade.php` plus a Store select. Full content:

```blade
@extends('layouts.crud.create_edit')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_branch_create_edit') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @parent
    <div class="row">
        <div class="col-md-12">
            @component('components.box')
                {!! CoralsForm::openForm($branch) !!}
                <div class="row">
                    <div class="col-md-4">
                        {!! CoralsForm::text('name', 'PaymentGateway::attributes.branch.name', true, null) !!}
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="store_id">{{ trans('PaymentGateway::attributes.branch.store_id') }} <span class="text-danger">*</span></label>
                            <select name="store_id" id="store_id" class="form-control" required>
                                <option value="">--</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->hashed_id }}"
                                        @if ((isset($selectedStoreId) && $selectedStoreId === $store->hashed_id) || (isset($branch) && $branch->store_id === $store->id)) selected @endif>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {!! CoralsForm::customFields($branch) !!}

                <div class="row">
                    <div class="col-md-12">
                        {!! CoralsForm::formButtons() !!}
                    </div>
                </div>
                {!! CoralsForm::closeForm($branch) !!}
            @endcomponent
        </div>
    </div>
@endsection

@section('js')
@endsection
```

- [ ] **Step 7: `branches/show.blade.php`** — the branch summary panel only (POS + Operators panels are added in Task 6). Full content:

```blade
@extends('layouts.crud.show')

@section('content_header')
    @component('components.content_header')
        @slot('page_title')
            {{ $title_singular }}
        @endslot
        @slot('breadcrumb')
            {{ Breadcrumbs::render('paymentgateway_branch_show') }}
        @endslot
    @endcomponent
@endsection

@section('content')
    @component('components.box')
        <div class="row">
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.branch.name') }}:</strong> {{ $branch->name }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>{{ trans('PaymentGateway::attributes.branch.store_id') }}:</strong> {{ $branch->store?->name }}</p>
            </div>
        </div>
    @endcomponent
@endsection
```

- [ ] **Step 8: Register the policy** — in `PaymentGatewayAuthServiceProvider`, add the import and map:
```php
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Policies\BranchPolicy;
```
```php
        Branch::class => BranchPolicy::class,
```

- [ ] **Step 9: Permissions seeder** — add `'branch'` to `$models`:
```php
        $models = ['store', 'branch', 'pos', 'issuer', 'invoice', 'payment_reference', 'transaction', 'shift'];
```

- [ ] **Step 10: Menu seeder** — add a Branches child after Stores (mirrors the Stores block):
```php
                [
                    'parent_id' => $paymentgateway_menu_id,
                    'key' => null,
                    'url' => config('paymentgateway.models.branch.resource_url'),
                    'active_menu_url' => config('paymentgateway.models.branch.resource_url') . '*',
                    'name' => 'Branches',
                    'description' => 'Branches List Menu Item',
                    'icon' => 'fa fa-sitemap',
                    'target' => null, 'roles' => '["1"]',
                    'order' => 0,
                ],
```

- [ ] **Step 11: Routes** — in `routes/web.php`, add above the `pos` resource:
```php
    Route::resource('branches', 'BranchesController');
    Route::post('branches/{branch}/operators', 'BranchesController@assignOperator')->name('paymentgateway.branches.operators.assign');
    Route::delete('branches/{branch}/operators/{user}', 'BranchesController@removeOperator')->name('paymentgateway.branches.operators.remove');
```

- [ ] **Step 12: Breadcrumbs** — add to `paymentgateway_breadcrumbs.php`:
```php
//Branch
Breadcrumbs::register('paymentgateway_branches', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.branch.title'), url(config('paymentgateway.models.branch.resource_url')));
});

Breadcrumbs::register('paymentgateway_branch_create_edit', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_branches');
    $breadcrumbs->push(view()->shared('title_singular'));
});

Breadcrumbs::register('paymentgateway_branch_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_branches');
    $breadcrumbs->push(view()->shared('title_singular'));
});
```

- [ ] **Step 13: Lang** — add a `branch` block to `module.php`:
```php
    'branch' => [
        'title' => 'Branches',
        'title_singular' => 'Branch',
        'operators' => 'Operators',
        'add_operator' => 'Add operator',
        'operator_assigned' => 'Operator assigned to this branch.',
        'operator_removed' => 'Operator removed from this branch.',
    ],
```
and a `branch` block to `attributes.php`:
```php
    'branch' => [
        'name' => 'Name',
        'store_id' => 'Store',
    ],
```

- [ ] **Step 14: Manual smoke**

Ensure branch permissions exist for a non-super user, or just test as the super user (id 1, who bypasses permission checks). Start the app, log into admin, visit `/branches`:
- List loads.
- "Create Branch" → pick a store, name it, save → lands on the branch show page.
- Branch appears in the list with its store.

- [ ] **Step 15: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/Http/Requests/BranchRequest.php \
        backend/Corals/modules/PaymentGateway/Policies/BranchPolicy.php \
        backend/Corals/modules/PaymentGateway/DataTables/BranchesDataTable.php \
        backend/Corals/modules/PaymentGateway/Http/Controllers/BranchesController.php \
        backend/Corals/modules/PaymentGateway/resources/views/branches \
        backend/Corals/modules/PaymentGateway/Providers/PaymentGatewayAuthServiceProvider.php \
        backend/Corals/modules/PaymentGateway/database/seeds/PaymentGatewayPermissionsDatabaseSeeder.php \
        backend/Corals/modules/PaymentGateway/database/seeds/PaymentGatewayMenuDatabaseSeeder.php \
        backend/Corals/modules/PaymentGateway/routes/web.php \
        backend/Corals/modules/PaymentGateway/routes/paymentgateway_breadcrumbs.php \
        backend/Corals/modules/PaymentGateway/resources/lang/en/module.php \
        backend/Corals/modules/PaymentGateway/resources/lang/en/attributes.php
git commit -m "feat(PaymentGateway): admin Branch CRUD under Store"
```

---

### Task 6: Move POS + Operators panels to the Branch; reparent Store show page & POS create

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/resources/views/branches/show.blade.php` (add POS + Operators panels)
- Modify: `backend/Corals/modules/PaymentGateway/resources/views/stores/show.blade.php` (remove POS + Operators panels; add Branches panel)
- Modify: `backend/Corals/modules/PaymentGateway/Http/Controllers/StoresController.php` (drop operator methods + `assignableUsers`; load branches)
- Modify: `backend/Corals/modules/PaymentGateway/routes/web.php` (drop store operator routes)
- Modify: `backend/Corals/modules/PaymentGateway/Http/Controllers/PosController.php` (create/store/update under `branch_id`)
- Modify: `backend/Corals/modules/PaymentGateway/resources/views/pos/create_edit.blade.php` (branch select instead of store select)

**Interfaces:**
- Consumes: `BranchesController` operator routes (Task 5), `Pos::branch()` (Task 1).
- Produces: POS created with `branch_id` + `store_id = branch.store_id`; operator assignment lives on the Branch page.

- [ ] **Step 1: Branch show — add POS + Operators panels**

Append to `branches/show.blade.php` inside `@section('content')`, after the summary box. This is the panel markup moved from `stores/show.blade.php`, retargeted to branch routes. Add the device-secret flash at the top of the section:
```blade
    @if (session('device_secret'))
        <div class="alert alert-warning">
            <strong>Device secret:</strong> <code>{{ session('device_secret') }}</code>
            <br>Copy this now - it will not be shown again.
        </div>
    @endif
```
POS panel (create link passes `branch_id`):
```blade
    @component('components.box')
        <div class="row mb-3">
            <div class="col-md-8">
                <h4 class="d-inline">{{ trans('PaymentGateway::module.pos.title') }}</h4>
            </div>
            <div class="col-md-4 text-right">
                @can('create', \Corals\Modules\PaymentGateway\Models\Pos::class)
                    <a href="{{ route('pos.create', ['branch_id' => $branch->hashed_id]) }}"
                       class="btn btn-sm btn-primary">
                        {{ trans('Corals::labels.create_title', ['title' => trans('PaymentGateway::module.pos.title_singular')]) }}
                    </a>
                @endcan
            </div>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.pos.name') }}</th>
                    <th>{{ trans('PaymentGateway::attributes.pos.code') }}</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branch->terminals()->orderBy('name')->get() as $terminal)
                    <tr>
                        <td>{{ $terminal->name }}</td>
                        <td>{{ $terminal->code }}</td>
                        <td class="text-right">
                            @can('update', $terminal)
                                <form method="POST" class="d-inline"
                                      action="{{ route('paymentgateway.pos.regenerate_secret', $terminal->hashed_id) }}"
                                      onsubmit="return confirm('Regenerate this device\'s secret? The old one stops working immediately.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning">Regenerate device secret</button>
                                </form>
                            @endcan
                            @can('destroy', $terminal)
                                <form method="POST" class="d-inline"
                                      action="{{ route('pos.destroy', $terminal->hashed_id) }}"
                                      onsubmit="return confirm('Delete this terminal?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No terminals yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
```
Operators panel (branch routes + `$branch`):
```blade
    @component('components.box')
        <div class="row mb-3">
            <div class="col-md-12">
                <h4 class="d-inline">{{ trans('PaymentGateway::module.branch.operators') }}</h4>
            </div>
        </div>

        @can('update', $branch)
            <form method="POST" action="{{ route('paymentgateway.branches.operators.assign', $branch->hashed_id) }}"
                  class="form-inline mb-3">
                @csrf
                <select name="user_id" class="form-control mr-2" required>
                    <option value="">--</option>
                    @foreach ($assignableUsers as $user)
                        <option value="{{ \Corals\Foundation\Facades\Hashids::encode($user->id) }}">
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">
                    {{ trans('PaymentGateway::module.branch.add_operator') }}
                </button>
            </form>
        @endcan

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.pos.name') }}</th>
                    <th>Email</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branch->operators()->orderBy('name')->get() as $operator)
                    <tr>
                        <td>{{ $operator->name }}</td>
                        <td>{{ $operator->email }}</td>
                        <td class="text-right">
                            @can('update', $branch)
                                <form method="POST" class="d-inline"
                                      action="{{ route('paymentgateway.branches.operators.remove', [$branch->hashed_id, \Corals\Foundation\Facades\Hashids::encode($operator->id)]) }}"
                                      onsubmit="return confirm('Remove this operator from the branch?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No operators yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
```

- [ ] **Step 2: Store show — replace POS + Operators panels with a Branches panel**

Rewrite `stores/show.blade.php` `@section('content')` to keep the store name box and replace the two panels with a single Branches panel:
```blade
@section('content')
    @component('components.box')
        <div class="row">
            <div class="col-md-12">
                <p><strong>{{ trans('PaymentGateway::attributes.store.name') }}:</strong> {{ $store->name }}</p>
            </div>
        </div>
    @endcomponent

    @component('components.box')
        <div class="row mb-3">
            <div class="col-md-8">
                <h4 class="d-inline">{{ trans('PaymentGateway::module.branch.title') }}</h4>
            </div>
            <div class="col-md-4 text-right">
                @can('create', \Corals\Modules\PaymentGateway\Models\Branch::class)
                    <a href="{{ route('branches.create', ['store_id' => $store->hashed_id]) }}"
                       class="btn btn-sm btn-primary">
                        {{ trans('Corals::labels.create_title', ['title' => trans('PaymentGateway::module.branch.title_singular')]) }}
                    </a>
                @endcan
            </div>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ trans('PaymentGateway::attributes.branch.name') }}</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($store->branches()->orderBy('name')->get() as $branch)
                    <tr>
                        <td>{{ $branch->name }}</td>
                        <td class="text-right">
                            <a href="{{ $branch->getShowURL() }}" class="btn btn-sm btn-secondary">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">No branches yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent
@endsection
```
Also remove the `@if (session('device_secret'))` block from the store show page (it now lives on the branch page).

- [ ] **Step 3: StoresController — drop operator handling**

- Delete methods `assignOperator` and `removeOperator`.
- Remove the `OperatorStore` and `User` imports if now unused.
- In `show()`, remove the `$assignableUsers` query and pass only the store:
```php
    public function show(StoreRequest $request, Store $store)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $store->getIdentifier()]),
            'showModel' => $store,
        ]);

        return view('PaymentGateway::stores.show')->with(compact('store'));
    }
```

- [ ] **Step 4: Routes — drop the store operator routes**

Remove these two lines from `routes/web.php`:
```php
    Route::post('stores/{store}/operators', 'StoresController@assignOperator')->name('paymentgateway.stores.operators.assign');
    Route::delete('stores/{store}/operators/{user}', 'StoresController@removeOperator')->name('paymentgateway.stores.operators.remove');
```

- [ ] **Step 5: PosController — create/store/update under a branch**

- Replace `use ...\Models\Store;` with `use ...\Models\Branch;`.
- `create()`: load branches and read `branch_id`:
```php
    public function create(PosRequest $request)
    {
        $pos = new Pos();
        $branches = Branch::query()->with('store')->orderBy('name')->get();

        // Pre-select the branch when arriving from a Branch's POS panel.
        $selectedBranchId = $request->get('branch_id');

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::pos.create_edit')->with(compact('pos', 'branches', 'selectedBranchId'));
    }
```
- `store()`: resolve branch, set both ids:
```php
    public function store(PosRequest $request)
    {
        try {
            $branch = Branch::findByHash($request->get('branch_id'));

            abort_if(!$branch, 404);

            $deviceSecret = Str::random(40);

            $pos = $this->posService->store($request, Pos::class, [
                'branch_id' => $branch->id,
                // ponytail: store_id denormalized from branch.store_id (see spec).
                'store_id' => $branch->store_id,
                'device_secret' => Hash::make($deviceSecret),
            ]);

            session()->flash('device_secret', $deviceSecret);

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Pos::class, 'store');
        }

        return redirectTo(isset($pos) ? $pos->getShowURL() : $this->resource_url);
    }
```
- `edit()`: load branches:
```php
        $branches = Branch::query()->with('store')->orderBy('name')->get();
        // ...
        return view('PaymentGateway::pos.create_edit')->with(compact('pos', 'branches'));
```
- `update()`: resolve branch, set both ids:
```php
    public function update(PosRequest $request, Pos $pos)
    {
        try {
            $branch = Branch::findByHash($request->get('branch_id'));

            abort_if(!$branch, 404);

            $this->posService->update($request, $pos, [
                'branch_id' => $branch->id,
                'store_id' => $branch->store_id,
            ]);

            flash(trans('Corals::messages.success.updated', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Pos::class, 'update');
        }

        return redirectTo($pos->getShowURL());
    }
```

- [ ] **Step 6: POS create_edit view — branch select**

In `pos/create_edit.blade.php`, replace the store `<select>` with a branch select (label its store for context). Read the file first; swap the store-select block for:
```blade
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="branch_id">{{ trans('PaymentGateway::attributes.pos.branch_id') }} <span class="text-danger">*</span></label>
                            <select name="branch_id" id="branch_id" class="form-control" required>
                                <option value="">--</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->hashed_id }}"
                                        @if ((isset($selectedBranchId) && $selectedBranchId === $branch->hashed_id) || (isset($pos) && $pos->branch_id === $branch->id)) selected @endif>
                                        {{ $branch->name }} — {{ $branch->store?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
```

- [ ] **Step 7: PosRequest — branch_id rule**

In `PosRequest`, change any `store_id` validation rule to `branch_id`. Read `PosRequest.php` first; in the create/update rule blocks use:
```php
            'branch_id' => ['required'],
```
(Remove the `store_id` rule if present.)

- [ ] **Step 8: Lang — POS branch attribute**

Add to the `pos` block in `attributes.php`:
```php
        'branch_id' => 'Branch',
```

- [ ] **Step 9: Manual smoke**

As super user: open a Store → Branches panel shows; create a Branch → on the Branch page, add a POS (device secret banner appears once), assign an operator, remove them, delete the POS. Confirm the Store page no longer shows POS/Operators panels.

- [ ] **Step 10: Run the API suite (guards against a broken POS model wiring)**

Run: `./vendor/bin/phpunit tests/Feature/PaymentGateway`
Expected: all PASS.

- [ ] **Step 11: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/resources/views/branches/show.blade.php \
        backend/Corals/modules/PaymentGateway/resources/views/stores/show.blade.php \
        backend/Corals/modules/PaymentGateway/Http/Controllers/StoresController.php \
        backend/Corals/modules/PaymentGateway/routes/web.php \
        backend/Corals/modules/PaymentGateway/Http/Controllers/PosController.php \
        backend/Corals/modules/PaymentGateway/resources/views/pos/create_edit.blade.php \
        backend/Corals/modules/PaymentGateway/Http/Requests/PosRequest.php \
        backend/Corals/modules/PaymentGateway/resources/lang/en/attributes.php
git commit -m "feat(PaymentGateway): move POS + operators from Store to Branch"
```

---

### Task 7: Update the API contract

**Files:**
- Modify: `docs/api-contract.md`

**Interfaces:** documentation only — must match Tasks 2–4.

- [ ] **Step 1: Edit `docs/api-contract.md`**

In the **Auth (POS)** section:
- `/pos/login`: change the body to `{email, password, branch_id}`; abilities include `branch:{hashid}` (not `store:{hashid}`); `branch_id` must be a branch the operator is assigned to (`422` otherwise). Note the response carries `branch_id` + `store_id`.
- `/pos/device-login`: token carries `branch:{hashid}` (from the terminal's branch) plus `pos:{hashid}`; response carries `branch_id` + `store_id`.

In the **Shift** section:
- `POST /shifts` body: `{branch_id}` (was `store_id`); requires a `branch:{hashid}` ability matching the branch (`403` otherwise).
- Add field `branch_id` (hashid, string); keep `store_id` (hashid, string) noted as the branch's owning store (denormalized).

Add a short **Branch** note near the Shift/Store sections:
> ### Branch
> The operational unit a POS terminal and its operators belong to; a Store has many Branches. Admin-managed only — there is **no** `GET /branches` yet (a listing endpoint is deferred to the Flutter client work). `branch_id` (hashid) is supplied to `/pos/login` and `POST /shifts` as a raw parameter, exactly as `store_id` was.

- [ ] **Step 2: Commit**

```bash
git add docs/api-contract.md
git commit -m "docs(api): branch-scoped POS auth and shifts"
```

---

### Task 8: Full suite green

- [ ] **Step 1: Run the whole PaymentGateway suite**

Run: `./vendor/bin/phpunit tests/Feature/PaymentGateway`
Expected: all PASS. If anything references `store_id` on login/shift or `OperatorStore` in a still-failing test, fix per Tasks 2–4.

- [ ] **Step 2: Final commit (only if fixes were needed)**

```bash
git add backend/tests/Feature/PaymentGateway
git commit -m "test(PaymentGateway): branch-scope remaining POS auth/shift tests"
```

---

## Notes for the implementer

- **Deferred (do not build):** branch geofencing (per-branch lat/lng + 20 m radius) — captured in the spec's "Deferred follow-up" section.
- **Deferred:** the Flutter `apps/pos` change to send `branch_id`, and a `GET /branches` listing endpoint.
- If a Blade clone step says "read the sibling first," do it — mirror structure exactly rather than inventing markup.
