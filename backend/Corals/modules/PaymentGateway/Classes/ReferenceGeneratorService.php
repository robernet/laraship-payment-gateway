<?php

namespace Corals\Modules\PaymentGateway\Classes;

use Corals\Modules\PaymentGateway\Models\Issuer;
use DateTimeInterface;
use InvalidArgumentException;

class ReferenceGeneratorService
{
    /**
     * Build a Reference for the given issuer and customer identifier.
     *
     * Format: PREFIX(from Settings, always zero-padded to 3 digits) + SUB_ID(issuer, 3 digits)
     *         + IDENTIFIER(customer id [+ optional amount] [+ optional due date], zero-padded per the
     *         issuer's layout) + DV(1, Mod10). Max 29 digits total.
     *
     * Batch mode (amount/due-date embedding) is driven entirely by the issuer's own
     * `reference_layout` - if it declares `amount_length` and/or `embed_due_date`, this
     * issuer requires those arguments; there is no separate "mode" flag to pass in.
     *
     * @param int|null $amountMinor Integer minor units - required if the issuer's layout declares `amount_length`.
     * @param DateTimeInterface|string|null $dueDate Required if the issuer's layout sets `embed_due_date`.
     */
    public function generate(Issuer $issuer, string $customerId, ?int $amountMinor = null, DateTimeInterface|string|null $dueDate = null): string
    {
        $prefix = str_pad((string) \Settings::get('paymentgateway_id', '777'), 3, '0', STR_PAD_LEFT);
        $subId = str_pad((string) $issuer->sub_id, 3, '0', STR_PAD_LEFT);

        $identifierLength = (int) data_get($issuer->reference_layout, 'identifier_length', 15);
        $identifier = str_pad($customerId, $identifierLength, '0', STR_PAD_LEFT);

        if (strlen($identifier) > $identifierLength) {
            throw new InvalidArgumentException("Customer id '{$customerId}' exceeds the issuer's identifier length ({$identifierLength}).");
        }

        $amountSegment = '';
        $amountLength = data_get($issuer->reference_layout, 'amount_length');

        if ($amountLength) {
            if (is_null($amountMinor)) {
                throw new InvalidArgumentException('This issuer requires an amount to be embedded in the reference.');
            }

            $amountSegment = str_pad((string) $amountMinor, $amountLength, '0', STR_PAD_LEFT);

            if (strlen($amountSegment) > $amountLength) {
                throw new InvalidArgumentException("Amount exceeds the issuer's amount segment length ({$amountLength}).");
            }
        }

        $dueDateSegment = '';

        if (data_get($issuer->reference_layout, 'embed_due_date')) {
            if (is_null($dueDate)) {
                throw new InvalidArgumentException('This issuer requires a due date to be embedded in the reference.');
            }

            $dueDateSegment = ($dueDate instanceof DateTimeInterface ? $dueDate : new \DateTimeImmutable($dueDate))->format('Ymd');
        }

        $payload = $prefix . $subId . $identifier . $amountSegment . $dueDateSegment;

        if (strlen($payload) > 28) {
            throw new InvalidArgumentException('Reference payload exceeds 28 digits (29 with check digit).');
        }

        return $payload . Mod10::checkDigit($payload);
    }
}
