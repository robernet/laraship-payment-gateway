<?php

namespace Corals\Modules\PaymentGateway\database\seeds;

use Corals\Modules\PaymentGateway\Classes\BarcodeGeneratorService;
use Corals\Modules\PaymentGateway\Classes\CollectionValidator;
use Corals\Modules\PaymentGateway\Classes\PayFormatGeneratorService;
use Corals\Modules\PaymentGateway\Classes\ReferenceGeneratorService;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\OperatorStore;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\Modules\PaymentGateway\Services\InvoiceService;
use Corals\Modules\PaymentGateway\Services\IssuerService;
use Corals\Modules\PaymentGateway\Services\PaymentReferenceService;
use Corals\Modules\PaymentGateway\Services\ShiftService;
use Corals\Modules\PaymentGateway\Services\StoreService;
use Corals\Modules\PaymentGateway\Services\TransactionService;
use Corals\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

/**
 * Load-test data for the PaymentGateway module, driven through the real
 * services (IssuerService/InvoiceService/.../PaymentReferenceService's
 * generateWithArtifacts, TransactionsController's real collect logic) rather
 * than raw inserts, so the barcode/PDF pipeline and the collect/overdue/
 * discrepancy business rules all actually run.
 *
 * Run: php artisan db:seed --class="Corals\Modules\PaymentGateway\database\seeds\PaymentGatewayStressTestSeeder"
 *
 * Not wired into PaymentGatewayDatabaseSeeder - this is dev/stress data, not
 * part of module install.
 */
class PaymentGatewayStressTestSeeder extends Seeder
{
    private IssuerService $issuerService;
    private StoreService $storeService;
    private InvoiceService $invoiceService;
    private PaymentReferenceService $paymentReferenceService;
    private ShiftService $shiftService;
    private TransactionService $transactionService;

    public function run()
    {
        $this->issuerService = new IssuerService();
        $this->storeService = new StoreService();
        $this->invoiceService = new InvoiceService();
        $this->paymentReferenceService = new PaymentReferenceService();
        $this->shiftService = new ShiftService();
        $this->transactionService = new TransactionService();

        $issuers = $this->createIssuers();
        $stores = $this->createStores();
        $operators = $this->createOperators($stores);
        $invoices = $this->createInvoices($issuers);

        [$pendingRefs, $toCollectRefs] = $this->createPaymentReferences($invoices);
        $this->attemptBlockedCollections($pendingRefs);
        $this->collectTransactions($toCollectRefs, $stores, $operators);

        $this->command?->info(sprintf(
            'Stress seed done: %d issuers, %d stores, %d operators, %d invoices, %d payment references, %d transactions.',
            count($issuers),
            count($stores),
            count($operators),
            count($invoices),
            count($pendingRefs) + count($toCollectRefs),
            count($toCollectRefs)
        ));
    }

    /** @return Issuer[] */
    private function createIssuers(): array
    {
        $specs = [
            ['name' => 'Stress Issuer Online A', 'sub_id' => 900, 'layout' => ['identifier_length' => 10], 'reject_late_payment' => false],
            ['name' => 'Stress Issuer Online B', 'sub_id' => 901, 'layout' => ['identifier_length' => 12], 'reject_late_payment' => true],
            ['name' => 'Stress Issuer Batch Amount', 'sub_id' => 902, 'layout' => ['identifier_length' => 8, 'amount_length' => 8], 'reject_late_payment' => false],
            ['name' => 'Stress Issuer Batch Amount+Due', 'sub_id' => 903, 'layout' => ['identifier_length' => 6, 'amount_length' => 6, 'embed_due_date' => true], 'reject_late_payment' => true],
            ['name' => 'Stress Issuer Batch Due', 'sub_id' => 904, 'layout' => ['identifier_length' => 10, 'embed_due_date' => true], 'reject_late_payment' => false],
        ];

        return array_map(fn ($spec) => $this->issuerService->store([], Issuer::class, [
            'name' => $spec['name'],
            'sub_id' => $spec['sub_id'],
            'reference_layout' => $spec['layout'],
            'reject_late_payment' => $spec['reject_late_payment'],
        ]), $specs);
    }

    /** @return Store[] */
    private function createStores(): array
    {
        return array_map(
            fn ($i) => $this->storeService->store([], Store::class, ['name' => "Stress Store {$i}"]),
            range(1, 10)
        );
    }

    /**
     * Each operator is assigned to `$stores[index % count($stores)]` - the same
     * store index `collectTransactions()` later opens that operator's shift
     * against, so the POS login credential and the pre-seeded shift/transaction
     * data line up against the same store.
     *
     * @return User[]
     */
    private function createOperators(array $stores): array
    {
        return array_map(function ($i) use ($stores) {
            $operator = User::create([
                'name' => "Stress Operator {$i}",
                'email' => "stress-operator-{$i}@example.test",
                'password' => 'stress-test-password',
            ]);

            OperatorStore::create([
                'user_id' => $operator->id,
                'store_id' => $stores[($i - 1) % count($stores)]->id,
            ]);

            return $operator;
        }, range(1, 4));
    }

    /**
     * 35 invoices round-robined across the 5 issuers: 5 stay unpaid with no
     * reference at all, 10 get a reference that's never collected (7 normal +
     * 3 deliberately overdue against reject_late_payment issuers), 20 get a
     * reference that's collected end-to-end (invoice flips to paid).
     *
     * @return Invoice[]
     */
    private function createInvoices(array $issuers): array
    {
        $invoices = [];
        $rejectLatePaymentIssuers = array_values(array_filter($issuers, fn ($issuer) => $issuer->reject_late_payment));

        for ($i = 0; $i < 35; $i++) {
            // Indices 5-7 are the first 3 invoices of the "pending" bucket (see
            // createPaymentReferences) - deliberately overdue against a
            // reject_late_payment issuer, to exercise the collection-blocked path.
            $isOverdueDemo = $i >= 5 && $i < 8;
            $issuer = $isOverdueDemo
                ? $rejectLatePaymentIssuers[($i - 5) % count($rejectLatePaymentIssuers)]
                : $issuers[$i % count($issuers)];

            $invoices[] = $this->invoiceService->store([], Invoice::class, [
                'issuer_id' => $issuer->id,
                'customer_id' => "stress-cust-{$i}",
                'amount_minor' => random_int(50, 5000) * 100,
                'currency' => 'MXN',
                'due_date' => $isOverdueDemo
                    ? now()->subDays(10)->toDateString()
                    : now()->addDays(random_int(1, 30))->toDateString(),
                'description' => "Stress test invoice #{$i}",
            ]);
        }

        return $invoices;
    }

    /**
     * @return array{0: PaymentReference[], 1: PaymentReference[]} [pendingNeverCollected, toCollect]
     */
    private function createPaymentReferences(array $invoices): array
    {
        $generator = new ReferenceGeneratorService();
        $barcodeGenerator = new BarcodeGeneratorService();
        $payFormatGenerator = new PayFormatGeneratorService();

        $pending = [];
        $toCollect = [];

        // First 5 invoices stay bare (no reference) - available in the "existing invoice" picker.
        // Next 10 get a reference that's never collected. Remaining 20 get collected.
        foreach (array_slice($invoices, 5, 10) as $index => $invoice) {
            $autopay = $index % 4 === 0;

            $pending[] = $this->paymentReferenceService->generateWithArtifacts(
                $invoice->fresh(),
                $generator,
                $barcodeGenerator,
                $payFormatGenerator,
                $autopay,
                $autopay ? 6 : null,
                $autopay ? 30 : null
            );
        }

        foreach (array_slice($invoices, 15, 20) as $index => $invoice) {
            $autopay = $index % 5 === 0;

            $toCollect[] = $this->paymentReferenceService->generateWithArtifacts(
                $invoice->fresh(),
                $generator,
                $barcodeGenerator,
                $payFormatGenerator,
                $autopay,
                $autopay ? 3 : null,
                $autopay ? 15 : null
            );
        }

        return [$pending, $toCollect];
    }

    /**
     * The 3 references generated against a past due_date + reject_late_payment
     * issuer should be rejected by the real CollectionValidator - prove it and
     * leave them pending, exactly like a real blocked collection attempt.
     */
    private function attemptBlockedCollections(array $pendingRefs): void
    {
        $collectionValidator = new CollectionValidator();
        $blocked = 0;

        foreach ($pendingRefs as $paymentReference) {
            if (!$paymentReference->due_date?->isPast() || !$paymentReference->issuer->reject_late_payment) {
                continue;
            }

            try {
                $collectionValidator->assertNotOverdue($paymentReference);
            } catch (ValidationException) {
                $blocked++;
            }
        }

        $this->command?->info("Confirmed {$blocked} overdue reference(s) correctly rejected by CollectionValidator.");
    }

    /**
     * Opens one shift per operator, collects 5 references each via the real
     * CollectionValidator + TransactionService (mirroring TransactionsController::store),
     * then closes each shift with a deliberately varied counted amount so
     * discrepancy_minor comes out at 0, over, and short across shifts.
     */
    private function collectTransactions(array $toCollectRefs, array $stores, array $operators): void
    {
        $collectionValidator = new CollectionValidator();
        $countedDeltas = [0, 500, -300, 0];
        $chunks = array_chunk($toCollectRefs, (int) ceil(count($toCollectRefs) / count($operators)));

        foreach ($operators as $i => $operator) {
            $shift = $this->shiftService->store([], Shift::class, [
                'store_id' => $stores[$i % count($stores)]->id,
                'operator_id' => $operator->id,
                'opened_at' => now()->subHours(4),
            ]);

            $collected = 0;

            foreach ($chunks[$i] ?? [] as $paymentReference) {
                $collectionValidator->assertAmountMatches($paymentReference, $paymentReference->amount_minor);
                $collectionValidator->assertNotOverdue($paymentReference);

                $this->transactionService->store([], Transaction::class, [
                    'payment_reference_id' => $paymentReference->id,
                    'shift_id' => $shift->id,
                    'amount_minor' => $paymentReference->amount_minor,
                    'currency' => $paymentReference->currency,
                    'collected_at' => now(),
                    'status' => 'settled',
                ]);

                $paymentReference->update(['status' => 'collected']);
                $paymentReference->invoice->update(['status' => 'paid']);

                $collected += $paymentReference->amount_minor;
            }

            $countedMinor = $collected + $countedDeltas[$i % count($countedDeltas)];

            $this->shiftService->update([], $shift, [
                'closed_at' => now(),
                'counted_amount_minor' => $countedMinor,
                'discrepancy_minor' => $countedMinor - $collected,
            ]);
        }
    }
}
