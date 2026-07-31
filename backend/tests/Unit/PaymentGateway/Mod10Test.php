<?php

namespace Tests\Unit\PaymentGateway;

use Corals\Modules\PaymentGateway\Classes\Mod10;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class Mod10Test extends TestCase
{
    #[Test]
    public function it_computes_the_classic_luhn_test_vector()
    {
        // Wikipedia's canonical Luhn example: payload 7992739871 -> check digit 3,
        // full valid number 79927398713.
        $this->assertSame(3, Mod10::checkDigit('7992739871'));
    }

    #[Test]
    public function it_validates_a_correct_full_number()
    {
        $this->assertTrue(Mod10::isValid('79927398713'));
    }

    #[Test]
    public function it_rejects_a_tampered_check_digit()
    {
        $this->assertFalse(Mod10::isValid('79927398714'));
    }

    #[Test]
    public function it_rejects_a_single_miskeyed_digit()
    {
        // Miskey the second digit (9 -> 8): 78927398713 must fail Luhn.
        $this->assertFalse(Mod10::isValid('78927398713'));
    }
}
