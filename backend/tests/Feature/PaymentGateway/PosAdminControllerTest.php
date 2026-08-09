<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosAdminControllerTest extends TestCase
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
        }

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    private function admin(string $suffix): User
    {
        if (!User::query()->where('id', 1)->exists()) {
            User::create([
                'name' => 'ID Guard',
                'email' => 'id-guard-' . uniqid() . '@example.test',
                'password' => 'secret-password',
            ]);
        }

        $admin = User::create([
            'name' => 'Pos Admin ' . $suffix,
            'email' => 'pos-admin-' . $suffix . '@example.test',
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
    public function admin_can_create_list_and_view_a_pos_linked_to_a_store()
    {
        $admin = $this->admin('crud');
        $store = Store::create(['name' => 'Downtown Store']);

        $this->actingAs($admin)->get('/pos/create')
            ->assertStatus(200)
            ->assertSee($store->getHashedIdAttribute());

        $this->actingAs($admin)->get('/pos')->assertStatus(200);

        $response = $this->actingAs($admin)->post('/pos', [
            'store_id' => $store->getHashedIdAttribute(),
            'name' => 'Register 1',
            'code' => 'REG-001',
        ]);

        $response->assertRedirect();

        $pos = Pos::query()->where('code', 'REG-001')->firstOrFail();
        $this->assertSame('Register 1', $pos->name);
        $this->assertSame($store->id, $pos->store_id);

        $this->actingAs($admin)->get('/pos/' . $pos->getHashedIdAttribute())
            ->assertStatus(200)
            ->assertSee('Register 1')
            ->assertSee('REG-001')
            ->assertSee('Downtown Store');
    }

    #[Test]
    public function creating_a_pos_with_a_duplicate_code_fails_validation()
    {
        $admin = $this->admin('dup-code');
        $store = Store::create(['name' => 'Duplicate Store']);

        Pos::create(['store_id' => $store->id, 'name' => 'Register A', 'code' => 'DUP-001']);

        $response = $this->actingAs($admin)->post('/pos', [
            'store_id' => $store->getHashedIdAttribute(),
            'name' => 'Register B',
            'code' => 'DUP-001',
        ]);

        $response->assertSessionHasErrors('code');
    }

    #[Test]
    public function the_pos_index_page_renders_exactly_one_create_button()
    {
        $admin = $this->admin('single-create-button');

        $response = $this->actingAs($admin)->get('/pos');

        $response->assertStatus(200);
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'href="' . url('pos/create') . '"'),
            'Expected exactly one link to /pos/create on the index page.'
        );
    }

    #[Test]
    public function store_show_page_lists_its_terminals_with_credential_actions()
    {
        $admin = $this->admin('store-panel');
        $store = Store::create(['name' => 'Panel Store']);
        $terminal = Pos::create(['store_id' => $store->id, 'name' => 'Caja 1', 'code' => 'PANEL-001']);

        // Terminal from a different store must not leak into this store's panel.
        $otherStore = Store::create(['name' => 'Other Store']);
        Pos::create(['store_id' => $otherStore->id, 'name' => 'Caja X', 'code' => 'OTHER-001']);

        $this->actingAs($admin)->get('/stores/' . $store->getHashedIdAttribute())
            ->assertStatus(200)
            ->assertSee('Caja 1')
            ->assertSee('PANEL-001')
            ->assertDontSee('OTHER-001')
            ->assertSee(route('pos.create', ['store_id' => $store->getHashedIdAttribute()]), false)
            ->assertSee(route('paymentgateway.pos.regenerate_secret', $terminal->getHashedIdAttribute()), false);
    }

    #[Test]
    public function admin_can_regenerate_a_terminal_secret_and_delete_it_from_the_store_panel()
    {
        $admin = $this->admin('store-panel-actions');
        $store = Store::create(['name' => 'Actions Store']);
        $terminal = Pos::create(['store_id' => $store->id, 'name' => 'Caja 1', 'code' => 'ACT-001']);
        $originalSecret = $terminal->device_secret;

        $this->actingAs($admin)
            ->post(route('paymentgateway.pos.regenerate_secret', $terminal->getHashedIdAttribute()))
            ->assertRedirect()
            ->assertSessionHas('device_secret');

        $this->assertNotSame($originalSecret, $terminal->fresh()->device_secret);

        // Plain form delete (not the DataTable AJAX call) redirects instead of JSON.
        $this->actingAs($admin)
            ->delete('/pos/' . $terminal->getHashedIdAttribute())
            ->assertRedirect();

        $this->assertDatabaseMissing('paymentgateway_pos', ['id' => $terminal->id]);
    }

    #[Test]
    public function user_without_admin_permission_is_forbidden()
    {
        if (!User::query()->where('id', 1)->exists()) {
            User::create([
                'name' => 'ID Guard',
                'email' => 'id-guard-' . uniqid() . '@example.test',
                'password' => 'secret-password',
            ]);
        }

        $user = User::create([
            'name' => 'No Access User',
            'email' => 'pos-no-access@example.test',
            'password' => 'secret-password',
        ]);

        $this->actingAs($user)->get('/pos')->assertStatus(403);
        $this->actingAs($user)->get('/pos/create')->assertStatus(403);
    }
}
