<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\Http\Requests\InvoiceRequest;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Services\InvoiceService;
use Corals\Modules\PaymentGateway\Transformers\API\InvoicePresenter;
use Illuminate\Validation\ValidationException;

class InvoicesController extends APIBaseController
{
    protected $invoiceService;

    /**
     * @param InvoiceService $invoiceService
     * @throws \Exception
     */
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
        $this->invoiceService->setPresenter(new InvoicePresenter());

        parent::__construct();
    }

    /**
     * Scoped the same way as the admin panel's Invoice list - admins see
     * everything, issuer-linked users see only their own issuers' invoices.
     * Optional `?status=unpaid|paid` filter.
     *
     * @param InvoiceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(InvoiceRequest $request)
    {
        $user = $request->user();

        $invoices = Invoice::query()
            ->when(!Issuer::isAdminUser($user), function ($query) use ($user) {
                $query->whereIn('issuer_id', Issuer::accessibleBy($user)->pluck('id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->get('status'));
            })
            ->paginate($request->get('limit'));

        return apiResponse((new InvoicePresenter())->present($invoices));
    }

    /**
     * @param InvoiceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(InvoiceRequest $request)
    {
        try {
            $issuer = Issuer::findByHash($request->get('issuer_id'));

            if (!$issuer) {
                throw ValidationException::withMessages(['issuer_id' => [trans('Corals::messages.errors.not_found')]]);
            }

            abort_if(!$issuer->isAccessibleBy($request->user()), 403, 'This user is not linked to the requested issuer.');

            $invoice = $this->invoiceService->store($request, Invoice::class, ['issuer_id' => $issuer->id]);

            return apiResponse($this->invoiceService->getModelDetails(), trans('Corals::messages.success.created', ['item' => $invoice->getIdentifier()]));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * @param InvoiceRequest $request
     * @param Invoice $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(InvoiceRequest $request, Invoice $invoice)
    {
        try {
            abort_if(!$invoice->issuer->isAccessibleBy($request->user()), 403);

            return apiResponse($this->invoiceService->getModelDetails($invoice));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }
}
