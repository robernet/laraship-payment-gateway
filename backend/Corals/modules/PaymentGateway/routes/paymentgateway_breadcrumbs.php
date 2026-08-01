<?php

//Store
Breadcrumbs::register('paymentgateway_stores', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.store.title'), url(config('paymentgateway.models.store.resource_url')));
});

Breadcrumbs::register('paymentgateway_store_create_edit', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_stores');
    $breadcrumbs->push(view()->shared('title_singular'));
});

Breadcrumbs::register('paymentgateway_store_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_stores');
    $breadcrumbs->push(view()->shared('title_singular'));
});

//PaymentReference
Breadcrumbs::register('paymentgateway_payment_references', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.payment_reference.title'), url(config('paymentgateway.models.payment_reference.resource_url')));
});

Breadcrumbs::register('paymentgateway_payment_reference_create_edit', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_payment_references');
    $breadcrumbs->push(view()->shared('title_singular'));
});

Breadcrumbs::register('paymentgateway_payment_reference_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_payment_references');
    $breadcrumbs->push(view()->shared('title_singular'));
});

//Transaction
Breadcrumbs::register('paymentgateway_transactions', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.transaction.title'), url(config('paymentgateway.models.transaction.resource_url')));
});

Breadcrumbs::register('paymentgateway_transaction_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_transactions');
    $breadcrumbs->push(view()->shared('title_singular'));
});

//Shift
Breadcrumbs::register('paymentgateway_shifts', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.shift.title'), url(config('paymentgateway.models.shift.resource_url')));
});

Breadcrumbs::register('paymentgateway_shift_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_shifts');
    $breadcrumbs->push(view()->shared('title_singular'));
});
