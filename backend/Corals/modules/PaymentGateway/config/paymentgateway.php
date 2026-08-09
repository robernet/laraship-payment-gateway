<?php

return [
    'models' => [
        'autopay_schedule' => [
            'resource_url' => 'autopay-schedules',
        ],
        'invoice' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\InvoicePresenter::class,
            'resource_url' => 'invoices',
        ],
        'issuer' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\IssuerPresenter::class,
            'resource_url' => 'issuers',
        ],
        'pos' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\PosPresenter::class,
            'resource_url' => 'pos',
        ],
        'payment_reference' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\PaymentReferencePresenter::class,
            'resource_url' => 'payment-references',
        ],
        'transaction' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\TransactionPresenter::class,
            'resource_url' => 'transactions',
        ],
        'shift' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\ShiftPresenter::class,
            'resource_url' => 'shifts',
        ],
        'store' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\StorePresenter::class,
            'resource_url' => 'stores',
        ],
    ],
];
