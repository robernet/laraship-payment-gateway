<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Models\AutopaySchedule;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SCAFFOLD ONLY (Phase 4) - confirms the schema/model exist and relate
 * correctly. No gateway is wired yet - see docs/roadmap.md.
 */
class AutopayScheduleScaffoldTest extends TestCase
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

    #[Test]
    public function it_creates_an_autopay_schedule_linked_to_a_payment_reference()
    {
        $issuer = Issuer::create([
            'name' => 'Scaffold Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $paymentReference = PaymentReference::create([
            'issuer_id' => $issuer->id,
            'reference' => '7770010000000042X',
            'integration_mode' => 'online',
            'status' => 'pending',
            'autopay_enabled' => true,
            'autopay_payment_number' => 3,
            'autopay_frequency_days' => 30,
        ]);

        $schedule = AutopaySchedule::create([
            'payment_reference_id' => $paymentReference->id,
            'status' => 'scheduled',
            'next_charge_date' => now()->addDays(30),
        ]);

        $this->assertSame($paymentReference->id, $schedule->paymentReference->id);
        $this->assertSame('scheduled', $schedule->status);
        $this->assertSame(0, $schedule->fresh()->retry_count);
    }
}
