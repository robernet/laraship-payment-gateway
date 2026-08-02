<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\Classes\BarcodeGeneratorService;
use Corals\Modules\PaymentGateway\Classes\PayFormatGeneratorService;
use Corals\Modules\PaymentGateway\Classes\ReferenceGeneratorService;
use Corals\Modules\PaymentGateway\Http\Requests\PaymentReferenceRequest;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\IssuerUser;
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
     * Generate a Reference for an issuer/customer, with its barcode + pay-format
     * artifacts. Mode ("online" vs "batch") is derived entirely from the issuer's
     * own reference_layout - not a client choice.
     *
     * Non-admin callers must be linked to the target issuer (paymentgateway_issuer_users) -
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
            $issuer = Issuer::findByHash($request->get('issuer_id'));

            if (!$issuer) {
                throw ValidationException::withMessages(['issuer_id' => [trans('Corals::messages.errors.not_found')]]);
            }

            $user = $request->user();

            if (!isSuperUser($user) && !$user->hasPermissionTo('PaymentGateway::payment_reference.create')) {
                $isLinkedIssuer = IssuerUser::query()
                    ->where('user_id', $user->id)
                    ->where('issuer_id', $issuer->id)
                    ->exists();

                abort_if(!$isLinkedIssuer, 403, 'This user is not linked to the requested issuer.');
            }

            $paymentReference = $this->paymentReferenceService->generateWithArtifacts(
                $issuer,
                $request->get('customer_id'),
                $request->get('amount'),
                $request->get('currency'),
                $request->get('due_date'),
                $generator,
                $barcodeGenerator,
                $payFormatGenerator
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
