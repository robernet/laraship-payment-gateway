<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\AutopaySchedule;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\IssuerUser;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Covers gaps found reviewing PaymentReference against docs/api-contract.md:
 * generation is always keyed on invoice_id (a unique Invoice, not a
 * free-typed customer id) - missing/foreign/already-referenced invoices must
 * be rejected with the right status code; AutoPay fields submitted on
 * generation must actually persist and create the scaffold schedule row; and
 * a non-admin caller linked to the issuer via paymentgateway_issuer_users
 * must be able to reach that check at all (PaymentReferenceRequest::authorize()
 * used to 403 them before the controller's issuer-link check ever ran).
 */
class PaymentReferenceValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

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

    private function apiUrl(string $path): string
    {
        return '/api/' . config('corals.api_version') . '/' . ltrim($path, '/');
    }

    /**
     * Issues a Sanctum token directly (like CollectFlowTest's third test does),
     * bypassing pos/login - that endpoint's store lookup is documented elsewhere
     * as flaky under PHPUnit due to module-loading timing, unrelated to what
     * these tests cover.
     */
    private function operatorHeaders(string $suffix, Issuer $issuer): array
    {
        $operator = User::create([
            'name' => 'Validation Operator ' . $suffix,
            'email' => 'validation-operator-' . $suffix . '@example.test',
            'password' => 'secret-password',
        ]);

        IssuerUser::create(['user_id' => $operator->id, 'issuer_id' => $issuer->id]);

        $token = $operator->createToken('validation-operator')->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token];
    }

    private function unpaidInvoice(Issuer $issuer, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'issuer_id' => $issuer->id,
            'customer_id' => '42',
            'amount_minor' => 15230,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ], $overrides));
    }

    #[Test]
    public function generating_without_an_invoice_id_fails_with_a_422_validation_envelope()
    {
        $issuer = Issuer::create([
            'name' => 'No Invoice Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $response = $this->withHeaders($this->operatorHeaders('no-invoice', $issuer))
            ->postJson($this->apiUrl('payment-references'), []);

        $response->assertStatus(422);
        $this->assertArrayHasKey('invoice_id', $response->json('errors'));
    }

    #[Test]
    public function generating_against_an_invoice_whose_issuer_the_caller_is_not_linked_to_is_forbidden()
    {
        $issuer = Issuer::create([
            'name' => 'Foreign Invoice Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);
        $unrelatedIssuer = Issuer::create([
            'name' => 'Unrelated Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);
        $invoice = $this->unpaidInvoice($unrelatedIssuer);

        $response = $this->withHeaders($this->operatorHeaders('foreign', $issuer))
            ->postJson($this->apiUrl('payment-references'), [
                'invoice_id' => $invoice->getHashedIdAttribute(),
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function generating_twice_against_the_same_invoice_is_rejected_with_a_422()
    {
        $issuer = Issuer::create([
            'name' => 'Already Referenced Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);
        $invoice = $this->unpaidInvoice($issuer);
        $headers = $this->operatorHeaders('twice', $issuer);

        $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ])->assertStatus(200);

        $response = $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'invoice_id' => $invoice->getHashedIdAttribute(),
        ]);

        // Thrown and caught inside the controller (invoice found, but already
        // referenced) - apiExceptionResponse() nests ValidationException errors
        // under data.errors, unlike the top-level errors key a FormRequest's
        // own automatic validation failure produces.
        $response->assertStatus(422);
        $this->assertArrayHasKey('invoice_id', $response->json('data.errors'));
    }

    #[Test]
    public function generating_from_an_invoice_embeds_the_issuers_batch_mode_amount_and_due_date()
    {
        $issuer = Issuer::create([
            'name' => 'Batch Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 6, 'amount_length' => 8, 'embed_due_date' => true],
        ]);
        $invoice = $this->unpaidInvoice($issuer, ['amount_minor' => 15230, 'due_date' => '2026-08-15']);

        $response = $this->withHeaders($this->operatorHeaders('batch', $issuer))
            ->postJson($this->apiUrl('payment-references'), [
                'invoice_id' => $invoice->getHashedIdAttribute(),
            ]);

        $response->assertStatus(200);
        $this->assertSame('batch', $response->json('data.integration_mode'));
        $this->assertSame(15230, $response->json('data.amount'));
        $this->assertSame('2026-08-15', $response->json('data.due_date'));

        $reference = $response->json('data.reference');
        $this->assertSame('00015230', substr($reference, 12, 8));
        $this->assertSame('20260815', substr($reference, 20, 8));
    }

    #[Test]
    public function generating_with_autopay_persists_the_fields_and_creates_a_schedule()
    {
        $issuer = Issuer::create([
            'name' => 'Online Issuer AutoPay',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);
        $invoice = $this->unpaidInvoice($issuer);

        $response = $this->withHeaders($this->operatorHeaders('autopay', $issuer))
            ->postJson($this->apiUrl('payment-references'), [
                'invoice_id' => $invoice->getHashedIdAttribute(),
                'autopay_enabled' => true,
                'autopay_payment_number' => 3,
                'autopay_frequency_days' => 30,
            ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.autopay_enabled'));
        $this->assertSame(3, $response->json('data.autopay_payment_number'));
        $this->assertSame(30, $response->json('data.autopay_frequency_days'));

        $paymentReference = PaymentReference::query()->where('reference', $response->json('data.reference'))->firstOrFail();

        $this->assertDatabaseHas('paymentgateway_autopay_schedules', [
            'payment_reference_id' => $paymentReference->id,
            'status' => 'scheduled',
        ]);

        $this->assertSame(1, AutopaySchedule::query()->where('payment_reference_id', $paymentReference->id)->count());
    }
}
