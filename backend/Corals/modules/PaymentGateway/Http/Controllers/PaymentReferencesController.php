<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\Classes\BarcodeGeneratorService;
use Corals\Modules\PaymentGateway\Classes\PayFormatGeneratorService;
use Corals\Modules\PaymentGateway\Classes\ReferenceGeneratorService;
use Corals\Modules\PaymentGateway\DataTables\PaymentReferencesDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\PaymentReferenceRequest;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Services\PaymentReferenceService;

class PaymentReferencesController extends BaseController
{
    protected $paymentReferenceService;

    public function __construct(PaymentReferenceService $paymentReferenceService)
    {
        $this->paymentReferenceService = $paymentReferenceService;

        $this->resource_url = config('paymentgateway.models.payment_reference.resource_url');

        $this->resource_model = new PaymentReference();

        $this->title = trans('PaymentGateway::module.payment_reference.title');
        $this->title_singular = trans('PaymentGateway::module.payment_reference.title_singular');

        parent::__construct();
    }

    /**
     * @param PaymentReferenceRequest $request
     * @param PaymentReferencesDataTable $dataTable
     * @return mixed
     */
    public function index(PaymentReferenceRequest $request, PaymentReferencesDataTable $dataTable)
    {
        return $dataTable->render('PaymentGateway::payment_references.index');
    }

    /**
     * Show the "generate a reference" form - pick an unpaid Invoice; its
     * issuer, amount, currency, and due date all come from the invoice.
     *
     * @param PaymentReferenceRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(PaymentReferenceRequest $request)
    {
        $user = $request->user();

        $accessibleIssuerIds = Issuer::accessibleBy($user)->pluck('id');

        $invoices = Invoice::query()
            ->whereIn('issuer_id', $accessibleIssuerIds)
            ->where('status', 'unpaid')
            ->whereDoesntHave('paymentReference')
            ->with('issuer')
            ->orderBy('due_date')
            ->get();

        $groupByIssuer = Issuer::isAdminUser($user) || $accessibleIssuerIds->count() > 1;

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::payment_references.create')->with(compact('invoices', 'groupByIssuer'));
    }

    /**
     * @param PaymentReferenceRequest $request
     * @param ReferenceGeneratorService $generator
     * @param BarcodeGeneratorService $barcodeGenerator
     * @param PayFormatGeneratorService $payFormatGenerator
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(
        PaymentReferenceRequest $request,
        ReferenceGeneratorService $generator,
        BarcodeGeneratorService $barcodeGenerator,
        PayFormatGeneratorService $payFormatGenerator
    ) {
        try {
            $invoice = Invoice::findByHash($request->get('invoice_id'));

            abort_if(!$invoice, 404);

            $issuer = $invoice->issuer;

            abort_if(!$issuer->isAccessibleBy($request->user()), 403, 'This user is not linked to the requested issuer.');

            abort_if($invoice->paymentReference()->exists(), 422, 'This invoice already has a Payment Reference.');

            $paymentReference = $this->paymentReferenceService->generateWithArtifacts(
                $invoice,
                $generator,
                $barcodeGenerator,
                $payFormatGenerator
            );

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, PaymentReference::class, 'store');
        }

        return redirectTo(isset($paymentReference) ? $paymentReference->getShowURL() : $this->resource_url);
    }

    /**
     * @param PaymentReferenceRequest $request
     * @param PaymentReference $paymentReference
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(PaymentReferenceRequest $request, PaymentReference $paymentReference)
    {
        abort_if(!$paymentReference->issuer->isAccessibleBy($request->user()), 403);

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $paymentReference->reference]),
            'showModel' => $paymentReference,
        ]);

        return view('PaymentGateway::payment_references.show')->with(compact('paymentReference'));
    }
}
