<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\OperatorBranch;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * KNOWN LIMITATION: this test still fails intermittently on the shift-open
     * step with a "store not found" error that does not reproduce outside
     * PHPUnit (verified via `php artisan tinker` and a real `php artisan serve`
     * + curl session - hashids encode/decode and Store::findByHash both work
     * correctly there). The dynamic module-loading system (ModulesServiceProvider
     * reading the `modules` DB table during provider registration, before this
     * test's own body or lazy-refresh trigger runs) does not play well with
     * PHPUnit's application bootstrapping lifecycle. This override pre-seeds
     * the module's DB row before the kernel boots as a partial mitigation, but
     * does not fully resolve the issue. Needs follow-up - see the phase-1
     * completion report for what was independently verified instead.
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';

        try {
            // .env hasn't been loaded into the environment yet at this point -
            // $kernel->bootstrap() below is what normally does that - so load it
            // explicitly here to get real DB credentials instead of empty env() calls.
            \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..')->load();

            $pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']),
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD']
            );
            // Bound parameter, not string-embedded, so the backslash-heavy FQCN
            // never goes through a second layer of escaping.
            $stmt = $pdo->prepare("
                INSERT INTO modules (code, enabled, installed, load_order, provider, folder, type, created_at, updated_at)
                VALUES ('corals-paymentgateway', 1, 1, 0, :provider, 'PaymentGateway', 'module', NOW(), NOW())
                ON DUPLICATE KEY UPDATE enabled = 1, provider = VALUES(provider)
            ");
            $stmt->execute(['provider' => \Corals\Modules\PaymentGateway\PaymentGatewayServiceProvider::class]);
        } catch (\PDOException $e) {
            // modules table doesn't exist yet (very first run before any migration) - skip.
        }

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    private function apiUrl(string $path): string
    {
        return '/api/' . config('corals.api_version') . '/' . ltrim($path, '/');
    }

    #[Test]
    public function operator_can_login_generate_a_reference_open_a_shift_and_collect_cash()
    {
        $store = Store::create(['name' => 'Test Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);

        $operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@example.test',
            'password' => 'secret-password',
        ]);

        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id]);

        $issuer = Issuer::create([
            'name' => 'Test Issuer',
            'sub_id' => 7,
            'reference_layout' => ['identifier_length' => 10],
        ]);

        // Deterministically grant permission to generate payment references,
        // regardless of DB auto-increment order (isSuperUser() otherwise only
        // bypasses this for whichever user happens to land on id 1).
        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'PaymentGateway::payment_reference.create',
            'guard_name' => config('auth.defaults.guard'),
        ]);
        $operator->givePermissionTo('PaymentGateway::payment_reference.create');

        // 1. Login - scoped token for this branch.
        $login = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'operator@example.test',
            'password' => 'secret-password',
            'branch_id' => $branch->getHashedIdAttribute(),
        ]);

        $login->assertStatus(200);
        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $headers = ['Authorization' => 'Bearer ' . $token];

        // 2. Generate a reference from an Invoice (online mode - identifier only).
        $invoice = Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => '42',
            'amount_minor' => 15230,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ]);

        $generate = $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ]);

        $generate->assertStatus(200);
        $reference = $generate->json('data.reference');
        $paymentReferenceId = $generate->json('data.id');
        $this->assertNotEmpty($reference);
        $this->assertSame('777', substr($reference, 0, 3));
        $this->assertSame('007', substr($reference, 3, 3));

        // 3. POS looks up the reference by its raw string (not the hashid).
        $lookup = $this->withHeaders($headers)->getJson($this->apiUrl('payment-references/lookup/' . $reference));
        $lookup->assertStatus(200);
        $this->assertSame($paymentReferenceId, $lookup->json('data.id'));

        // 4. Open a shift for the operator's assigned branch.
        $openShift = $this->withHeaders($headers)->postJson($this->apiUrl('shifts'), [
            'branch_id' => $branch->getHashedIdAttribute(),
        ]);
        $openShift->assertStatus(200);

        // 5. Collect cash against the reference.
        $collect = $this->withHeaders($headers)->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReferenceId,
            'amount' => 15230,
            'currency' => 'MXN',
        ]);

        $collect->assertStatus(200);
        $this->assertSame(15230, $collect->json('data.amount'));

        // collected_at must be ISO 8601 (the Flutter client parses it as a DateTime;
        // a human-formatted "10 Aug, 2026" throws a FormatException client-side).
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $collect->json('data.collected_at')
        );

        $this->assertDatabaseHas('paymentgateway_transactions', [
            'payment_reference_id' => PaymentReference::findByHash($paymentReferenceId)->id,
            'amount_minor' => 15230,
            'currency' => 'MXN',
        ]);

        $this->assertSame('collected', PaymentReference::findByHash($paymentReferenceId)->fresh()->status);
        $this->assertSame(1, Transaction::query()->count());
    }

    #[Test]
    public function operator_cannot_open_a_shift_for_a_branch_they_are_not_assigned_to()
    {
        $store = Store::create(['name' => 'Assigned Store']);
        $assignedBranch = Branch::create(['store_id' => $store->id, 'name' => 'Assigned Branch']);
        $otherBranch = Branch::create(['store_id' => $store->id, 'name' => 'Other Branch']);

        $operator = User::create([
            'name' => 'Test Operator 2',
            'email' => 'operator2@example.test',
            'password' => 'secret-password',
        ]);

        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $assignedBranch->id]);

        // Login is scoped to the assigned branch...
        $login = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'operator2@example.test',
            'password' => 'secret-password',
            'branch_id' => $assignedBranch->getHashedIdAttribute(),
        ]);
        $login->assertStatus(200);
        $token = $login->json('data.token');

        // ...so trying to open a shift for a DIFFERENT branch must be rejected,
        // even though the token is otherwise valid and has shift:manage.
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson($this->apiUrl('shifts'), ['branch_id' => $otherBranch->getHashedIdAttribute()]);

        $response->assertStatus(403);
    }

    #[Test]
    public function collecting_without_the_payment_collect_ability_is_rejected()
    {
        $store = Store::create(['name' => 'Test Store 3']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);

        $operator = User::create([
            'name' => 'Test Operator 3',
            'email' => 'operator3@example.test',
            'password' => 'secret-password',
        ]);

        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id]);

        $issuer = Issuer::create([
            'name' => 'Test Issuer 3',
            'sub_id' => 8,
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $paymentReference = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'reference' => '7770080000000042' . '5',
            'integration_mode' => 'online',
            'status' => 'pending',
        ]);

        // Deliberately missing 'payment:collect' - only lookup/read/shift abilities granted.
        $token = $operator->createToken('pos-operator', [
            'payment:lookup',
            'transaction:read-own',
            'shift:manage',
            'branch:' . $branch->getHashedIdAttribute(),
        ])->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson($this->apiUrl('transactions'), [
                'payment_reference_id' => $paymentReference->getHashedIdAttribute(),
                'amount' => 15230,
                'currency' => 'MXN',
            ]);

        $response->assertStatus(403);
    }

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

        // Force a real gap before opening shift B, so opened_at (second
        // precision) can't tie with shift A's - a tie would make
        // latest('opened_at') non-deterministic and could pass even against
        // the buggy (branch-unaware) lookup by accident.
        $this->travel(2)->seconds();

        $loginB = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'roaming-operator@example.test',
            'password' => 'secret-password',
            'branch_id' => $branchB->getHashedIdAttribute(),
        ]);
        $tokenB = $loginB->json('data.token');

        // Sanctum's guard memoizes the resolved user (and its attached token)
        // for the lifetime of the test's Application instance, keyed by user
        // identity rather than by token - so switching to a DIFFERENT token
        // for the SAME operator still needs forgetGuards() immediately before
        // the next authenticated call, not just once earlier in the test.
        $this->app['auth']->forgetGuards();

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
}
