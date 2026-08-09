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
