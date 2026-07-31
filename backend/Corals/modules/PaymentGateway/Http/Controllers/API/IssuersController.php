<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\Http\Requests\IssuerRequest;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Services\IssuerService;
use Corals\Modules\PaymentGateway\Transformers\API\IssuerPresenter;

class IssuersController extends APIBaseController
{
    protected $issuerService;

    /**
     * @param IssuerService $issuerService
     * @throws \Exception
     */
    public function __construct(IssuerService $issuerService)
    {
        $this->issuerService = $issuerService;
        $this->issuerService->setPresenter(new IssuerPresenter());

        parent::__construct();
    }

    /**
     * @param IssuerRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IssuerRequest $request)
    {
        $issuers = Issuer::query()->paginate($request->get('limit'));

        return apiResponse((new IssuerPresenter())->present($issuers));
    }

    /**
     * @param IssuerRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(IssuerRequest $request)
    {
        try {
            $issuer = $this->issuerService->store($request, Issuer::class);

            return apiResponse($this->issuerService->getModelDetails(), trans('Corals::messages.success.created', ['item' => $issuer->name]));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * @param IssuerRequest $request
     * @param Issuer $issuer
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(IssuerRequest $request, Issuer $issuer)
    {
        try {
            return apiResponse($this->issuerService->getModelDetails($issuer));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * @param IssuerRequest $request
     * @param Issuer $issuer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(IssuerRequest $request, Issuer $issuer)
    {
        try {
            $this->issuerService->update($request, $issuer);

            return apiResponse($this->issuerService->getModelDetails(), trans('Corals::messages.success.updated', ['item' => $issuer->name]));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * @param IssuerRequest $request
     * @param Issuer $issuer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(IssuerRequest $request, Issuer $issuer)
    {
        try {
            $this->issuerService->destroy($request, $issuer);

            return apiResponse([], trans('Corals::messages.success.deleted', ['item' => $issuer->name]));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }
}
