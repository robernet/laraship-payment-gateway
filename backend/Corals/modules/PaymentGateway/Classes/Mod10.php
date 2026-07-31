<?php

namespace Corals\Modules\PaymentGateway\Classes;

class Mod10
{
    /**
     * Compute the Luhn/Mod10 check digit for a string of digits.
     */
    public static function checkDigit(string $digits): int
    {
        $sum = 0;
        $alternate = true;

        foreach (array_reverse(str_split($digits)) as $digit) {
            $digit = (int) $digit;

            if ($alternate) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = !$alternate;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Validate a full digit string (payload + trailing check digit) against Luhn/Mod10.
     */
    public static function isValid(string $digitsWithCheckDigit): bool
    {
        $payload = substr($digitsWithCheckDigit, 0, -1);
        $checkDigit = (int) substr($digitsWithCheckDigit, -1);

        return static::checkDigit($payload) === $checkDigit;
    }
}
