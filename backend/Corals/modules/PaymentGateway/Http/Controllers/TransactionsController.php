<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\TransactionsDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\TransactionRequest;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\Modules\PaymentGateway\Services\TransactionService;

class TransactionsController extends BaseController
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;

        $this->resource_url = config('paymentgateway.models.transaction.resource_url');

        $this->resource_model = new Transaction();

        $this->title = trans('PaymentGateway::module.transaction.title');
        $this->title_singular = trans('PaymentGateway::module.transaction.title_singular');

        parent::__construct();
    }

    /**
     * @param TransactionRequest $request
     * @param TransactionsDataTable $dataTable
     * @return mixed
     */
    public function index(TransactionRequest $request, TransactionsDataTable $dataTable)
    {
        // No 'create' route exists for transactions (only index/show - they're only
        // ever created via the POS collect flow), so clear resourceModel: the crud
        // layout would otherwise render a create button from its genericActions.
        $this->setViewSharedData(['resource_model' => null]);

        return $dataTable->render('PaymentGateway::transactions.index');
    }

    /**
     * @param TransactionRequest $request
     * @param Transaction $transaction
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(TransactionRequest $request, Transaction $transaction)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $transaction->getIdentifier()]),
            'showModel' => $transaction,
        ]);

        return view('PaymentGateway::transactions.show')->with(compact('transaction'));
    }
}
