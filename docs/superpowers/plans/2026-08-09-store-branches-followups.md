# Store Branches Follow-ups Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the three concrete technical-debt items the final review of `feature/store-branches` surfaced but didn't block merge on: a real collect-attribution bug, two non-re-runnable seeders that can't backfill existing environments, and a raw SQL error surfaced to admins on a common delete failure.

**Architecture:** Three independent, surgical fixes against the already-shipped Branch feature (`master`, commit `a569d6e`+). No schema changes, no new endpoints. Each fix is its own task/commit.

**Tech Stack:** Laravel 11 / PHP 8.3, Laraship module system, Sanctum tokens, PHPUnit (real MySQL test DB).

## Global Constraints

- Source of these findings: the final whole-branch review of `docs/superpowers/plans/2026-08-09-store-branches.md`, conducted during that plan's execution (not a separate spec doc — the findings themselves are the requirements, reproduced verbatim per task below).
- Run tests with `./vendor/bin/phpunit tests/Feature/PaymentGateway` from `backend/` (no `php artisan test` in this environment).
- Identifiers cross the API as Hashid strings — never raw BIGINT.
- Commit after each task. Conventional commits, scope `PaymentGateway`. Stage only the files each task lists — never `git add -A`.
- Branch off `master` as `fix/store-branches-followups` (or continue on an existing branch if your human partner directs otherwise) — these are independent of the Branch feature branch, which has already merged/is in PR.

## Out of scope (reviewed and deliberately excluded — do not add tasks for these)

- **Branch geofencing** — a separate feature with its own open design questions (enforcement point, device-login applicability, missing-coordinates behavior), explicitly deferred at brainstorming, not implementation debt. Needs its own brainstorm session.
- **`ShiftTransformer` (API) N+1 on `branch`/`store`** — flagged by the reviewer as "add `->with(['branch','store'])` when a listing endpoint ships." No `GET /shifts` list endpoint exists yet; the transformer today only ever formats a single resource (`POST /shifts` / `PATCH /shifts/{id}` responses), so there is no N+1 to fix yet. Revisit when a list endpoint is added.
- **Duplicated `createApplication()` dynamic-module bootstrap override** — present in 15 test files across the whole PaymentGateway suite (`grep -rl "function createApplication" tests/Feature/PaymentGateway/`), not something the Branch feature introduced (only 2 of the 15 are new files from that branch). Extracting a shared trait is real cleanup but is module-wide tech debt, out of scope for a Branch-scoped follow-up plan.
- **Pre-deploy `store:{hashid}` Sanctum tokens 403ing at shift-open post-deploy** — not a code fix, a one-time deploy step (revoke/expire POS and operator tokens issued before this deploy). Documented here as a deploy note, not a task:
  > **Deploy note:** after deploying the Branch feature, any Sanctum token minted before the deploy (operator or device) carries a `store:{hashid}` ability and will authenticate fine but 403 on `POST /shifts`. Revoke all `PaymentGateway`-scoped personal access tokens as part of the deploy (or accept that affected users must log in again).

---

### Task 1: Collect attributes to the token's own branch, not just the latest open shift

**Problem (verbatim from the final review):** `POST /transactions` resolves the caller's open shift with `Shift::query()->whereNull('closed_at')->latest('opened_at')`, filtered only by `pos_id`/`operator_id` — never by branch. Per-branch operator assignment (shipped in the Branch feature) makes "one operator, two open shifts at two branches" a real, designed workflow: an operator can be assigned to multiple branches, log in separately at each, and have two shifts open simultaneously. A collect made with a token scoped to Branch A can currently be silently attributed to a shift at Branch B purely because it was opened later.

**Decision (confirmed):** constrain the open-shift lookup by the branch encoded in the requesting token's `branch:{hashid}` ability — the same branch the token was scoped to at login/device-login.

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/Http/Controllers/API/TransactionsController.php`
- Test: `backend/tests/Feature/PaymentGateway/CollectFlowTest.php` (append a test method; this file already exercises the collect flow and already has the `createApplication()` override, `apiUrl()` helper, and relevant imports for `Store`/`Issuer`/`PaymentReference`/`User` — add `Branch` and `OperatorBranch` imports if not already present)

**Interfaces:**
- Consumes: `Branch::findByHash()`, `$token->abilities` (Sanctum `PersonalAccessToken`'s JSON-cast abilities array), the existing `branch:{hashid}` ability format minted by `PosAuthController`.
- Produces: no new public interface — internal behavior change only.

- [ ] **Step 1: Write the failing test**

Append to `CollectFlowTest.php` (adjust the class's existing import list to include `Corals\Modules\PaymentGateway\Models\Branch` and `Corals\Modules\PaymentGateway\Models\OperatorBranch` if not already imported):

```php
    #[Test]
    public function a_collect_attributes_to_the_shift_at_the_tokens_own_branch_not_just_the_latest_open_shift()
    {
        $store = Store::create(['name' => 'Multi-Branch Co']);
        $branchA = Branch::create(['store_id' => $store->id, 'name' => 'Branch A']);
        $branchB = Branch::create(['store_id' => $store->id, 'name' => 'Branch B']);

        $operator = User::create([
            'name' => 'Roaming Operator',
            'email' => 'roaming-operator@example.test',
            'password' => 'secret-password',
        ]);
        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branchA->id]);
        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branchB->id]);

        $loginA = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'roaming-operator@example.test',
            'password' => 'secret-password',
            'branch_id' => $branchA->getHashedIdAttribute(),
        ]);
        $tokenA = $loginA->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])
            ->postJson($this->apiUrl('shifts'), ['branch_id' => $branchA->getHashedIdAttribute()])
            ->assertStatus(200);
        $shiftA = Shift::where('branch_id', $branchA->id)->where('operator_id', $operator->id)->firstOrFail();

        // Sanctum's guard memoizes the resolved user for the test's Application
        // instance - forget it before authenticating as a different token.
        $this->app['auth']->forgetGuards();

        $loginB = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'roaming-operator@example.test',
            'password' => 'secret-password',
            'branch_id' => $branchB->getHashedIdAttribute(),
        ]);
        $tokenB = $loginB->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenB])
            ->postJson($this->apiUrl('shifts'), ['branch_id' => $branchB->getHashedIdAttribute()])
            ->assertStatus(200);
        $shiftB = Shift::where('branch_id', $branchB->id)->where('operator_id', $operator->id)->firstOrFail();

        $this->assertTrue($shiftB->opened_at->gte($shiftA->opened_at));

        $issuer = Issuer::create([
            'name' => 'Multi-Branch Issuer',
            'sub_id' => 40,
            'reference_layout' => ['identifier_length' => 10],
        ]);
        $paymentReference = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'reference' => '7770400000000001',
            'status' => 'pending',
            'amount_minor' => 3000,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        // Switch back to token A (scoped to Branch A) before collecting.
        // Without the fix, whereNull('closed_at')->latest('opened_at') alone
        // would pick shift B (opened later) purely because operator_id
        // matches both shifts - regardless of which branch the token is for.
        $this->app['auth']->forgetGuards();

        $collect = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenA])->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReference->getHashedIdAttribute(),
            'amount' => 3000,
            'currency' => 'MXN',
        ]);
        $collect->assertStatus(200);

        $this->assertSame($shiftA->getHashedIdAttribute(), $collect->json('data.shift_id'));
    }
```

- [ ] **Step 2: Run it, verify it fails**

Run: `cd /home/robernet/Proyectos/laraship-clubpago/backend && ./vendor/bin/phpunit --filter=a_collect_attributes_to_the_shift_at_the_tokens_own_branch_not_just_the_latest_open_shift tests/Feature/PaymentGateway/CollectFlowTest.php`
Expected: FAIL — `$collect->json('data.shift_id')` is `$shiftB`'s hashid, not `$shiftA`'s (the bug: latest-opened wins regardless of branch).

- [ ] **Step 3: Fix `TransactionsController@store`**

Add imports:
```php
use Corals\Modules\PaymentGateway\Models\Branch;
use Illuminate\Support\Str;
```

Replace the shift-resolution block (currently `$user = $request->user();` through the `if (!$shift) { throw ... }` check) with:
```php
            $user = $request->user();

            $token = $request->user()->currentAccessToken();

            $branchAbility = collect($token?->abilities ?? [])
                ->first(fn ($ability) => Str::startsWith($ability, 'branch:'));

            $branch = $branchAbility ? Branch::findByHash(Str::after($branchAbility, 'branch:')) : null;

            if (!$branch) {
                throw ValidationException::withMessages(['shift' => ['This token is not scoped to a branch.']]);
            }

            $openShiftQuery = Shift::query()->whereNull('closed_at')->where('branch_id', $branch->id)->latest('opened_at');

            $shift = $user instanceof Pos
                ? $openShiftQuery->where('pos_id', $user->id)->first()
                : $openShiftQuery->where('operator_id', $user->id)->first();

            if (!$shift) {
                throw ValidationException::withMessages(['shift' => ['No open shift for this operator - open a shift before collecting.']]);
            }
```

- [ ] **Step 4: Run the new test, verify it passes**

Run: `./vendor/bin/phpunit --filter=a_collect_attributes_to_the_shift_at_the_tokens_own_branch_not_just_the_latest_open_shift tests/Feature/PaymentGateway/CollectFlowTest.php`
Expected: PASS.

- [ ] **Step 5: Run the full PaymentGateway suite, verify no regressions**

Run: `./vendor/bin/phpunit tests/Feature/PaymentGateway`
Expected: all green (61+ tests).

- [ ] **Step 6: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/Http/Controllers/API/TransactionsController.php \
        backend/tests/Feature/PaymentGateway/CollectFlowTest.php
git commit -m "fix(PaymentGateway): resolve collect's open shift by the token's own branch"
```

---

### Task 2: Make the permissions and menu seeders re-runnable, so existing environments can backfill Branch

**Problem (verbatim from the final review):** `PaymentGatewayPermissionsDatabaseSeeder` ends in a plain `DB::table('permissions')->insert($permissions)` — adding `'branch'` to `$models` only takes effect on a fresh seed; re-running it on an already-seeded database violates the unique index on `permissions.name`. Same for `PaymentGatewayMenuDatabaseSeeder`'s Branches menu row — re-running duplicates the whole menu tree. Net effect: an already-deployed environment has no way to backfill the new `branch.*` permissions or the Branches menu item without a fresh install.

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/database/seeds/PaymentGatewayPermissionsDatabaseSeeder.php`
- Modify: `backend/Corals/modules/PaymentGateway/database/seeds/PaymentGatewayMenuDatabaseSeeder.php`

**Interfaces:** none — internal seeder behavior only.

There is no feature test harness for seeders in this codebase (they aren't run by `LazilyRefreshDatabase`). Verify both by running each seeder command twice in a row against the local dev database and confirming the second run doesn't error and doesn't duplicate rows.

- [ ] **Step 1: Make the permissions seeder idempotent**

Replace `PaymentGatewayPermissionsDatabaseSeeder.php`'s `run()` body from the `$permissions = array_map(...)` line onward:

```php
        $permissions = array_map(function ($item) {
            return array_merge($item, [
                'guard_name' => config('auth.defaults.guard'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, $permissions);

        $existingNames = DB::table('permissions')
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('name')
            ->all();

        $newPermissions = array_values(array_filter(
            $permissions,
            fn ($permission) => !in_array($permission['name'], $existingNames, true)
        ));

        if (!empty($newPermissions)) {
            DB::table('permissions')->insert($newPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
```

- [ ] **Step 2: Verify the permissions seeder is safe to re-run**

Run: `cd /home/robernet/Proyectos/laraship-clubpago/backend && php artisan db:seed --class="Corals\Modules\PaymentGateway\database\seeds\PaymentGatewayPermissionsDatabaseSeeder" --force`
Run it a SECOND time immediately after.
Expected: both runs exit 0 (no unique-constraint error); `SELECT COUNT(*) FROM permissions WHERE name LIKE 'PaymentGateway::branch.%'` returns 6 (one per level) after either run, not 12 after the second.

- [ ] **Step 3: Make the menu seeder idempotent**

Replace the entire `run()` method in `PaymentGatewayMenuDatabaseSeeder.php`:

```php
    public function run()
    {
        $paymentgateway_menu_id = \DB::table('menus')->where('key', 'paymentgateway')->value('id');

        if (!$paymentgateway_menu_id) {
            $paymentgateway_menu_id = \DB::table('menus')->insertGetId([
                'parent_id' => 1,// admin
                'key' => 'paymentgateway',
                'url' => null,
                'active_menu_url' => 'stores*',
                'name' => 'Payment Gateway',
                'description' => 'Payment Gateway Menu Item',
                'icon' => 'fa fa-globe',
                'target' => null, 'roles' => '["1","2"]',
                'order' => 0,
            ]);
        }

        $children = [
            [
                'parent_id' => $paymentgateway_menu_id,
                'key' => null,
                'url' => config('paymentgateway.models.issuer.resource_url'),
                'active_menu_url' => config('paymentgateway.models.issuer.resource_url') . '*',
                'name' => 'Issuers',
                'description' => 'Issuers List Menu Item',
                'icon' => 'fa fa-stack-overflow',
                'target' => null, 'roles' => '["1"]',
                'order' => 0,
            ],
            [
                'parent_id' => $paymentgateway_menu_id,
                'key' => null,
                'url' => config('paymentgateway.models.pos.resource_url'),
                'active_menu_url' => config('paymentgateway.models.pos.resource_url') . '*',
                'name' => 'POS',
                'description' => 'POS List Menu Item',
                'icon' => 'fa fa-desktop',
                'target' => null, 'roles' => '["1"]',
                'order' => 0,
            ],
            [
                'parent_id' => $paymentgateway_menu_id,
                'key' => null,
                'url' => config('paymentgateway.models.invoice.resource_url'),
                'active_menu_url' => config('paymentgateway.models.invoice.resource_url') . '*',
                'name' => 'Invoices',
                'description' => 'Invoices List Menu Item',
                'icon' => 'fa fa-file-text-o',
                'target' => null, 'roles' => '["1"]',
                'order' => 0,
            ],
            [
                'parent_id' => $paymentgateway_menu_id,
                'key' => null,
                'url' => config('paymentgateway.models.store.resource_url'),
                'active_menu_url' => config('paymentgateway.models.store.resource_url') . '*',
                'name' => 'Stores',
                'description' => 'Stores List Menu Item',
                'icon' => 'fa fa-cube',
                'target' => null, 'roles' => '["1"]',
                'order' => 0,
            ],
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
            [
                'parent_id' => $paymentgateway_menu_id,
                'key' => null,
                'url' => config('paymentgateway.models.payment_reference.resource_url'),
                'active_menu_url' => config('paymentgateway.models.payment_reference.resource_url') . '*',
                'name' => 'Payment References',
                'description' => 'Payment References List Menu Item',
                'icon' => 'fa fa-barcode',
                'target' => null, 'roles' => '["1"]',
                'order' => 1,
            ],
            [
                'parent_id' => $paymentgateway_menu_id,
                'key' => null,
                'url' => config('paymentgateway.models.transaction.resource_url'),
                'active_menu_url' => config('paymentgateway.models.transaction.resource_url') . '*',
                'name' => 'Transactions',
                'description' => 'Transactions List Menu Item',
                'icon' => 'fa fa-exchange',
                'target' => null, 'roles' => '["1"]',
                'order' => 2,
            ],
            [
                'parent_id' => $paymentgateway_menu_id,
                'key' => null,
                'url' => config('paymentgateway.models.shift.resource_url'),
                'active_menu_url' => config('paymentgateway.models.shift.resource_url') . '*',
                'name' => 'Shifts',
                'description' => 'Shifts List Menu Item',
                'icon' => 'fa fa-clock-o',
                'target' => null, 'roles' => '["1"]',
                'order' => 3,
            ],
            [
                'parent_id' => $paymentgateway_menu_id,
                'key' => null,
                'url' => 'reports',
                'active_menu_url' => 'reports*',
                'name' => 'Reports',
                'description' => 'Issuer/Store Reports Menu Item',
                'icon' => 'fa fa-bar-chart',
                'target' => null, 'roles' => '["1"]',
                'order' => 4,
            ],
        ];

        $existingUrls = \DB::table('menus')
            ->where('parent_id', $paymentgateway_menu_id)
            ->pluck('url')
            ->all();

        $newChildren = array_values(array_filter(
            $children,
            fn ($child) => !in_array($child['url'], $existingUrls, true)
        ));

        if (!empty($newChildren)) {
            \DB::table('menus')->insert($newChildren);
        }
    }
```

- [ ] **Step 4: Verify the menu seeder is safe to re-run**

Run: `php artisan db:seed --class="Corals\Modules\PaymentGateway\database\seeds\PaymentGatewayMenuDatabaseSeeder" --force`
Run it a SECOND time immediately after.
Expected: both runs exit 0; `SELECT COUNT(*) FROM menus WHERE key='paymentgateway'` stays 1; `SELECT COUNT(*) FROM menus WHERE url LIKE '%branches%'` stays 1 after either run.

- [ ] **Step 5: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/database/seeds/PaymentGatewayPermissionsDatabaseSeeder.php \
        backend/Corals/modules/PaymentGateway/database/seeds/PaymentGatewayMenuDatabaseSeeder.php
git commit -m "fix(PaymentGateway): make permissions and menu seeders re-runnable"
```

---

### Task 3: Friendly error when deleting a branch that still has POS/operators/shifts

**Problem (verbatim from the final review):** `BranchesController::destroy()` catches `\Exception` generically and returns `$exception->getMessage()` as the JSON error message. `paymentgateway_pos`, `paymentgateway_operator_branches`, and `paymentgateway_shifts` all have `RESTRICT` foreign keys on `branch_id` (default Laravel `constrained()` behavior), so deleting a branch that still has any of those rows throws a `QueryException` whose message is a raw SQL constraint string — surfaced verbatim to the admin.

**Files:**
- Modify: `backend/Corals/modules/PaymentGateway/Http/Controllers/BranchesController.php`

**Interfaces:** none — internal error-handling only.

- [ ] **Step 1: Write the failing test**

Create `backend/tests/Feature/PaymentGateway/BranchDeletionTest.php` (copy the `createApplication()` helper pattern verbatim from `BranchModelTest.php`):

```php
<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BranchDeletionTest extends TestCase
{
    use LazilyRefreshDatabase;

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
    public function deleting_a_branch_with_a_terminal_returns_a_friendly_error_not_a_raw_sql_message()
    {
        // Explicit permission grant (not reliance on "first user = id 1 =
        // superuser") - that assumption is unreliable across a full suite
        // run, per the same fragility CollectFlowTest's isolation fix
        // addressed for payment_reference.create.
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'secret-password']);
        Permission::firstOrCreate([
            'name' => 'PaymentGateway::branch.delete',
            'guard_name' => config('auth.defaults.guard'),
        ]);
        $admin->givePermissionTo('PaymentGateway::branch.delete');

        $store = Store::create(['name' => 'Deletion Co']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Occupied Branch']);
        Pos::create(['store_id' => $store->id, 'branch_id' => $branch->id, 'name' => 'Till 1', 'code' => 'DEL-TILL-1']);

        $response = $this->actingAs($admin)->deleteJson('/branches/' . $branch->hashed_id);

        $response->assertStatus(200);
        $response->assertJson(['level' => 'error']);
        $this->assertStringContainsString(
            'still has POS terminals, operators, or shifts',
            $response->json('message')
        );
        $this->assertStringNotContainsString('SQLSTATE', $response->json('message'));
        $this->assertDatabaseHas('paymentgateway_branches', ['id' => $branch->id]);
    }
}
```

- [ ] **Step 2: Run it, verify it fails**

Run: `cd /home/robernet/Proyectos/laraship-clubpago/backend && ./vendor/bin/phpunit tests/Feature/PaymentGateway/BranchDeletionTest.php`
Expected: FAIL — `destroy()` still catches generically, so `message` contains a raw `SQLSTATE` string instead of the friendly text; `assertStringNotContainsString('SQLSTATE', ...)` fails.

- [ ] **Step 3: Fix `destroy()`**

Current method in `BranchesController.php` (for reference — replace it exactly):
```php
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
```

Replace with:
```php
    public function destroy(BranchRequest $request, Branch $branch)
    {
        try {
            $this->branchService->destroy($request, $branch);

            $message = [
                'level' => 'success',
                'message' => trans('Corals::messages.success.deleted', ['item' => $this->title_singular]),
            ];
        } catch (\Illuminate\Database\QueryException $exception) {
            log_exception($exception, Branch::class, 'destroy');
            $message = [
                'level' => 'error',
                'message' => 'This branch still has POS terminals, operators, or shifts assigned to it and cannot be deleted.',
            ];
        } catch (\Exception $exception) {
            log_exception($exception, Branch::class, 'destroy');
            $message = ['level' => 'error', 'message' => $exception->getMessage()];
        }

        return response()->json($message);
    }
```

- [ ] **Step 4: Run it, verify it passes**

Run: `./vendor/bin/phpunit tests/Feature/PaymentGateway/BranchDeletionTest.php`
Expected: PASS.

- [ ] **Step 5: Run the full PaymentGateway suite, verify no regressions**

Run: `./vendor/bin/phpunit tests/Feature/PaymentGateway`
Expected: all green.

- [ ] **Step 6: Commit**

```bash
git add backend/Corals/modules/PaymentGateway/Http/Controllers/BranchesController.php \
        backend/tests/Feature/PaymentGateway/BranchDeletionTest.php
git commit -m "fix(PaymentGateway): friendly error deleting a branch with dependents"
```
