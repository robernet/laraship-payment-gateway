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

//Branch
Breadcrumbs::register('paymentgateway_branches', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.branch.title'), url(config('paymentgateway.models.branch.resource_url')));
});

Breadcrumbs::register('paymentgateway_branch_create_edit', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_branches');
    $breadcrumbs->push(view()->shared('title_singular'));
});

Breadcrumbs::register('paymentgateway_branch_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_branches');
    $breadcrumbs->push(view()->shared('title_singular'));
});

//Pos
Breadcrumbs::register('paymentgateway_pos', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.pos.title'), url(config('paymentgateway.models.pos.resource_url')));
});

Breadcrumbs::register('paymentgateway_pos_create_edit', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_pos');
    $breadcrumbs->push(view()->shared('title_singular'));
});

Breadcrumbs::register('paymentgateway_pos_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_pos');
    $breadcrumbs->push(view()->shared('title_singular'));
});

//Issuer
Breadcrumbs::register('paymentgateway_issuers', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.issuer.title'), url(config('paymentgateway.models.issuer.resource_url')));
});

Breadcrumbs::register('paymentgateway_issuer_create_edit', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_issuers');
    $breadcrumbs->push(view()->shared('title_singular'));
});

Breadcrumbs::register('paymentgateway_issuer_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_issuers');
    $breadcrumbs->push(view()->shared('title_singular'));
});

//Invoice
Breadcrumbs::register('paymentgateway_invoices', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.invoice.title'), url(config('paymentgateway.models.invoice.resource_url')));
});

Breadcrumbs::register('paymentgateway_invoice_create_edit', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_invoices');
    $breadcrumbs->push(view()->shared('title_singular'));
});

Breadcrumbs::register('paymentgateway_invoice_show', function ($breadcrumbs) {
    $breadcrumbs->parent('paymentgateway_invoices');
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

//Report
Breadcrumbs::register('paymentgateway_reports', function ($breadcrumbs) {
    $breadcrumbs->parent('dashboard');
    $breadcrumbs->push(trans('PaymentGateway::module.report.title'), url('reports'));
});
