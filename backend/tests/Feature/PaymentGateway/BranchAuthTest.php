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

class BranchAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

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

    #[Test]
    public function login_with_an_unknown_branch_id_is_rejected()
    {
        $operator = User::create([
            'name' => 'Unknown Branch Op',
            'email' => 'unknown-branch-op@example.test',
            'password' => 'secret-password',
        ]);

        $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'unknown-branch-op@example.test',
            'password' => 'secret-password',
            'branch_id' => 'not-a-real-hashid',
        ])->assertStatus(422)
            ->assertJsonPath('errors.branch_id.0', 'This branch was not found.');
    }

    #[Test]
    public function device_login_with_a_branchless_pos_is_rejected()
    {
        $store = Store::create(['name' => 'Branchless Co']);

        $pos = Pos::create([
            'store_id' => $store->id,
            'branch_id' => null,
            'name' => 'Branchless Till',
            'code' => 'BRANCHLESS-TILL-1',
        ]);
        $plainSecret = $pos->regenerateDeviceSecret();

        $this->postJson($this->apiUrl('pos/device-login'), [
            'code' => 'BRANCHLESS-TILL-1',
            'device_secret' => $plainSecret,
        ])->assertStatus(422);
    }

    #[Test]
    public function a_branch_scoped_token_cannot_open_a_shift_in_a_different_stores_branch()
    {
        $storeX = Store::create(['name' => 'Store X']);
        $storeY = Store::create(['name' => 'Store Y']);
        $branchX = Branch::create(['store_id' => $storeX->id, 'name' => 'Branch X']);
        $branchY = Branch::create(['store_id' => $storeY->id, 'name' => 'Branch Y']);

        $operator = User::create([
            'name' => 'Cross Store Op',
            'email' => 'cross-store-op@example.test',
            'password' => 'secret-password',
        ]);
        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branchX->id]);

        $loginResponse = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'cross-store-op@example.test',
            'password' => 'secret-password',
            'branch_id' => $branchX->getHashedIdAttribute(),
        ]);

        $token = $loginResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->apiUrl('shifts'), [
                'branch_id' => $branchY->getHashedIdAttribute(),
            ])->assertStatus(403);
    }
}
