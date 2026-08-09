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
