<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\AutopaySchedule;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\IssuerUser;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Covers gaps found reviewing PaymentReference against docs/api-contract.md:
 * amount/due_date-required-by-issuer-layout must fail as a proper 422 validation
 * error (not a generic exception); AutoPay fields submitted on generation must
 * actually persist and create the scaffold schedule row; and a non-admin caller
 * linked to the issuer via paymentgateway_issuer_users must be able to reach that
 * check at all (PaymentReferenceRequest::authorize() used to 403 them before the
 * controller's issuer-link check ever ran).
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

    #[Test]
    public function generating_without_the_required_amount_fails_with_a_422_validation_envelope()
    {
        $issuer = Issuer::create([
            'name' => 'Batch Issuer A',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 6, 'amount_length' => 8],
        ]);

        $response = $this->withHeaders($this->operatorHeaders('amount', $issuer))
            ->postJson($this->apiUrl('payment-references'), [
                'issuer_id' => $issuer->getHashedIdAttribute(),
                'customer_id' => '42',
            ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('amount', $response->json('errors'));
    }

    #[Test]
    public function generating_without_the_required_due_date_fails_with_a_422_validation_envelope()
    {
        $issuer = Issuer::create([
            'name' => 'Batch Issuer B',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 6, 'embed_due_date' => true],
        ]);

        $response = $this->withHeaders($this->operatorHeaders('duedate', $issuer))
            ->postJson($this->apiUrl('payment-references'), [
                'issuer_id' => $issuer->getHashedIdAttribute(),
                'customer_id' => '42',
            ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('due_date', $response->json('errors'));
    }

    #[Test]
    public function generating_with_autopay_persists_the_fields_and_creates_a_schedule()
    {
        $issuer = Issuer::create([
            'name' => 'Online Issuer AutoPay',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $response = $this->withHeaders($this->operatorHeaders('autopay', $issuer))
            ->postJson($this->apiUrl('payment-references'), [
                'issuer_id' => $issuer->getHashedIdAttribute(),
                'customer_id' => '42',
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
