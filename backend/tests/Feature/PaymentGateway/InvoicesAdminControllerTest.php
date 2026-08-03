<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\IssuerUser;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesAdminControllerTest extends TestCase
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
        $admin = $this->nonPrivilegedUser('admin-' . $suffix);

        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'Administrations::admin.paymentgateway',
            'guard_name' => config('auth.defaults.guard'),
        ]);
        $admin->givePermissionTo('Administrations::admin.paymentgateway');

        return $admin;
    }

    private function nonPrivilegedUser(string $suffix): User
    {
        if (!User::query()->where('id', 1)->exists()) {
            User::create([
                'name' => 'ID Guard',
                'email' => 'id-guard-' . uniqid() . '@example.test',
                'password' => 'secret-password',
            ]);
        }

        return User::create([
            'name' => 'Invoice User ' . $suffix,
            'email' => 'invoice-user-' . $suffix . '@example.test',
            'password' => 'secret-password',
        ]);
    }

    private function issuer(string $suffix): Issuer
    {
        return Issuer::create([
            'name' => 'Invoice Test Issuer ' . $suffix,
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);
    }

    private function link(User $user, Issuer $issuer): void
    {
        IssuerUser::create(['user_id' => $user->id, 'issuer_id' => $issuer->id]);
    }

    #[Test]
    public function admin_can_create_list_and_view_an_invoice_for_any_issuer()
    {
        $admin = $this->admin('crud');
        $issuer = $this->issuer('crud');

        $this->actingAs($admin)->get('/invoices/create')->assertStatus(200);
        $this->actingAs($admin)->get('/invoices')->assertStatus(200);

        $response = $this->actingAs($admin)->post('/invoices', [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'acme-corp',
            'amount_input' => '150.00',
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'description' => 'August retainer',
        ]);

        $response->assertRedirect();

        $invoice = Invoice::query()->where('customer_id', 'acme-corp')->firstOrFail();
        $this->assertSame(15000, $invoice->amount_minor);
        $this->assertSame('unpaid', $invoice->status);

        $this->actingAs($admin)->get('/invoices/' . $invoice->getHashedIdAttribute())
            ->assertStatus(200)
            ->assertSee('acme-corp');
    }

    #[Test]
    public function issuer_linked_user_can_only_create_and_see_invoices_for_their_own_issuer()
    {
        $user = $this->nonPrivilegedUser('linked');
        $ownIssuer = $this->issuer('linked-own');
        $foreignIssuer = $this->issuer('linked-foreign');
        $this->link($user, $ownIssuer);

        $response = $this->actingAs($user)->post('/invoices', [
            'issuer_id' => $foreignIssuer->getHashedIdAttribute(),
            'customer_id' => 'foreign-customer',
            'amount_input' => '50.00',
            'currency' => 'MXN',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect('/invoices');
        $this->assertDatabaseMissing('paymentgateway_invoices', ['customer_id' => 'foreign-customer']);

        $response = $this->actingAs($user)->post('/invoices', [
            'issuer_id' => $ownIssuer->getHashedIdAttribute(),
            'customer_id' => 'own-customer',
            'amount_input' => '50.00',
            'currency' => 'MXN',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
        $response->assertRedirect();

        $ownInvoice = Invoice::query()->where('customer_id', 'own-customer')->firstOrFail();
        $foreignInvoice = Invoice::create([
            'issuer_id' => $foreignIssuer->id,
            'customer_id' => 'preexisting-foreign',
            'amount_minor' => 1000,
            'currency' => 'MXN',
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'unpaid',
        ]);

        $this->actingAs($user)->get('/invoices/' . $ownInvoice->getHashedIdAttribute())->assertStatus(200);
        $this->actingAs($user)->get('/invoices/' . $foreignInvoice->getHashedIdAttribute())->assertStatus(403);
    }

    #[Test]
    public function user_with_no_admin_permission_and_no_issuer_link_is_forbidden()
    {
        $user = $this->nonPrivilegedUser('no-access');

        $this->actingAs($user)->get('/invoices/create')->assertStatus(403);
        $this->actingAs($user)->get('/invoices')->assertStatus(403);
    }

    #[Test]
    public function editing_an_invoice_is_allowed_before_a_reference_exists_and_blocked_after()
    {
        $admin = $this->admin('edit-lock');
        $issuer = $this->issuer('edit-lock');

        $invoice = Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => 'edit-lock-customer',
            'amount_minor' => 1000,
            'currency' => 'MXN',
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'unpaid',
        ]);

        $this->actingAs($admin)->get('/invoices/' . $invoice->getHashedIdAttribute() . '/edit')->assertStatus(200);

        $updateResponse = $this->actingAs($admin)->put('/invoices/' . $invoice->getHashedIdAttribute(), [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'edit-lock-customer-updated',
            'amount_input' => '20.00',
            'currency' => 'MXN',
            'due_date' => now()->addDays(6)->toDateString(),
        ]);
        $updateResponse->assertRedirect();
        $this->assertSame('edit-lock-customer-updated', $invoice->fresh()->customer_id);

        $this->actingAs($admin)->post('/payment-references', [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ])->assertRedirect();

        $this->actingAs($admin)->get('/invoices/' . $invoice->getHashedIdAttribute() . '/edit')->assertStatus(403);

        $this->actingAs($admin)->put('/invoices/' . $invoice->getHashedIdAttribute(), [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'should-not-apply',
            'amount_input' => '99.00',
            'currency' => 'MXN',
            'due_date' => now()->addDays(9)->toDateString(),
        ])->assertStatus(403);
    }
}
