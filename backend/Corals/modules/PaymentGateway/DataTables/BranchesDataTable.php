<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Transformers\BranchTransformer;
use Yajra\DataTables\EloquentDataTable;

class BranchesDataTable extends BaseDataTable
{
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.branch.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new BranchTransformer());
    }

    public function query(Branch $model)
    {
        return $model->newQuery()->with('store');
    }

    protected function getColumns()
    {
        return [
            'id' => ['visible' => false],
            'name' => ['title' => trans('PaymentGateway::attributes.branch.name')],
            'store' => ['title' => trans('PaymentGateway::attributes.branch.store_id'), 'searchable' => false, 'orderable' => false],
            'created_at' => ['title' => trans('Corals::attributes.created_at')],
            'updated_at' => ['title' => trans('Corals::attributes.updated_at')],
        ];
    }
}
