<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\OperatorBranch;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShiftReconciliationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\LazilyRefreshDatabase;

    /**
     * KNOWN LIMITATION: copied verbatim from CollectFlowTest - see that file's
     * docblock for why this override is needed (dynamic module-loading vs
     * PHPUnit's app bootstrap lifecycle).
     */
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

    private function apiUrl(string $path): string
    {
        return '/api/' . config('corals.api_version') . '/' . ltrim($path, '/');
    }

    /**
     * @return array{token: string, shift_id: string}
     */
    private function loginAndOpenShift(Branch $branch, User $operator): array
    {
        $login = $this->postJson($this->apiUrl('pos/login'), [
            'email' => $operator->email,
            'password' => 'secret-password',
            'branch_id' => $branch->getHashedIdAttribute(),
        ]);
        $login->assertStatus(200);
        $token = $login->json('data.token');

        $openShift = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson($this->apiUrl('shifts'), ['branch_id' => $branch->getHashedIdAttribute()]);
        $openShift->assertStatus(200);

        return [
            'token' => $token,
            'shift_id' => $openShift->json('data.id'),
        ];
    }

    #[Test]
    public function closing_a_shift_records_the_counted_amount_and_a_negative_discrepancy_when_cash_is_short()
    {
        $store = Store::create(['name' => 'Reconciliation Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);

        $operator = User::create([
            'name' => 'Reconciliation Operator',
            'email' => 'reconciliation-operator@example.test',
            'password' => 'secret-password',
        ]);

        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id]);

        $issuer = Issuer::create([
            'name' => 'Reconciliation Issuer',
            'sub_id' => 8,
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

        $opened = $this->loginAndOpenShift($branch, $operator);
        $token = $opened['token'];
        $shiftHashid = $opened['shift_id'];
        $headers = ['Authorization' => 'Bearer ' . $token];

        $invoice = Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => '99',
            'amount_minor' => 15230,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ]);

        $generate = $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ]);
        $generate->assertStatus(200);
        $paymentReferenceId = $generate->json('data.id');

        // Collected 15230, but the operator only counts 15000 in the drawer.
        $this->withHeaders($headers)->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReferenceId,
            'amount' => 15230,
            'currency' => 'MXN',
        ])->assertStatus(200);

        $close = $this->withHeaders($headers)->patchJson(
            $this->apiUrl('shifts/' . $shiftHashid),
            ['counted_amount' => 15000]
        );

        $close->assertStatus(200);
        $this->assertSame(15000, $close->json('data.counted_amount_minor'));
        $this->assertSame(-230, $close->json('data.discrepancy_minor'));

        $this->assertDatabaseHas('paymentgateway_shifts', [
            'id' => \Corals\Modules\PaymentGateway\Models\Shift::findByHash($shiftHashid)->id,
            'counted_amount_minor' => 15000,
            'discrepancy_minor' => -230,
        ]);
    }

    #[Test]
    public function closing_a_shift_with_an_exact_count_records_zero_discrepancy()
    {
        $store = Store::create(['name' => 'Exact Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);

        $operator = User::create([
            'name' => 'Exact Operator',
            'email' => 'exact-operator@example.test',
            'password' => 'secret-password',
        ]);

        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id]);

        $issuer = Issuer::create([
            'name' => 'Exact Issuer',
            'sub_id' => 9,
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

        $opened = $this->loginAndOpenShift($branch, $operator);
        $token = $opened['token'];
        $shiftHashid = $opened['shift_id'];
        $headers = ['Authorization' => 'Bearer ' . $token];

        $invoice = Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => '100',
            'amount_minor' => 5000,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ]);

        $generate = $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ]);
        $paymentReferenceId = $generate->json('data.id');

        $this->withHeaders($headers)->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReferenceId,
            'amount' => 5000,
            'currency' => 'MXN',
        ])->assertStatus(200);

        $close = $this->withHeaders($headers)->patchJson(
            $this->apiUrl('shifts/' . $shiftHashid),
            ['counted_amount' => 5000]
        );

        $close->assertStatus(200);
        $this->assertSame(0, $close->json('data.discrepancy_minor'));
    }

    #[Test]
    public function closing_a_shift_with_extra_cash_records_a_positive_discrepancy()
    {
        $store = Store::create(['name' => 'Over Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);

        $operator = User::create([
            'name' => 'Over Operator',
            'email' => 'over-operator@example.test',
            'password' => 'secret-password',
        ]);

        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id]);

        $issuer = Issuer::create([
            'name' => 'Over Issuer',
            'sub_id' => 10,
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

        $opened = $this->loginAndOpenShift($branch, $operator);
        $token = $opened['token'];
        $shiftHashid = $opened['shift_id'];
        $headers = ['Authorization' => 'Bearer ' . $token];

        $invoice = Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => '101',
            'amount_minor' => 5000,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ]);

        $generate = $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ]);
        $paymentReferenceId = $generate->json('data.id');

        // Collected 5000, but the operator counts 5500 in the drawer (over).
        $this->withHeaders($headers)->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReferenceId,
            'amount' => 5000,
            'currency' => 'MXN',
        ])->assertStatus(200);

        $close = $this->withHeaders($headers)->patchJson(
            $this->apiUrl('shifts/' . $shiftHashid),
            ['counted_amount' => 5500]
        );

        $close->assertStatus(200);
        $this->assertSame(5500, $close->json('data.counted_amount_minor'));
        $this->assertSame(500, $close->json('data.discrepancy_minor'));

        $this->assertDatabaseHas('paymentgateway_shifts', [
            'id' => \Corals\Modules\PaymentGateway\Models\Shift::findByHash($shiftHashid)->id,
            'counted_amount_minor' => 5500,
            'discrepancy_minor' => 500,
        ]);
    }
}
