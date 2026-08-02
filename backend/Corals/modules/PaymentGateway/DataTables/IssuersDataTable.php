<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Transformers\IssuerTransformer;
use Yajra\DataTables\EloquentDataTable;

class IssuersDataTable extends BaseDataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.issuer.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new IssuerTransformer());
    }

    /**
     * Get query source of dataTable.
     * @param Issuer $model
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function query(Issuer $model)
    {
        return $model->newQuery();
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'id' => ['visible' => false],
            'name' => ['title' => trans('PaymentGateway::attributes.issuer.name')],
            'sub_id' => ['title' => trans('PaymentGateway::attributes.issuer.sub_id')],
            'reject_late_payment' => ['title' => trans('PaymentGateway::attributes.issuer.reject_late_payment')],
            'created_at' => ['title' => trans('Corals::attributes.created_at')],
            'updated_at' => ['title' => trans('Corals::attributes.updated_at')],
        ];
    }
}
