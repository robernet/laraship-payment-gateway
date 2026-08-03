<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\InvoicesDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\InvoiceRequest;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Services\InvoiceService;

class InvoicesController extends BaseController
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;

        $this->resource_url = config('paymentgateway.models.invoice.resource_url');

        $this->resource_model = new Invoice();

        $this->title = trans('PaymentGateway::module.invoice.title');
        $this->title_singular = trans('PaymentGateway::module.invoice.title_singular');

        parent::__construct();
    }

    /**
     * @param InvoiceRequest $request
     * @param InvoicesDataTable $dataTable
     * @return mixed
     */
    public function index(InvoiceRequest $request, InvoicesDataTable $dataTable)
    {
        return $dataTable->render('PaymentGateway::invoices.index');
    }

    /**
     * @param InvoiceRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(InvoiceRequest $request)
    {
        $user = $request->user();

        $invoice = new Invoice();
        $issuers = Issuer::accessibleBy($user)->orderBy('name')->get();
        $isAdmin = Issuer::isAdminUser($user);

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::invoices.create_edit')->with(compact('invoice', 'issuers', 'isAdmin'));
    }

    /**
     * @param InvoiceRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(InvoiceRequest $request)
    {
        try {
            $issuer = Issuer::findByHash($request->get('issuer_id'));

            abort_if(!$issuer, 404);

            abort_if(!$issuer->isAccessibleBy($request->user()), 403, 'This user is not linked to the requested issuer.');

            $invoice = $this->invoiceService->store($request, Invoice::class, ['issuer_id' => $issuer->id]);

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Invoice::class, 'store');
        }

        return redirectTo(isset($invoice) ? $invoice->getShowURL() : $this->resource_url);
    }

    /**
     * @param InvoiceRequest $request
     * @param Invoice $invoice
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(InvoiceRequest $request, Invoice $invoice)
    {
        abort_if(!$invoice->issuer->isAccessibleBy($request->user()), 403);

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $invoice->getIdentifier()]),
            'showModel' => $invoice,
        ]);

        return view('PaymentGateway::invoices.show')->with(compact('invoice'));
    }

    /**
     * @param InvoiceRequest $request
     * @param Invoice $invoice
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(InvoiceRequest $request, Invoice $invoice)
    {
        abort_if($invoice->paymentReference()->exists(), 403, 'This invoice already has a Payment Reference and can no longer be edited.');

        $user = $request->user();

        $issuers = Issuer::accessibleBy($user)->orderBy('name')->get();
        $isAdmin = Issuer::isAdminUser($user);

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.update_title', ['title' => $invoice->getIdentifier()]),
        ]);

        return view('PaymentGateway::invoices.create_edit')->with(compact('invoice', 'issuers', 'isAdmin'));
    }

    /**
     * @param InvoiceRequest $request
     * @param Invoice $invoice
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        abort_if($invoice->paymentReference()->exists(), 403, 'This invoice already has a Payment Reference and can no longer be edited.');

        try {
            $issuer = Issuer::findByHash($request->get('issuer_id'));

            abort_if(!$issuer, 404);

            abort_if(!$issuer->isAccessibleBy($request->user()), 403, 'This user is not linked to the requested issuer.');

            $this->invoiceService->update($request, $invoice, ['issuer_id' => $issuer->id]);

            flash(trans('Corals::messages.success.updated', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Invoice::class, 'update');
        }

        return redirectTo($invoice->getShowURL());
    }
}
