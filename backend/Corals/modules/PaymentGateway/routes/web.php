<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => ''], function () {
    Route::resource('stores', 'StoresController');
    Route::resource('payment-references', 'PaymentReferencesController')->only(['index', 'create', 'store', 'show']);
    Route::resource('transactions', 'TransactionsController')->only(['index', 'show']);
    Route::resource('shifts', 'ShiftsController')->only(['index', 'show']);
});
