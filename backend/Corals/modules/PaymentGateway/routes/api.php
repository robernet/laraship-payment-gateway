<?php

Route::post('pos/login', 'PosAuthController@login');
Route::post('pos/device-login', 'PosAuthController@deviceLogin');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::apiResource('issuers', 'IssuersController', ['as' => 'api.paymentgateway.issuer']);

    Route::apiResource('invoices', 'InvoicesController', ['as' => 'api.paymentgateway.invoice'])->only(['index', 'store', 'show']);

    Route::post('payment-references', 'PaymentReferencesController@store')->name('api.paymentgateway.payment_reference.store');
    Route::get('payment-references/lookup/{reference}', 'PaymentReferencesController@lookup')->name('api.paymentgateway.payment_reference.lookup');

    Route::post('transactions', 'TransactionsController@store')->name('api.paymentgateway.transaction.store');

    Route::post('shifts', 'ShiftsController@store')->name('api.paymentgateway.shift.store');
    Route::patch('shifts/{shift}', 'ShiftsController@update')->name('api.paymentgateway.shift.update');
});
