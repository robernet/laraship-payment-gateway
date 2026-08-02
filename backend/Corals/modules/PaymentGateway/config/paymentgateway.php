<?php

return [
    'models' => [
        'store' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\StorePresenter::class,
            'resource_url' => 'stores',
        ],
        'issuer' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\IssuerPresenter::class,
            'resource_url' => 'issuers',
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
        'autopay_schedule' => [
            'resource_url' => 'autopay-schedules',
        ],
    ],
];
