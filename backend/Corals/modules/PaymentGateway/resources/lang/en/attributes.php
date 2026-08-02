<?php

return [
    'store' => [
        'name' => 'Name',
    ],
    'issuer' => [
        'name' => 'Name',
        'sub_id' => 'Sub ID',
        'reject_late_payment' => 'Reject late payment',
        'reference_layout' => 'Reference layout',
        'identifier_length' => 'Identifier length',
        'amount_length' => 'Amount length',
        'embed_due_date' => 'Embed due date',
    ],
    'payment_reference' => [
        'issuer_id' => 'Issuer',
        'customer_id' => 'Customer ID',
        'amount' => 'Amount (minor units)',
        'amount_input' => 'Amount',
        'generating_for' => 'Generating for',
        'currency' => 'Currency',
        'due_date' => 'Due date',
        'reference' => 'Reference',
        'folio' => 'Folio',
        'status' => 'Status',
        'integration_mode' => 'Integration mode',
        'pay_format_url' => 'Payment slip (PDF)',
    ],
    'transaction' => [
        'reference' => 'Reference',
        'store' => 'Store',
        'operator' => 'Operator',
        'amount' => 'Amount (minor units)',
        'currency' => 'Currency',
        'status' => 'Status',
        'collected_at' => 'Collected at',
    ],
    'shift' => [
        'store' => 'Store',
        'operator' => 'Operator',
        'opened_at' => 'Opened at',
        'closed_at' => 'Closed at',
        'counted_amount_minor' => 'Counted amount (minor units)',
        'discrepancy_minor' => 'Discrepancy (minor units)',
    ],
];
