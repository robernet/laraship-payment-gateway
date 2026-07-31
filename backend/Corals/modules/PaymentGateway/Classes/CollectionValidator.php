<?php

namespace Corals\Modules\PaymentGateway\Classes;

use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Illuminate\Validation\ValidationException;

class CollectionValidator
{
    /**
     * If the reference embeds an exact amount, the collected amount must match it
     * exactly. If no amount is embedded, any positive amount is accepted (partial
     * or full payment), per the Implementation Guide.
     */
    public function assertAmountMatches(PaymentReference $paymentReference, int $amountMinor): void
    {
        if (!is_null($paymentReference->amount_minor) && $paymentReference->amount_minor !== $amountMinor) {
            throw ValidationException::withMessages([
                'amount' => ["This reference requires an exact amount of {$paymentReference->amount_minor}."],
            ]);
        }
    }

    /**
     * A due date is only ever a rejection trigger, never a requirement to
     * collect early - "embed only to reject late payment" per the Implementation Guide.
     */
    public function assertNotOverdue(PaymentReference $paymentReference): void
    {
        if (!$paymentReference->due_date) {
            return;
        }

        if ($paymentReference->due_date->isPast() && $paymentReference->issuer->reject_late_payment) {
            throw ValidationException::withMessages([
                'payment_reference_id' => ['This reference is overdue and can no longer be collected.'],
            ]);
        }
    }
}
