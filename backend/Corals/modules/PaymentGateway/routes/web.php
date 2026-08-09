<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => ''], function () {
    Route::resource('stores', 'StoresController');
    Route::post('stores/{store}/operators', 'StoresController@assignOperator')->name('paymentgateway.stores.operators.assign');
    Route::delete('stores/{store}/operators/{user}', 'StoresController@removeOperator')->name('paymentgateway.stores.operators.remove');
    Route::resource('pos', 'PosController', ['parameters' => ['pos' => 'pos']]);
    Route::post('pos/{pos}/regenerate-secret', 'PosController@regenerateSecret')->name('paymentgateway.pos.regenerate_secret');
    Route::resource('issuers', 'IssuersController');
    Route::resource('invoices', 'InvoicesController')->except(['destroy']);
    Route::resource('payment-references', 'PaymentReferencesController')->only(['index', 'create', 'store', 'show']);
    Route::resource('transactions', 'TransactionsController')->only(['index', 'show']);
    Route::resource('shifts', 'ShiftsController')->only(['index', 'show']);
    Route::get('reports', 'ReportController@index')->name('paymentgateway.reports.index');
});
