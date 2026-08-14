<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IssuersAdminControllerTest extends TestCase
{
    use \Illuminate\Foundation\Testing\LazilyRefreshDatabase;

    /**
     * Same known limitation as CollectFlowTest (Phase 1) - see there for the full writeup.
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

    private function admin(): User
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'issuer-admin@example.test',
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
    public function admin_can_list_create_and_view_issuers()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/issuers')->assertStatus(200);
        $this->actingAs($admin)->get('/issuers/create')->assertStatus(200);

        $response = $this->actingAs($admin)->post('/issuers', [
            'name' => 'Admin UI Issuer',
            'sub_id' => 42,
            'reference_layout' => ['identifier_length' => 10],
        ]);
        $response->assertRedirect();

        $issuer = Issuer::query()->where('name', 'Admin UI Issuer')->firstOrFail();
        $this->assertSame(42, $issuer->sub_id);

        $this->actingAs($admin)->get('/issuers/' . $issuer->getHashedIdAttribute())
            ->assertStatus(200)
            ->assertSee('Admin UI Issuer');

        $this->actingAs($admin)->get('/issuers/' . $issuer->getHashedIdAttribute() . '/edit')
            ->assertStatus(200)
            ->assertSee('Admin UI Issuer');
    }

    #[Test]
    public function issuer_layout_whose_segments_together_exceed_the_reference_budget_is_rejected()
    {
        // identifier_length (20) and amount_length (10) each pass their own max
        // (22 and 15), but 20 + 10 + PREFIX(3) + SUB_ID(3) = 36 > 28, so no valid
        // reference could ever be generated (IssuerRequest::after()).
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/issuers', [
            'name' => 'Overflowing Layout Issuer',
            'sub_id' => 43,
            'reference_layout' => ['identifier_length' => 20, 'amount_length' => 10],
        ]);

        $response->assertSessionHasErrors('reference_layout.identifier_length');
        $this->assertDatabaseMissing('paymentgateway_issuers', ['name' => 'Overflowing Layout Issuer']);
    }
}
