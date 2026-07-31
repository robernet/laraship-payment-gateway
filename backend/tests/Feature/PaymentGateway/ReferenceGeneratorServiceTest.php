<?php

namespace Tests\Feature\PaymentGateway;

use Corals\Modules\PaymentGateway\Classes\Mod10;
use Corals\Modules\PaymentGateway\Classes\ReferenceGeneratorService;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReferenceGeneratorServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function it_generates_a_reference_with_correct_segments_and_a_valid_mod10_check_digit()
    {
        $issuer = new Issuer([
            'name' => 'Test Issuer',
            'sub_id' => 1,
            'reference_layout' => ['identifier_length' => 10],
        ]);

        $reference = (new ReferenceGeneratorService())->generate($issuer, '42');

        // 3 (prefix) + 3 (sub_id) + 10 (identifier) + 1 (check digit) = 17.
        $this->assertSame(17, strlen($reference));
        $this->assertSame('777', substr($reference, 0, 3));
        $this->assertSame('001', substr($reference, 3, 3));
        $this->assertSame('0000000042', substr($reference, 6, 10));
        $this->assertTrue(Mod10::isValid($reference));
    }

    #[Test]
    public function it_rejects_a_customer_id_longer_than_the_issuers_identifier_length()
    {
        $issuer = new Issuer([
            'name' => 'Test Issuer',
            'sub_id' => 2,
            'reference_layout' => ['identifier_length' => 3],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        (new ReferenceGeneratorService())->generate($issuer, '99999');
    }

    #[Test]
    public function it_embeds_amount_and_due_date_for_batch_mode_issuers()
    {
        $issuer = new Issuer([
            'name' => 'Batch Issuer',
            'sub_id' => 3,
            'reference_layout' => [
                'identifier_length' => 6,
                'amount_length' => 8,
                'embed_due_date' => true,
            ],
        ]);

        $reference = (new ReferenceGeneratorService())->generate($issuer, '42', 15230, '2026-08-15');

        // 3 (prefix) + 3 (sub_id) + 6 (identifier) + 8 (amount) + 8 (due date) + 1 (check digit) = 29.
        $this->assertSame(29, strlen($reference));
        $this->assertSame('000042', substr($reference, 6, 6));
        $this->assertSame('00015230', substr($reference, 12, 8));
        $this->assertSame('20260815', substr($reference, 20, 8));
        $this->assertTrue(Mod10::isValid($reference));
    }

    #[Test]
    public function it_rejects_missing_amount_when_the_issuer_requires_one()
    {
        $issuer = new Issuer([
            'name' => 'Batch Issuer',
            'sub_id' => 4,
            'reference_layout' => ['identifier_length' => 10, 'amount_length' => 8],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        (new ReferenceGeneratorService())->generate($issuer, '42');
    }

    #[Test]
    public function it_rejects_missing_due_date_when_the_issuer_requires_one()
    {
        $issuer = new Issuer([
            'name' => 'Batch Issuer',
            'sub_id' => 5,
            'reference_layout' => ['identifier_length' => 10, 'embed_due_date' => true],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        (new ReferenceGeneratorService())->generate($issuer, '42');
    }
}
