<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\Classes\BarcodeGeneratorService;
use Corals\Modules\PaymentGateway\Classes\PayFormatGeneratorService;
use Corals\Modules\PaymentGateway\Classes\ReferenceGeneratorService;
use Corals\Modules\PaymentGateway\Http\Requests\PaymentReferenceRequest;
use Corals\Modules\PaymentGateway\Models\Invoice;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Services\PaymentReferenceService;
use Corals\Modules\PaymentGateway\Transformers\API\PaymentReferencePresenter;
use Illuminate\Validation\ValidationException;

class PaymentReferencesController extends APIBaseController
{
    protected $paymentReferenceService;

    /**
     * @param PaymentReferenceService $paymentReferenceService
     * @throws \Exception
     */
    public function __construct(PaymentReferenceService $paymentReferenceService)
    {
        $this->paymentReferenceService = $paymentReferenceService;
        $this->paymentReferenceService->setPresenter(new PaymentReferencePresenter());

        parent::__construct();
    }

    /**
     * Generate a Reference for an Invoice, with its barcode + pay-format
     * artifacts. `invoice_id` is the unique identifier generation is keyed
     * on - it supplies the issuer, identifier, amount, currency, and due
     * date. Mode ("online" vs "batch") is derived entirely from the issuer's
     * own reference_layout - not a client choice.
     *
     * Non-admin callers must be linked to the invoice's issuer (paymentgateway_issuer_users) -
     * we are the Reference Generator service now, offered to issuers directly, not just admins.
     *
     * @param PaymentReferenceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(
        PaymentReferenceRequest $request,
        ReferenceGeneratorService $generator,
        BarcodeGeneratorService $barcodeGenerator,
        PayFormatGeneratorService $payFormatGenerator
    ) {
        try {
            $invoice = Invoice::findByHash($request->get('invoice_id'));

            if (!$invoice) {
                throw ValidationException::withMessages(['invoice_id' => [trans('Corals::messages.errors.not_found')]]);
            }

            $user = $request->user();

            abort_if(!$invoice->issuer->isAccessibleBy($user), 403, 'This user is not linked to the requested issuer.');

            if ($invoice->paymentReference()->exists()) {
                throw ValidationException::withMessages(['invoice_id' => ['This invoice already has a Payment Reference.']]);
            }

            $paymentReference = $this->paymentReferenceService->generateWithArtifacts(
                $invoice,
                $generator,
                $barcodeGenerator,
                $payFormatGenerator,
                $request->boolean('autopay_enabled'),
                $request->get('autopay_payment_number'),
                $request->get('autopay_frequency_days')
            );

            return apiResponse($this->paymentReferenceService->getModelDetails(), trans('Corals::messages.success.created', ['item' => $paymentReference->reference]));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * POS lookup by the raw Reference string (domain identifier, not the API hashid).
     *
     * @param PaymentReferenceRequest $request
     * @param string $reference
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookup(PaymentReferenceRequest $request, string $reference)
    {
        try {
            $this->authorize('view', PaymentReference::class);

            $paymentReference = PaymentReference::query()->where('reference', $reference)->firstOrFail();

            return apiResponse($this->paymentReferenceService->getModelDetails($paymentReference));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }
}
