<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\Http\Requests\ReportRequest;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseController
{
    public function __construct()
    {
        $this->title = trans('PaymentGateway::module.report.title');
        $this->title_singular = trans('PaymentGateway::module.report.title_singular');

        parent::__construct();
    }

    /**
     * @param ReportRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(ReportRequest $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');

        $issuerTotals = $this->issuerTotals($from, $to);
        $storeTotals = $this->storeTotals($from, $to);

        $this->setViewSharedData();

        return view('PaymentGateway::reports.index')->with(compact('issuerTotals', 'storeTotals', 'from', 'to'));
    }

    private function issuerTotals(?string $from, ?string $to)
    {
        return DB::table('paymentgateway_transactions')
            ->join('paymentgateway_payment_references', 'paymentgateway_payment_references.id', '=', 'paymentgateway_transactions.payment_reference_id')
            ->join('paymentgateway_issuers', 'paymentgateway_issuers.id', '=', 'paymentgateway_payment_references.issuer_id')
            ->when($from, fn ($query) => $query->whereDate('paymentgateway_transactions.collected_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('paymentgateway_transactions.collected_at', '<=', $to))
            ->groupBy('paymentgateway_issuers.id', 'paymentgateway_issuers.name')
            ->selectRaw('paymentgateway_issuers.id as issuer_id, paymentgateway_issuers.name as issuer_name, SUM(paymentgateway_transactions.amount_minor) as total_minor, COUNT(*) as transaction_count')
            ->orderByDesc('total_minor')
            ->get();
    }

    private function storeTotals(?string $from, ?string $to)
    {
        return DB::table('paymentgateway_transactions')
            ->join('paymentgateway_shifts', 'paymentgateway_shifts.id', '=', 'paymentgateway_transactions.shift_id')
            ->join('paymentgateway_stores', 'paymentgateway_stores.id', '=', 'paymentgateway_shifts.store_id')
            ->when($from, fn ($query) => $query->whereDate('paymentgateway_transactions.collected_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('paymentgateway_transactions.collected_at', '<=', $to))
            ->groupBy('paymentgateway_stores.id', 'paymentgateway_stores.name')
            ->selectRaw('paymentgateway_stores.id as store_id, paymentgateway_stores.name as store_name, SUM(paymentgateway_transactions.amount_minor) as total_minor, COUNT(*) as transaction_count')
            ->orderByDesc('total_minor')
            ->get();
    }
}
