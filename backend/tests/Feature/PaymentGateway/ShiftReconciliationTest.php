<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\OperatorStore;
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

    private function loginAndOpenShift(Store $store, User $operator): string
    {
        $login = $this->postJson($this->apiUrl('pos/login'), [
            'email' => $operator->email,
            'password' => 'secret-password',
            'store_id' => $store->getHashedIdAttribute(),
        ]);
        $login->assertStatus(200);
        $token = $login->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson($this->apiUrl('shifts'), ['store_id' => $store->getHashedIdAttribute()])
            ->assertStatus(200);

        return $token;
    }

    #[Test]
    public function closing_a_shift_records_the_counted_amount_and_a_negative_discrepancy_when_cash_is_short()
    {
        $store = Store::create(['name' => 'Reconciliation Store']);

        $operator = User::create([
            'name' => 'Reconciliation Operator',
            'email' => 'reconciliation-operator@example.test',
            'password' => 'secret-password',
        ]);

        OperatorStore::create(['user_id' => $operator->id, 'store_id' => $store->id]);

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

        $token = $this->loginAndOpenShift($store, $operator);
        $headers = ['Authorization' => 'Bearer ' . $token];

        $generate = $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => '99',
        ]);
        $generate->assertStatus(200);
        $paymentReferenceId = $generate->json('data.id');

        // Collected 15230, but the operator only counts 15000 in the drawer.
        $this->withHeaders($headers)->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReferenceId,
            'amount' => 15230,
            'currency' => 'MXN',
        ])->assertStatus(200);

        $shiftHashid = $this->withHeaders($headers)
            ->postJson($this->apiUrl('shifts'), ['store_id' => $store->getHashedIdAttribute()])
            ->json('data.id');

        // The above re-opens a shift because Task's flow already has one open from loginAndOpenShift -
        // instead close the shift actually holding the transaction: fetch it directly.
        $openShift = \Corals\Modules\PaymentGateway\Models\Shift::query()
            ->where('operator_id', $operator->id)
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        $close = $this->withHeaders($headers)->patchJson(
            $this->apiUrl('shifts/' . $openShift->getHashedIdAttribute()),
            ['counted_amount' => 15000]
        );

        $close->assertStatus(200);
        $this->assertSame(15000, $close->json('data.counted_amount_minor'));
        $this->assertSame(-230, $close->json('data.discrepancy_minor'));

        $this->assertDatabaseHas('paymentgateway_shifts', [
            'id' => $openShift->id,
            'counted_amount_minor' => 15000,
            'discrepancy_minor' => -230,
        ]);
    }

    #[Test]
    public function closing_a_shift_with_an_exact_count_records_zero_discrepancy()
    {
        $store = Store::create(['name' => 'Exact Store']);

        $operator = User::create([
            'name' => 'Exact Operator',
            'email' => 'exact-operator@example.test',
            'password' => 'secret-password',
        ]);

        OperatorStore::create(['user_id' => $operator->id, 'store_id' => $store->id]);

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

        $token = $this->loginAndOpenShift($store, $operator);
        $headers = ['Authorization' => 'Bearer ' . $token];

        $generate = $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => '100',
        ]);
        $paymentReferenceId = $generate->json('data.id');

        $this->withHeaders($headers)->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReferenceId,
            'amount' => 5000,
            'currency' => 'MXN',
        ])->assertStatus(200);

        $openShift = \Corals\Modules\PaymentGateway\Models\Shift::query()
            ->where('operator_id', $operator->id)
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        $close = $this->withHeaders($headers)->patchJson(
            $this->apiUrl('shifts/' . $openShift->getHashedIdAttribute()),
            ['counted_amount' => 5000]
        );

        $close->assertStatus(200);
        $this->assertSame(0, $close->json('data.discrepancy_minor'));
    }
}
