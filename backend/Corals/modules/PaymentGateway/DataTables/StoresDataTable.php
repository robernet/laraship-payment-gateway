<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Transformers\StoreTransformer;
use Yajra\DataTables\EloquentDataTable;

class StoresDataTable extends BaseDataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.store.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new StoreTransformer());
    }

    /**
     * Get query source of dataTable.
     * @param Store $model
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function query(Store $model)
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
            'name' => ['title' => trans('PaymentGateway::attributes.store.name')],
            'created_at' => ['title' => trans('Corals::attributes.created_at')],
            'updated_at' => ['title' => trans('Corals::attributes.updated_at')],
        ];
    }
}
