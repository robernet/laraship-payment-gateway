<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\DataTables\PaymentReferencesDataTable;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\IssuerUser;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentReferencesAdminControllerTest extends TestCase
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
        // isSuperUser() treats id==Settings::get('super_user_id', 1) as a superuser -
        // burn id 1 first so a fresh test user isn't accidentally superuser.
        if (!User::query()->where('id', 1)->exists()) {
            User::create([
                'name' => 'ID Guard',
                'email' => 'id-guard-' . uniqid() . '@example.test',
                'password' => 'secret-password',
            ]);
        }

        return User::create([
            'name' => 'PR User ' . $suffix,
            'email' => 'pr-user-' . $suffix . '@example.test',
            'password' => 'secret-password',
        ]);
    }

    private function issuer(string $suffix, array $layout = ['identifier_length' => 10]): Issuer
    {
        return Issuer::create([
            'name' => 'Admin Panel Issuer ' . $suffix,
            'sub_id' => random_int(0, 999),
            'reference_layout' => $layout,
        ]);
    }

    private function link(User $user, Issuer $issuer): void
    {
        IssuerUser::create(['user_id' => $user->id, 'issuer_id' => $issuer->id]);
    }

    #[Test]
    public function admin_sees_full_issuer_select_and_can_generate_for_any_issuer()
    {
        $admin = $this->admin('generate-any');
        $issuerOne = $this->issuer('admin-one');
        $issuerTwo = $this->issuer('admin-two');

        $this->actingAs($admin)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertSee('<select name="issuer_id"', false)
            ->assertSee($issuerOne->name)
            ->assertSee($issuerTwo->name);

        $response = $this->actingAs($admin)->post('/payment-references', [
            'issuer_id' => $issuerTwo->getHashedIdAttribute(),
            'customer_id' => 'cust-1',
            'currency' => 'MXN',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'issuer_id' => $issuerTwo->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function single_issuer_linked_user_gets_auto_selected_hidden_field_and_can_generate()
    {
        $user = $this->nonPrivilegedUser('linked-single');
        $issuer = $this->issuer('linked-single');
        $this->link($user, $issuer);

        $this->actingAs($user)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertDontSee('<select name="issuer_id"', false)
            ->assertSee('name="issuer_id" value="' . $issuer->getHashedIdAttribute() . '"', false)
            ->assertSee($issuer->name);

        $response = $this->actingAs($user)->post('/payment-references', [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'cust-2',
            'currency' => 'MXN',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'issuer_id' => $issuer->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function two_issuer_linked_user_sees_scoped_picker_excluding_unrelated_issuer()
    {
        $user = $this->nonPrivilegedUser('linked-two');
        $issuerOne = $this->issuer('scoped-one');
        $issuerTwo = $this->issuer('scoped-two');
        $unrelatedIssuer = $this->issuer('scoped-unrelated');
        $this->link($user, $issuerOne);
        $this->link($user, $issuerTwo);

        $this->actingAs($user)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertSee('<select name="issuer_id"', false)
            ->assertSee($issuerOne->name)
            ->assertSee($issuerTwo->name)
            ->assertDontSee($unrelatedIssuer->name);
    }

    #[Test]
    public function issuer_linked_user_cannot_generate_for_an_issuer_they_are_not_linked_to()
    {
        $user = $this->nonPrivilegedUser('linked-foreign');
        $ownIssuer = $this->issuer('own');
        $foreignIssuer = $this->issuer('foreign');
        $this->link($user, $ownIssuer);

        $response = $this->actingAs($user)->post('/payment-references', [
            'issuer_id' => $foreignIssuer->getHashedIdAttribute(),
            'customer_id' => 'cust-3',
            'currency' => 'MXN',
        ]);

        $response->assertRedirect('/payment-references');
        $this->assertDatabaseMissing('paymentgateway_payment_references', [
            'issuer_id' => $foreignIssuer->id,
        ]);
    }

    #[Test]
    public function user_with_no_admin_permission_and_no_issuer_link_is_forbidden()
    {
        $user = $this->nonPrivilegedUser('no-access');
        $issuer = $this->issuer('no-access');

        $this->actingAs($user)->get('/payment-references/create')->assertStatus(403);

        $response = $this->actingAs($user)->post('/payment-references', [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'cust-4',
            'currency' => 'MXN',
        ]);

        $response->assertRedirect('/payment-references');
        $this->assertDatabaseMissing('paymentgateway_payment_references', [
            'issuer_id' => $issuer->id,
        ]);
    }

    #[Test]
    public function issuer_scoped_user_cannot_view_another_issuers_reference()
    {
        $user = $this->nonPrivilegedUser('scoped-show');
        $ownIssuer = $this->issuer('scoped-show-own');
        $otherIssuer = $this->issuer('scoped-show-other');
        $this->link($user, $ownIssuer);

        $ownReference = PaymentReference::create([
            'issuer_id' => $ownIssuer->id,
            'reference' => '7770010000001239',
            'integration_mode' => 'online',
            'status' => 'pending',
            'folio' => 'FOL-20260802-AAAAAA',
        ]);

        $otherReference = PaymentReference::create([
            'issuer_id' => $otherIssuer->id,
            'reference' => '7770020000001231',
            'integration_mode' => 'online',
            'status' => 'pending',
            'folio' => 'FOL-20260802-BBBBBB',
        ]);

        $this->actingAs($user)
            ->get('/payment-references/' . $otherReference->getHashedIdAttribute())
            ->assertStatus(403);

        $this->actingAs($user)
            ->get('/payment-references/' . $ownReference->getHashedIdAttribute())
            ->assertStatus(200);
    }

    #[Test]
    public function issuer_scoped_users_datatable_query_excludes_other_issuers_references()
    {
        $user = $this->nonPrivilegedUser('scoped-index');
        $ownIssuer = $this->issuer('scoped-index-own');
        $otherIssuer = $this->issuer('scoped-index-other');
        $this->link($user, $ownIssuer);

        $ownReference = PaymentReference::create([
            'issuer_id' => $ownIssuer->id,
            'reference' => '7770030000001238',
            'integration_mode' => 'online',
            'status' => 'pending',
            'folio' => 'FOL-20260802-CCCCCC',
        ]);

        $otherReference = PaymentReference::create([
            'issuer_id' => $otherIssuer->id,
            'reference' => '7770040000001230',
            'integration_mode' => 'online',
            'status' => 'pending',
            'folio' => 'FOL-20260802-DDDDDD',
        ]);

        $this->actingAs($user)->get('/payment-references')->assertStatus(200);

        $references = app(PaymentReferencesDataTable::class)
            ->query(new PaymentReference())
            ->pluck('reference')
            ->all();

        $this->assertContains($ownReference->reference, $references);
        $this->assertNotContains($otherReference->reference, $references);
    }

    #[Test]
    public function decimal_amount_input_converts_to_minor_units()
    {
        $admin = $this->admin('decimal');
        $issuer = $this->issuer('decimal');

        $response = $this->actingAs($admin)->post('/payment-references', [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'cust-5',
            'currency' => 'MXN',
            'amount_input' => '150.00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'issuer_id' => $issuer->id,
            'amount_minor' => 15000,
        ]);
    }

    #[Test]
    public function missing_amount_validation_error_is_visible_on_the_rendered_create_page()
    {
        $admin = $this->admin('missing-amount');
        $issuer = $this->issuer('missing-amount', ['identifier_length' => 6, 'amount_length' => 8]);

        $response = $this->from('/payment-references/create')->actingAs($admin)->post('/payment-references', [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'cust-6',
            'currency' => 'MXN',
        ]);

        $response->assertSessionHasErrors('amount');

        $this->actingAs($admin)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertSee(trans('validation.required', ['attribute' => 'amount']));
    }
}
