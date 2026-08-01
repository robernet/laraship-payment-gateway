<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\OperatorStore;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use \Illuminate\Foundation\Testing\LazilyRefreshDatabase;

    /**
     * KNOWN LIMITATION: copied verbatim from CollectFlowTest - see that file's
     * docblock for why this override is needed.
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

    #[Test]
    public function admin_sees_correct_issuer_and_store_totals()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret-password',
        ]);

        // Deterministically grant permission to view reports, regardless of
        // DB auto-increment order (isSuperUser() otherwise only bypasses this
        // for whichever user happens to land on id 1 - see the same pattern
        // in ShiftReconciliationTest).
        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'Administrations::admin.paymentgateway',
            'guard_name' => config('auth.defaults.guard'),
        ]);
        $admin->givePermissionTo('Administrations::admin.paymentgateway');

        $store = Store::create(['name' => 'Report Store']);
        $issuer = Issuer::create([
            'name' => 'Report Issuer',
            'sub_id' => 5,
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $operator = User::create([
            'name' => 'Report Operator',
            'email' => 'report-operator@example.test',
            'password' => 'secret-password',
        ]);
        OperatorStore::create(['user_id' => $operator->id, 'store_id' => $store->id]);

        $shift = Shift::create(['store_id' => $store->id, 'operator_id' => $operator->id, 'opened_at' => now()]);

        $reference1 = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'reference' => '77700500000000000000000001',
            'integration_mode' => 'online',
            'status' => 'collected',
        ]);
        $reference2 = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'reference' => '77700500000000000000000002',
            'integration_mode' => 'online',
            'status' => 'collected',
        ]);

        Transaction::create([
            'payment_reference_id' => $reference1->id,
            'shift_id' => $shift->id,
            'amount_minor' => 10000,
            'currency' => 'MXN',
            'collected_at' => now(),
            'status' => 'settled',
        ]);
        Transaction::create([
            'payment_reference_id' => $reference2->id,
            'shift_id' => $shift->id,
            'amount_minor' => 2500,
            'currency' => 'MXN',
            'collected_at' => now(),
            'status' => 'settled',
        ]);

        $response = $this->actingAs($admin)->get('/reports');

        $response->assertStatus(200);
        $response->assertSee('Report Issuer');
        $response->assertSee('Report Store');
        $response->assertSee('12500'); // 10000 + 2500 combined total for the single issuer/store
    }
}
