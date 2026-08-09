<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Foundation\Facades\Hashids;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OperatorAssignmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** Same module-bootstrap override as the other PaymentGateway feature tests. */
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
        }

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    private function admin(): User
    {
        if (!User::query()->where('id', 1)->exists()) {
            User::create(['name' => 'ID Guard', 'email' => 'id-guard-' . uniqid() . '@example.test', 'password' => 'x']);
        }

        $admin = User::create([
            'name' => 'Store Admin',
            'email' => 'store-admin-' . uniqid() . '@example.test',
            'password' => 'secret-password',
        ]);

        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'Administrations::admin.paymentgateway',
            'guard_name' => config('auth.defaults.guard'),
        ]);
        $admin->givePermissionTo('Administrations::admin.paymentgateway');

        return $admin;
    }

    #[Test]
    public function assigning_an_operator_lets_them_pos_login_and_removing_revokes_it()
    {
        $admin = $this->admin();
        $store = Store::create(['name' => 'Operator Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);

        $operator = User::create([
            'name' => 'Cashier',
            'email' => 'cashier@example.test',
            'password' => 'cashier-pass',
        ]);

        $loginPayload = [
            'email' => 'cashier@example.test',
            'password' => 'cashier-pass',
            'branch_id' => $branch->getHashedIdAttribute(),
        ];
        $apiLogin = '/api/' . config('corals.api_version') . '/pos/login';

        // Before assignment: login is rejected (not assigned to the branch).
        $this->postJson($apiLogin, $loginPayload)->assertStatus(422);

        // Admin assigns the operator via the Branch panel.
        $this->actingAs($admin)->post(
            route('paymentgateway.branches.operators.assign', $branch->getHashedIdAttribute()),
            ['user_id' => Hashids::encode($operator->id)]
        )->assertRedirect();

        $this->assertDatabaseHas('paymentgateway_operator_branches', [
            'user_id' => $operator->id,
            'branch_id' => $branch->id,
        ]);

        // Now login succeeds.
        $this->app['auth']->forgetGuards();
        $this->postJson($apiLogin, $loginPayload)->assertStatus(200);

        // Admin removes the operator; login is rejected again.
        $this->actingAs($admin)->delete(
            route('paymentgateway.branches.operators.remove', [$branch->getHashedIdAttribute(), Hashids::encode($operator->id)])
        )->assertRedirect();

        $this->assertDatabaseMissing('paymentgateway_operator_branches', [
            'user_id' => $operator->id,
            'branch_id' => $branch->id,
        ]);

        $this->app['auth']->forgetGuards();
        $this->postJson($apiLogin, $loginPayload)->assertStatus(422);
    }

    #[Test]
    public function branch_show_page_lists_operators_and_offers_unassigned_users()
    {
        $admin = $this->admin();
        $store = Store::create(['name' => 'Panel Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);

        $assigned = User::create(['name' => 'Assigned One', 'email' => 'assigned@example.test', 'password' => 'x']);
        $branch->operators()->attach($assigned->id);

        $this->actingAs($admin)->get('/branches/' . $branch->getHashedIdAttribute())
            ->assertStatus(200)
            ->assertSee('assigned@example.test')
            ->assertSee(route('paymentgateway.branches.operators.assign', $branch->getHashedIdAttribute()), false);
    }
}
