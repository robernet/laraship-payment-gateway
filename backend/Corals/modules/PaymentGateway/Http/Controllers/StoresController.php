<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\StoresDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\StoreRequest;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Services\StoreService;

class StoresController extends BaseController
{
    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;

        $this->resource_url = config('paymentgateway.models.store.resource_url');

        $this->resource_model = new Store();

        $this->title = trans('PaymentGateway::module.store.title');
        $this->title_singular = trans('PaymentGateway::module.store.title_singular');

        parent::__construct();
    }

    /**
     * @param StoreRequest $request
     * @param StoresDataTable $dataTable
     * @return mixed
     */
    public function index(StoreRequest $request, StoresDataTable $dataTable)
    {
        return $dataTable->render('PaymentGateway::stores.index');
    }

    /**
     * @param StoreRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(StoreRequest $request)
    {
        $store = new Store();

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::stores.create_edit')->with(compact('store'));
    }

    /**
     * @param StoreRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(StoreRequest $request)
    {
        try {
            $store = $this->storeService->store($request, Store::class);

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Store::class, 'store');
        }

        return redirectTo(isset($store) ? $store->getShowURL() : $this->resource_url);
    }

    /**
     * @param StoreRequest $request
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(StoreRequest $request, Store $store)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $store->getIdentifier()]),
            'showModel' => $store,
        ]);

        return view('PaymentGateway::stores.show')->with(compact('store'));
    }

    /**
     * @param StoreRequest $request
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(StoreRequest $request, Store $store)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.update_title', ['title' => $store->getIdentifier()]),
        ]);

        return view('PaymentGateway::stores.create_edit')->with(compact('store'));
    }

    /**
     * @param StoreRequest $request
     * @param Store $store
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(StoreRequest $request, Store $store)
    {
        try {
            $this->storeService->update($request, $store);

            flash(trans('Corals::messages.success.updated', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Store::class, 'update');
        }

        return redirectTo($store->getShowURL());
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

            $message = [
                'level' => 'success',
                'message' => trans('Corals::messages.success.deleted', ['item' => $this->title_singular]),
            ];
        } catch (\Exception $exception) {
            log_exception($exception, Store::class, 'destroy');
            $message = ['level' => 'error', 'message' => $exception->getMessage()];
        }

        return response()->json($message);
    }
}
