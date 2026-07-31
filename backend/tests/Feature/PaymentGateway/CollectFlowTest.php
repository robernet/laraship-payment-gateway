<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\OperatorStore;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * KNOWN LIMITATION: this test still fails intermittently on the shift-open
     * step with a "store not found" error that does not reproduce outside
     * PHPUnit (verified via `php artisan tinker` and a real `php artisan serve`
     * + curl session - hashids encode/decode and Store::findByHash both work
     * correctly there). The dynamic module-loading system (ModulesServiceProvider
     * reading the `modules` DB table during provider registration, before this
     * test's own body or lazy-refresh trigger runs) does not play well with
     * PHPUnit's application bootstrapping lifecycle. This override pre-seeds
     * the module's DB row before the kernel boots as a partial mitigation, but
     * does not fully resolve the issue. Needs follow-up - see the phase-1
     * completion report for what was independently verified instead.
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';

        try {
            // .env hasn't been loaded into the environment yet at this point -
            // $kernel->bootstrap() below is what normally does that - so load it
            // explicitly here to get real DB credentials instead of empty env() calls.
            \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..')->load();

            $pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']),
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD']
            );
            // Bound parameter, not string-embedded, so the backslash-heavy FQCN
            // never goes through a second layer of escaping.
            $stmt = $pdo->prepare("
                INSERT INTO modules (code, enabled, installed, load_order, provider, folder, type, created_at, updated_at)
                VALUES ('corals-paymentgateway', 1, 1, 0, :provider, 'PaymentGateway', 'module', NOW(), NOW())
                ON DUPLICATE KEY UPDATE enabled = 1, provider = VALUES(provider)
            ");
            $stmt->execute(['provider' => \Corals\Modules\PaymentGateway\PaymentGatewayServiceProvider::class]);
        } catch (\PDOException $e) {
            // modules table doesn't exist yet (very first run before any migration) - skip.
        }

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    private function apiUrl(string $path): string
    {
        return '/api/' . config('corals.api_version') . '/' . ltrim($path, '/');
    }

    #[Test]
    public function operator_can_login_generate_a_reference_open_a_shift_and_collect_cash()
    {
        $store = Store::create(['name' => 'Test Store']);

        $operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@example.test',
            'password' => 'secret-password',
        ]);

        OperatorStore::create(['user_id' => $operator->id, 'store_id' => $store->id]);

        $issuer = Issuer::create([
            'name' => 'Test Issuer',
            'sub_id' => 7,
            'reference_layout' => ['identifier_length' => 10],
        ]);

        // 1. Login - scoped token for this store.
        $login = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'operator@example.test',
            'password' => 'secret-password',
            'store_id' => $store->getHashedIdAttribute(),
        ]);

        $login->assertStatus(200);
        $token = $login->json('data.token');
        $this->assertNotEmpty($token);

        $headers = ['Authorization' => 'Bearer ' . $token];

        // 2. Generate a reference (online mode - customer id only).
        $generate = $this->withHeaders($headers)->postJson($this->apiUrl('payment-references'), [
            'issuer_id' => $issuer->getHashedIdAttribute(),
            'customer_id' => '42',
        ]);

        $generate->assertStatus(200);
        $reference = $generate->json('data.reference');
        $paymentReferenceId = $generate->json('data.id');
        $this->assertNotEmpty($reference);
        $this->assertSame('777', substr($reference, 0, 3));
        $this->assertSame('007', substr($reference, 3, 3));

        // 3. POS looks up the reference by its raw string (not the hashid).
        $lookup = $this->withHeaders($headers)->getJson($this->apiUrl('payment-references/lookup/' . $reference));
        $lookup->assertStatus(200);
        $this->assertSame($paymentReferenceId, $lookup->json('data.id'));

        // 4. Open a shift for the operator's assigned store.
        $openShift = $this->withHeaders($headers)->postJson($this->apiUrl('shifts'), [
            'store_id' => $store->getHashedIdAttribute(),
        ]);
        $openShift->assertStatus(200);

        // 5. Collect cash against the reference.
        $collect = $this->withHeaders($headers)->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReferenceId,
            'amount' => 15230,
            'currency' => 'MXN',
        ]);

        $collect->assertStatus(200);
        $this->assertSame(15230, $collect->json('data.amount'));

        $this->assertDatabaseHas('paymentgateway_transactions', [
            'payment_reference_id' => PaymentReference::findByHash($paymentReferenceId)->id,
            'amount_minor' => 15230,
            'currency' => 'MXN',
        ]);

        $this->assertSame('collected', PaymentReference::findByHash($paymentReferenceId)->fresh()->status);
        $this->assertSame(1, Transaction::query()->count());
    }

    #[Test]
    public function operator_cannot_open_a_shift_for_a_store_they_are_not_assigned_to()
    {
        $assignedStore = Store::create(['name' => 'Assigned Store']);
        $otherStore = Store::create(['name' => 'Other Store']);

        $operator = User::create([
            'name' => 'Test Operator 2',
            'email' => 'operator2@example.test',
            'password' => 'secret-password',
        ]);

        OperatorStore::create(['user_id' => $operator->id, 'store_id' => $assignedStore->id]);

        // Login is scoped to the assigned store...
        $login = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'operator2@example.test',
            'password' => 'secret-password',
            'store_id' => $assignedStore->getHashedIdAttribute(),
        ]);
        $login->assertStatus(200);
        $token = $login->json('data.token');

        // ...so trying to open a shift for a DIFFERENT store must be rejected,
        // even though the token is otherwise valid and has shift:manage.
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson($this->apiUrl('shifts'), ['store_id' => $otherStore->getHashedIdAttribute()]);

        $response->assertStatus(403);
    }
}
