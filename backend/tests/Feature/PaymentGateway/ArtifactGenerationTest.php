<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Classes\BarcodeGeneratorService;
use Corals\Modules\PaymentGateway\Classes\PayFormatGeneratorService;
use Corals\Modules\PaymentGateway\Classes\ReferenceGeneratorService;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Services\PaymentReferenceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtifactGenerationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Same known limitation as CollectFlowTest (Phase 1): the dynamic
     * module-loading system reads the `modules` DB table while providers
     * boot, before this test's own body runs, so its enabled/disabled state
     * is flaky across test runs. Pre-seed it before the kernel boots as a
     * partial mitigation - see CollectFlowTest for the full writeup.
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
            // modules table doesn't exist yet (very first run before any migration) - skip.
        }

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    private function makeIssuer(): Issuer
    {
        return Issuer::create([
            'name' => 'Artifact Test Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
        ]);
    }

    #[Test]
    public function barcode_generator_produces_a_valid_png_and_stores_it_on_the_public_disk()
    {
        Storage::fake('public');

        $url = (new BarcodeGeneratorService())->generate('7770010000000042X');

        $this->assertStringContainsString('7770010000000042X.png', $url);
        Storage::disk('public')->assertExists('paymentgateway/barcodes/7770010000000042X.png');

        $contents = Storage::disk('public')->get('paymentgateway/barcodes/7770010000000042X.png');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($contents, 0, 8));
    }

    #[Test]
    public function payment_reference_service_generates_a_reference_with_folio_barcode_and_pay_format()
    {
        Storage::fake('public');

        $issuer = $this->makeIssuer();

        $invoice = Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => '42',
            'amount_minor' => 15230,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ]);

        $paymentReference = (new PaymentReferenceService())->generateWithArtifacts(
            $invoice,
            new ReferenceGeneratorService(),
            new BarcodeGeneratorService(),
            new PayFormatGeneratorService()
        );

        $this->assertNotEmpty($paymentReference->reference);
        $this->assertStringStartsWith('FOL-', $paymentReference->folio);
        $this->assertNotEmpty($paymentReference->barcode_url);
        $this->assertNotEmpty($paymentReference->pay_format_url);
        $this->assertSame('online', $paymentReference->integration_mode);

        $this->assertDatabaseHas('paymentgateway_payment_references', [
            'id' => $paymentReference->id,
            'reference' => $paymentReference->reference,
        ]);

        // The pay-format slip embeds the barcode as an <img> data URI.
        $html = view('PaymentGateway::payment_references.pay_format', [
            'paymentReference' => $paymentReference,
            'barcodeDataUri' => 'data:image/png;base64,TESTBARCODE',
        ])->render();
        $this->assertStringContainsString('src="data:image/png;base64,TESTBARCODE"', $html);
    }

    #[Test]
    public function payment_reference_service_marks_batch_mode_when_the_issuer_layout_requires_it()
    {
        Storage::fake('public');

        $issuer = Issuer::create([
            'name' => 'Batch Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 6, 'amount_length' => 8],
        ]);

        $invoice = Invoice::create([
            'issuer_id' => $issuer->id,
            'customer_id' => '42',
            'amount_minor' => 15230,
            'currency' => 'MXN',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'unpaid',
        ]);

        $paymentReference = (new PaymentReferenceService())->generateWithArtifacts(
            $invoice,
            new ReferenceGeneratorService(),
            new BarcodeGeneratorService(),
            new PayFormatGeneratorService()
        );

        $this->assertSame('batch', $paymentReference->integration_mode);
        $this->assertSame(15230, $paymentReference->amount_minor);
    }
}
