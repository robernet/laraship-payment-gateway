<?php

namespace Corals\Modules\PaymentGateway\DataTables;

use Corals\Foundation\DataTables\BaseDataTable;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Transformers\InvoiceTransformer;
use Yajra\DataTables\EloquentDataTable;

class InvoicesDataTable extends BaseDataTable
{
    /**
     * @param mixed $query
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $this->setResourceUrl(config('paymentgateway.models.invoice.resource_url'));

        $dataTable = new EloquentDataTable($query);

        return $dataTable->setTransformer(new InvoiceTransformer());
    }

    /**
     * @param Invoice $model
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function query(Invoice $model)
    {
        $query = $model->newQuery()->with('issuer');

        $user = $this->request->user();

        if (!Issuer::isAdminUser($user)) {
            $query->whereIn('issuer_id', Issuer::accessibleBy($user)->pluck('id'));
        }

        return $query;
    }

    /**
     * @return array
     */
    protected function getColumns()
    {
        return [
            'id' => ['visible' => false],
            'issuer_name' => ['title' => trans('PaymentGateway::attributes.invoice.issuer_id')],
            'customer_id' => ['title' => trans('PaymentGateway::attributes.invoice.customer_id')],
            'amount_minor' => ['title' => trans('PaymentGateway::attributes.invoice.amount')],
            'currency' => ['title' => trans('PaymentGateway::attributes.invoice.currency')],
            'due_date' => ['title' => trans('PaymentGateway::attributes.invoice.due_date')],
            'status' => ['title' => trans('PaymentGateway::attributes.invoice.status')],
            'created_at' => ['title' => trans('Corals::attributes.created_at')],
        ];
    }
}
