<?php

return [
    'models' => [
        'store' => [
            'presenter' => \Corals\Modules\PaymentGateway\Transformers\StorePresenter::class,
            'resource_url' => 'stores',
        ],
        'issuer' => [
            'resource_url' => 'issuers',
        ],
        'payment_reference' => [
            'resource_url' => 'payment-references',
        ],
        'transaction' => [
            'resource_url' => 'transactions',
        ],
        'shift' => [
            'resource_url' => 'shifts',
        ],
        'autopay_schedule' => [
            'resource_url' => 'autopay-schedules',
        ],
    ],
];
