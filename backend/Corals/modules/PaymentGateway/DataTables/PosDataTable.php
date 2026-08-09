<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Transformers\PosTransformer;
use Yajra\DataTables\EloquentDataTable;

class PosDataTable extends BaseDataTable
{
    /**
     * @param mixed $query
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.pos.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new PosTransformer());
    }

    /**
     * @param Pos $model
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function query(Pos $model)
    {
        return $model->newQuery()->with('store');
    }

    /**
     * @return array
     */
    protected function getColumns()
    {
        return [
            'id' => ['visible' => false],
            'name' => ['title' => trans('PaymentGateway::attributes.pos.name')],
            'code' => ['title' => trans('PaymentGateway::attributes.pos.code')],
            'store_name' => ['title' => trans('PaymentGateway::attributes.pos.store_id')],
            'created_at' => ['title' => trans('Corals::attributes.created_at')],
            'updated_at' => ['title' => trans('Corals::attributes.updated_at')],
        ];
    }
}
