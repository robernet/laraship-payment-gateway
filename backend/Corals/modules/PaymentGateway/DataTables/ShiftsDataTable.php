<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Transformers\ShiftTransformer;
use Yajra\DataTables\EloquentDataTable;

class ShiftsDataTable extends BaseDataTable
{
    /**
     * @param mixed $query
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.shift.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new ShiftTransformer());
    }

    /**
     * @param Shift $model
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function query(Shift $model)
    {
        return $model->newQuery()->with(['store', 'operator']);
    }

    /**
     * @return array
     */
    protected function getColumns()
    {
        return [
            'id' => ['visible' => false],
            'store_name' => ['title' => trans('PaymentGateway::attributes.shift.store')],
            'operator_name' => ['title' => trans('PaymentGateway::attributes.shift.operator')],
            'opened_at' => ['title' => trans('PaymentGateway::attributes.shift.opened_at')],
            'closed_at' => ['title' => trans('PaymentGateway::attributes.shift.closed_at')],
            'counted_amount_minor' => ['title' => trans('PaymentGateway::attributes.shift.counted_amount_minor')],
            'discrepancy_minor' => ['title' => trans('PaymentGateway::attributes.shift.discrepancy_minor')],
        ];
    }
}
