<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Classes\CollectionValidator;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionValidatorTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeIssuer(bool $rejectLatePayment = false): Issuer
    {
        return Issuer::create([
            'name' => 'Test Issuer',
            'sub_id' => random_int(0, 999),
            'reference_layout' => ['identifier_length' => 10],
            'reject_late_payment' => $rejectLatePayment,
        ]);
    }

    #[Test]
    public function it_accepts_any_amount_when_the_reference_has_no_embedded_amount()
    {
        $paymentReference = PaymentReference::create([
            'issuer_id' => $this->makeIssuer()->id,
            'reference' => '7770010000000042X',
            'integration_mode' => 'online',
            'status' => 'pending',
        ]);

        (new CollectionValidator())->assertAmountMatches($paymentReference, 500);

        $this->assertTrue(true); // no exception = pass
    }

    #[Test]
    public function it_accepts_an_exact_amount_match()
    {
        $paymentReference = PaymentReference::create([
            'issuer_id' => $this->makeIssuer()->id,
            'reference' => '7770010000000042X',
            'integration_mode' => 'batch',
            'status' => 'pending',
            'amount_minor' => 15230,
            'currency' => 'MXN',
        ]);

        (new CollectionValidator())->assertAmountMatches($paymentReference, 15230);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_rejects_a_mismatched_amount_when_one_is_embedded()
    {
        $paymentReference = PaymentReference::create([
            'issuer_id' => $this->makeIssuer()->id,
            'reference' => '7770010000000042X',
            'integration_mode' => 'batch',
            'status' => 'pending',
            'amount_minor' => 15230,
            'currency' => 'MXN',
        ]);

        $this->expectException(ValidationException::class);

        (new CollectionValidator())->assertAmountMatches($paymentReference, 100);
    }

    #[Test]
    public function it_allows_collection_with_no_due_date()
    {
        $paymentReference = PaymentReference::create([
            'issuer_id' => $this->makeIssuer()->id,
            'reference' => '7770010000000042X',
            'integration_mode' => 'online',
            'status' => 'pending',
        ]);

        (new CollectionValidator())->assertNotOverdue($paymentReference);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_allows_an_overdue_reference_when_the_issuer_does_not_reject_late_payment()
    {
        $paymentReference = PaymentReference::create([
            'issuer_id' => $this->makeIssuer(rejectLatePayment: false)->id,
            'reference' => '7770010000000042X',
            'integration_mode' => 'batch',
            'status' => 'pending',
            'due_date' => now()->subDay(),
        ]);

        (new CollectionValidator())->assertNotOverdue($paymentReference->fresh());

        $this->assertTrue(true);
    }

    #[Test]
    public function it_rejects_an_overdue_reference_when_the_issuer_rejects_late_payment()
    {
        $paymentReference = PaymentReference::create([
            'issuer_id' => $this->makeIssuer(rejectLatePayment: true)->id,
            'reference' => '7770010000000042X',
            'integration_mode' => 'batch',
            'status' => 'pending',
            'due_date' => now()->subDay(),
        ]);

        $this->expectException(ValidationException::class);

        (new CollectionValidator())->assertNotOverdue($paymentReference->fresh());
    }

    #[Test]
    public function it_allows_a_not_yet_due_reference_even_when_the_issuer_rejects_late_payment()
    {
        $paymentReference = PaymentReference::create([
            'issuer_id' => $this->makeIssuer(rejectLatePayment: true)->id,
            'reference' => '7770010000000042X',
            'integration_mode' => 'batch',
            'status' => 'pending',
            'due_date' => now()->addDay(),
        ]);

        (new CollectionValidator())->assertNotOverdue($paymentReference->fresh());

        $this->assertTrue(true);
    }
}
