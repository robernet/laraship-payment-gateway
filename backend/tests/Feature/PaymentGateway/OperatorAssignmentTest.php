<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Foundation\Facades\Hashids;
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

        $operator = User::create([
            'name' => 'Cashier',
            'email' => 'cashier@example.test',
            'password' => 'cashier-pass',
        ]);

        $loginPayload = [
            'email' => 'cashier@example.test',
            'password' => 'cashier-pass',
            'store_id' => $store->getHashedIdAttribute(),
        ];
        $apiLogin = '/api/' . config('corals.api_version') . '/pos/login';

        // Before assignment: login is rejected (not assigned to the store).
        $this->postJson($apiLogin, $loginPayload)->assertStatus(422);

        // Admin assigns the operator via the Store panel.
        $this->actingAs($admin)->post(
            route('paymentgateway.stores.operators.assign', $store->getHashedIdAttribute()),
            ['user_id' => Hashids::encode($operator->id)]
        )->assertRedirect();

        $this->assertDatabaseHas('paymentgateway_operator_stores', [
            'user_id' => $operator->id,
            'store_id' => $store->id,
        ]);

        // Now login succeeds.
        $this->app['auth']->forgetGuards();
        $this->postJson($apiLogin, $loginPayload)->assertStatus(200);

        // Admin removes the operator; login is rejected again.
        $this->actingAs($admin)->delete(
            route('paymentgateway.stores.operators.remove', [$store->getHashedIdAttribute(), Hashids::encode($operator->id)])
        )->assertRedirect();

        $this->assertDatabaseMissing('paymentgateway_operator_stores', [
            'user_id' => $operator->id,
            'store_id' => $store->id,
        ]);

        $this->app['auth']->forgetGuards();
        $this->postJson($apiLogin, $loginPayload)->assertStatus(422);
    }

    #[Test]
    public function store_show_page_lists_operators_and_offers_unassigned_users()
    {
        $admin = $this->admin();
        $store = Store::create(['name' => 'Panel Store']);

        $assigned = User::create(['name' => 'Assigned One', 'email' => 'assigned@example.test', 'password' => 'x']);
        $store->operators()->attach($assigned->id);

        $this->actingAs($admin)->get('/stores/' . $store->getHashedIdAttribute())
            ->assertStatus(200)
            ->assertSee('assigned@example.test')
            ->assertSee(route('paymentgateway.stores.operators.assign', $store->getHashedIdAttribute()), false);
    }
}
