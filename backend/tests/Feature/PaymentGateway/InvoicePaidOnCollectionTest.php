<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Covers the invoice-based reference's collection lifecycle: collecting a
 * Transaction against an invoice-backed reference must mark the linked
 * Invoice paid; collecting against a non-invoice (API-generated) reference
 * must not touch any invoice at all.
 */
class InvoicePaidOnCollectionTest extends TestCase
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

    private function apiUrl(string $path): string
    {
        return '/api/' . config('corals.api_version') . '/' . ltrim($path, '/');
    }

    /**
     * Bypasses pos/login (documented as flaky under PHPUnit elsewhere in this
     * suite) and the HTTP shift-open step - issues a Sanctum token and an
     * open Shift directly, like PaymentReferenceValidationTest does for the
     * token half.
     */
    private function operatorHeaders(string $suffix, Store $store): array
    {
        $operator = User::create([
            'name' => 'Collect Operator ' . $suffix,
            'email' => 'collect-operator-' . $suffix . '@example.test',
            'password' => 'secret-password',
        ]);

        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Branch ' . $suffix]);

        Shift::create([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'operator_id' => $operator->id,
            'opened_at' => now(),
        ]);

        $token = $operator->createToken('collect-operator', ['*', 'branch:' . $branch->getHashedIdAttribute()])->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token];
    }

    #[Test]
    public function collecting_against_an_invoice_backed_reference_marks_the_invoice_paid()
    {
        $store = Store::create(['name' => 'Invoice Collect Store']);
        $issuer = Issuer::create([
            'name' => 'Invoice Collect Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $invoice = Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => 'acme-corp',
            'amount_minor' => 15000,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ]);

        $paymentReference = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'invoice_id' => $invoice->id,
            'reference' => '7770050000001236',
            'integration_mode' => 'online',
            'status' => 'pending',
            'amount_minor' => 15000,
            'currency' => 'MXN',
            'folio' => 'FOL-20260802-INVCOL',
        ]);

        $response = $this->withHeaders($this->operatorHeaders('paid', $store))
            ->postJson($this->apiUrl('transactions'), [
                'payment_reference_id' => $paymentReference->getHashedIdAttribute(),
                'amount' => 15000,
                'currency' => 'MXN',
            ]);

        $response->assertStatus(200);

        $this->assertSame('collected', $paymentReference->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    #[Test]
    public function collecting_against_a_non_invoice_reference_does_not_touch_any_invoice()
    {
        $store = Store::create(['name' => 'No Invoice Collect Store']);
        $issuer = Issuer::create([
            'name' => 'No Invoice Collect Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $paymentReference = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'reference' => '7770060000001235',
            'integration_mode' => 'online',
            'status' => 'pending',
            'folio' => 'FOL-20260802-NOINV',
        ]);

        $response = $this->withHeaders($this->operatorHeaders('no-invoice', $store))
            ->postJson($this->apiUrl('transactions'), [
                'payment_reference_id' => $paymentReference->getHashedIdAttribute(),
                'amount' => 5000,
                'currency' => 'MXN',
            ]);

        $response->assertStatus(200);
        $this->assertSame('collected', $paymentReference->fresh()->status);
        $this->assertNull($paymentReference->fresh()->invoice_id);
    }
}
