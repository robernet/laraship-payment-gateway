<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\Modules\PaymentGateway\Transformers\TransactionTransformer;
use Yajra\DataTables\EloquentDataTable;

class TransactionsDataTable extends BaseDataTable
{
    /**
     * @param mixed $query
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.transaction.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new TransactionTransformer());
    }

    /**
     * @param Transaction $model
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function query(Transaction $model)
    {
        return $model->newQuery()->with(['paymentReference', 'shift.store', 'shift.operator']);
    }

    /**
     * @return array
     */
    protected function getColumns()
    {
        return [
            'id' => ['visible' => false],
            'reference' => ['title' => trans('PaymentGateway::attributes.transaction.reference')],
            'store_name' => ['title' => trans('PaymentGateway::attributes.transaction.store')],
            'operator_name' => ['title' => trans('PaymentGateway::attributes.transaction.operator')],
            'amount_minor' => ['title' => trans('PaymentGateway::attributes.transaction.amount')],
            'currency' => ['title' => trans('PaymentGateway::attributes.transaction.currency')],
            'status' => ['title' => trans('PaymentGateway::attributes.transaction.status')],
            'collected_at' => ['title' => trans('PaymentGateway::attributes.transaction.collected_at')],
        ];
    }
}
