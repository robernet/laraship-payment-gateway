<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => ''], function () {
    Route::resource('stores', 'StoresController');
    Route::resource('branches', 'BranchesController');
    Route::post('branches/{branch}/operators', 'BranchesController@assignOperator')->name('paymentgateway.branches.operators.assign');
    Route::delete('branches/{branch}/operators/{user}', 'BranchesController@removeOperator')->name('paymentgateway.branches.operators.remove');
    Route::resource('pos', 'PosController', ['parameters' => ['pos' => 'pos']]);
    Route::post('pos/{pos}/regenerate-secret', 'PosController@regenerateSecret')->name('paymentgateway.pos.regenerate_secret');
    Route::resource('issuers', 'IssuersController');
    Route::resource('invoices', 'InvoicesController')->except(['destroy']);
    Route::resource('payment-references', 'PaymentReferencesController')->only(['index', 'create', 'store', 'show']);
    Route::resource('transactions', 'TransactionsController')->only(['index', 'show']);
    Route::resource('shifts', 'ShiftsController')->only(['index', 'show']);
    Route::get('reports', 'ReportController@index')->name('paymentgateway.reports.index');
});
