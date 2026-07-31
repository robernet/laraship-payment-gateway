<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Transformers\PaymentReferenceTransformer;
use Yajra\DataTables\EloquentDataTable;

class PaymentReferencesDataTable extends BaseDataTable
{
    /**
     * @param mixed $query
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.payment_reference.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new PaymentReferenceTransformer());
    }

    /**
     * @param PaymentReference $model
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function query(PaymentReference $model)
    {
        return $model->newQuery()->with('issuer');
    }

    /**
     * @return array
     */
    protected function getColumns()
    {
        return [
            'id' => ['visible' => false],
            'reference' => ['title' => trans('PaymentGateway::attributes.payment_reference.reference')],
            'issuer_name' => ['title' => trans('PaymentGateway::attributes.payment_reference.issuer_id')],
            'status' => ['title' => trans('PaymentGateway::attributes.payment_reference.status')],
            'amount_minor' => ['title' => trans('PaymentGateway::attributes.payment_reference.amount')],
            'currency' => ['title' => trans('PaymentGateway::attributes.payment_reference.currency')],
            'due_date' => ['title' => trans('PaymentGateway::attributes.payment_reference.due_date')],
            'created_at' => ['title' => trans('Corals::attributes.created_at')],
        ];
    }
}
