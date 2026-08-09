<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\OperatorBranch;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosDeviceAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * KNOWN LIMITATION: copied verbatim from CollectFlowTest - see that file's
     * docblock for why this override is needed (dynamic module-loading vs
     * PHPUnit's app bootstrap lifecycle).
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

    private function makePos(Branch $branch, string $code): array
    {
        $pos = Pos::create([
            'store_id' => $branch->store_id,
            'branch_id' => $branch->id,
            'name' => 'Terminal ' . $code,
            'code' => $code,
        ]);
        $plainSecret = $pos->regenerateDeviceSecret();

        return [$pos, $plainSecret];
    }

    #[Test]
    public function a_device_can_login_open_a_shift_collect_and_close_it_without_an_operator_identity()
    {
        $store = Store::create(['name' => 'Device Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);
        [$pos, $plainSecret] = $this->makePos($branch, 'TERM-001');

        $issuer = Issuer::create([
            'name' => 'Device Issuer',
            'sub_id' => 20,
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $paymentReference = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'reference' => '7770200000000001',
            'status' => 'pending',
            'amount_minor' => 5000,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $login = $this->postJson($this->apiUrl('pos/device-login'), [
            'code' => 'TERM-001',
            'device_secret' => $plainSecret,
        ]);
        $login->assertStatus(200);
        $token = $login->json('data.token');
        $this->assertNotEmpty($token);
        $headers = ['Authorization' => 'Bearer ' . $token];

        $openShift = $this->withHeaders($headers)->postJson($this->apiUrl('shifts'), [
            'branch_id' => $branch->getHashedIdAttribute(),
        ]);
        $openShift->assertStatus(200);
        $shiftHashid = $openShift->json('data.id');

        $this->assertNull($openShift->json('data.operator_id'));
        $this->assertSame($pos->getHashedIdAttribute(), $openShift->json('data.pos_id'));

        $this->withHeaders($headers)->postJson($this->apiUrl('transactions'), [
            'payment_reference_id' => $paymentReference->getHashedIdAttribute(),
            'amount' => 5000,
            'currency' => 'MXN',
        ])->assertStatus(200);

        $close = $this->withHeaders($headers)->patchJson(
            $this->apiUrl('shifts/' . $shiftHashid),
            ['counted_amount' => 5000]
        );
        $close->assertStatus(200);
        $this->assertSame(0, $close->json('data.discrepancy_minor'));

        $this->assertDatabaseHas('paymentgateway_shifts', [
            'id' => Shift::findByHash($shiftHashid)->id,
            'operator_id' => null,
            'pos_id' => $pos->id,
        ]);

        $this->assertSame($branch->getHashedIdAttribute(), $openShift->json('data.branch_id'));
        $this->assertDatabaseHas('paymentgateway_shifts', [
            'id' => Shift::findByHash($shiftHashid)->id,
            'branch_id' => $branch->id,
            'store_id' => $store->id,
        ]);
    }

    #[Test]
    public function device_login_rejects_the_wrong_secret()
    {
        $store = Store::create(['name' => 'Wrong Secret Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);
        $this->makePos($branch, 'TERM-002');

        $this->postJson($this->apiUrl('pos/device-login'), [
            'code' => 'TERM-002',
            'device_secret' => 'not-the-real-secret',
        ])->assertStatus(422);
    }

    #[Test]
    public function an_operator_token_cannot_close_a_shift_opened_by_a_device()
    {
        $store = Store::create(['name' => 'Mixed Identity Store']);
        $branch = Branch::create(['store_id' => $store->id, 'name' => 'Main']);
        [$pos, $plainSecret] = $this->makePos($branch, 'TERM-003');

        // isSuperUser() bypasses ownership checks for whichever user lands on
        // id 1 (default super_user_id setting) - create a throwaway user
        // first so the real operator below isn't accidentally a superuser.
        User::create(['name' => 'Filler', 'email' => 'filler@example.test', 'password' => 'x']);

        $operator = User::create([
            'name' => 'Mixed Operator',
            'email' => 'mixed-operator@example.test',
            'password' => 'secret-password',
        ]);
        OperatorBranch::create(['user_id' => $operator->id, 'branch_id' => $branch->id]);

        $deviceLogin = $this->postJson($this->apiUrl('pos/device-login'), [
            'code' => 'TERM-003',
            'device_secret' => $plainSecret,
        ]);
        $deviceToken = $deviceLogin->json('data.token');

        $openShift = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->postJson($this->apiUrl('shifts'), ['branch_id' => $branch->getHashedIdAttribute()]);
        $shiftHashid = $openShift->json('data.id');

        $operatorLogin = $this->postJson($this->apiUrl('pos/login'), [
            'email' => 'mixed-operator@example.test',
            'password' => 'secret-password',
            'branch_id' => $branch->getHashedIdAttribute(),
        ]);
        $operatorToken = $operatorLogin->json('data.token');

        // Laravel's Sanctum guard memoizes the resolved user for the lifetime
        // of the test's Application instance - without this, the PATCH below
        // would keep resolving as the device from the request above instead
        // of picking up the operator's token.
        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Authorization' => 'Bearer ' . $operatorToken])
            ->patchJson($this->apiUrl('shifts/' . $shiftHashid), ['counted_amount' => 0])
            ->assertStatus(403);
    }
}
