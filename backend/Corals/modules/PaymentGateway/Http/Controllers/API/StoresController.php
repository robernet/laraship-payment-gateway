<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\DataTables\StoresDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\StoreRequest;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Services\StoreService;
use Corals\Modules\PaymentGateway\Transformers\API\StorePresenter;

class StoresController extends APIBaseController
{
    protected $storeService;

    /**
     * StoresController constructor.
     * @param StoreService $storeService
     * @throws \Exception
     */
    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
        $this->storeService->setPresenter(new StorePresenter());

        parent::__construct();
    }

    /**
     * @param StoreRequest $request
     * @param StoresDataTable $dataTable
     * @return mixed
     * @throws \Exception
     */
    public function index(StoreRequest $request, StoresDataTable $dataTable)
    {
        $stores = $dataTable->query(new Store());

        return $this->storeService->index($stores, $dataTable);
    }

    /**
     * @param StoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRequest $request)
    {
        try {
            $store = $this->storeService->store($request, Store::class);

            return apiResponse($this->storeService->getModelDetails(), trans('Corals::messages.success.created', ['item' => $store->name]));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * @param StoreRequest $request
     * @param Store $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(StoreRequest $request, Store $store)
    {
        try {
            return apiResponse($this->storeService->getModelDetails($store));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * @param StoreRequest $request
     * @param Store $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StoreRequest $request, Store $store)
    {
        try {
            $this->storeService->update($request, $store);

            return apiResponse($this->storeService->getModelDetails(), trans('Corals::messages.success.updated', ['item' => $store->name]));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * @param StoreRequest $request
     * @param Store $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(StoreRequest $request, Store $store)
    {
        try {
            $this->storeService->destroy($request, $store);

            return apiResponse([], trans('Corals::messages.success.deleted', ['item' => $store->name]));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }
}
