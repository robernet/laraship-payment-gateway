<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\DataTables\PaymentReferencesDataTable;
use Corals\Modules\PaymentGateway\Models\Invoice;
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

    private function unpaidInvoice(Issuer $issuer, string $suffix): Invoice
    {
        return Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => 'cust-' . $suffix,
            'amount_minor' => 15000,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ]);
    }

    #[Test]
    public function admin_sees_all_unpaid_invoices_grouped_by_issuer_and_can_generate_from_one()
    {
        $admin = $this->admin('generate-any');
        $issuerOne = $this->issuer('admin-one');
        $issuerTwo = $this->issuer('admin-two');
        $invoiceOne = $this->unpaidInvoice($issuerOne, 'one');
        $invoiceTwo = $this->unpaidInvoice($issuerTwo, 'two');

        $this->actingAs($admin)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertSee('<optgroup label="' . $issuerOne->name . '"', false)
            ->assertSee('<optgroup label="' . $issuerTwo->name . '"', false)
            ->assertSee($invoiceOne->customer_id)
            ->assertSee($invoiceTwo->customer_id);

        $response = $this->actingAs($admin)->post('/payment-references', [
            'invoice_id' => $invoiceTwo->getHashedIdAttribute(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'issuer_id' => $issuerTwo->id,
            'invoice_id' => $invoiceTwo->id,
            'amount_minor' => 15000,
            'currency' => 'MXN',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function generating_from_an_invoice_succeeds_when_the_hidden_autopay_fields_are_submitted_blank()
    {
        // The create form's autopay_payment_number/autopay_frequency_days inputs are only
        // visually hidden via jQuery .toggle() (create.blade.php) - they're still submitted
        // as empty strings by a real browser even when autopay is unchecked. The global
        // ConvertEmptyStringsToNull middleware turns those into null, but a present null
        // value still runs through bare integer/min:1 rules without `nullable`, so this
        // reproduces the real submission shape rather than the omitted-fields shape the
        // other generate-from-invoice tests use.
        $admin = $this->admin('blank-autopay');
        $issuer = $this->issuer('blank-autopay');
        $invoice = $this->unpaidInvoice($issuer, 'blank-autopay');

        // An unchecked HTML checkbox is omitted by the browser entirely - only the
        // always-present number inputs are simulated here.
        $response = $this->actingAs($admin)->post('/payment-references', [
            'invoice_id' => $invoice->getHashedIdAttribute(),
            'autopay_payment_number' => '',
            'autopay_frequency_days' => '',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'invoice_id' => $invoice->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function single_issuer_linked_user_sees_a_flat_invoice_list_and_can_generate()
    {
        $user = $this->nonPrivilegedUser('linked-single');
        $issuer = $this->issuer('linked-single');
        $invoice = $this->unpaidInvoice($issuer, 'single');
        $this->link($user, $issuer);

        $this->actingAs($user)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertDontSee('<optgroup', false)
            ->assertSee($invoice->customer_id);

        $response = $this->actingAs($user)->post('/payment-references', [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'issuer_id' => $issuer->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    #[Test]
    public function two_issuer_linked_user_sees_grouped_list_excluding_unrelated_issuers_invoices()
    {
        $user = $this->nonPrivilegedUser('linked-two');
        $issuerOne = $this->issuer('scoped-one');
        $issuerTwo = $this->issuer('scoped-two');
        $unrelatedIssuer = $this->issuer('scoped-unrelated');
        $invoiceOne = $this->unpaidInvoice($issuerOne, 'scoped-one');
        $invoiceTwo = $this->unpaidInvoice($issuerTwo, 'scoped-two');
        $unrelatedInvoice = $this->unpaidInvoice($unrelatedIssuer, 'scoped-unrelated');
        $this->link($user, $issuerOne);
        $this->link($user, $issuerTwo);

        $this->actingAs($user)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertSee('<optgroup', false)
            ->assertSee($invoiceOne->customer_id)
            ->assertSee($invoiceTwo->customer_id)
            ->assertDontSee($unrelatedInvoice->customer_id);
    }

    #[Test]
    public function issuer_linked_user_cannot_generate_from_an_invoice_of_an_unlinked_issuer()
    {
        $user = $this->nonPrivilegedUser('linked-foreign');
        $ownIssuer = $this->issuer('own');
        $foreignIssuer = $this->issuer('foreign');
        $foreignInvoice = $this->unpaidInvoice($foreignIssuer, 'foreign');
        $this->link($user, $ownIssuer);

        $response = $this->actingAs($user)->post('/payment-references', [
            'invoice_id' => $foreignInvoice->getHashedIdAttribute(),
        ]);

        $response->assertRedirect('/payment-references');
        $this->assertDatabaseMissing('paymentgateway_payment_references', [
            'invoice_id' => $foreignInvoice->id,
        ]);
    }

    #[Test]
    public function user_with_no_admin_permission_and_no_issuer_link_is_forbidden()
    {
        $user = $this->nonPrivilegedUser('no-access');
        $issuer = $this->issuer('no-access');
        $invoice = $this->unpaidInvoice($issuer, 'no-access');

        $this->actingAs($user)->get('/payment-references/create')->assertStatus(403);

        $response = $this->actingAs($user)->post('/payment-references', [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ]);

        $response->assertRedirect('/payment-references');
        $this->assertDatabaseMissing('paymentgateway_payment_references', [
            'invoice_id' => $invoice->id,
        ]);
    }

    #[Test]
    public function generating_from_an_invoice_hides_it_from_the_unpaid_list_and_locks_it_from_editing()
    {
        $admin = $this->admin('lock');
        $issuer = $this->issuer('lock');
        $invoice = $this->unpaidInvoice($issuer, 'lock');

        $this->actingAs($admin)->post('/payment-references', [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ])->assertRedirect();

        $this->actingAs($admin)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertDontSee($invoice->customer_id);

        $this->actingAs($admin)->get('/invoices/' . $invoice->getHashedIdAttribute() . '/edit')
            ->assertStatus(403);
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
    public function admin_can_generate_a_reference_from_a_newly_created_invoice_in_one_submit()
    {
        $admin = $this->admin('inline-invoice');
        $issuer = $this->issuer('inline-invoice');

        $response = $this->actingAs($admin)->post('/payment-references', [
            'invoice_mode' => 'new',
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => 'cust-inline',
            'amount_input' => '150.00',
            'currency' => 'MXN',
            'due_date' => now()->addDays(5)->toDateString(),
            'description' => 'Inline invoice test',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('paymentgateway_invoices', [
            'issuer_id' => $issuer->id,
            'customer_id' => 'cust-inline',
            'amount_minor' => 15000,
            'currency' => 'MXN',
        ]);

        $invoice = Invoice::where('customer_id', 'cust-inline')->firstOrFail();

        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'issuer_id' => $issuer->id,
            'invoice_id' => $invoice->id,
            'amount_minor' => 15000,
            'currency' => 'MXN',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function admin_form_can_configure_autopay_on_generation()
    {
        $admin = $this->admin('autopay');
        $issuer = $this->issuer('autopay');
        $invoice = $this->unpaidInvoice($issuer, 'autopay');

        $response = $this->actingAs($admin)->post('/payment-references', [
            'invoice_mode' => 'existing',
            'invoice_id' => $invoice->getHashedIdAttribute(),
            'autopay_enabled' => '1',
            'autopay_payment_number' => 6,
            'autopay_frequency_days' => 30,
        ]);

        $response->assertRedirect();

        $paymentReference = PaymentReference::where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'id' => $paymentReference->id,
            'autopay_enabled' => 1,
            'autopay_payment_number' => 6,
            'autopay_frequency_days' => 30,
        ]);

        $this->assertDatabaseHas('paymentgateway_autopay_schedules', [
            'payment_reference_id' => $paymentReference->id,
            'status' => 'scheduled',
        ]);
    }

    #[Test]
    public function enabling_autopay_without_payment_number_or_frequency_fails_validation()
    {
        $admin = $this->admin('autopay-invalid');
        $issuer = $this->issuer('autopay-invalid');
        $invoice = $this->unpaidInvoice($issuer, 'autopay-invalid');

        $response = $this->actingAs($admin)->post('/payment-references', [
            'invoice_mode' => 'existing',
            'invoice_id' => $invoice->getHashedIdAttribute(),
            'autopay_enabled' => '1',
        ]);

        $response->assertSessionHasErrors(['autopay_payment_number', 'autopay_frequency_days']);

        $this->assertDatabaseMissing('paymentgateway_payment_references', [
            'invoice_id' => $invoice->id,
        ]);
    }

    #[Test]
    public function create_page_renders_the_new_invoice_toggle_and_autopay_fields()
    {
        $admin = $this->admin('render-toggle');
        $this->issuer('render-toggle');

        $this->actingAs($admin)->get('/payment-references/create')
            ->assertStatus(200)
            ->assertSee('name="invoice_mode"', false)
            ->assertSee('name="autopay_enabled"', false)
            ->assertSee('name="autopay_payment_number"', false)
            ->assertSee('name="autopay_frequency_days"', false);
    }
}
