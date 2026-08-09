<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Facades\Hashids;
use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\StoresDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\StoreRequest;
use Corals\Modules\PaymentGateway\Models\OperatorStore;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Services\StoreService;
use Corals\User\Models\User;
use Illuminate\Http\Request;

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

        // Users not yet assigned as operators here - the "Add operator" select.
        // ponytail: loads all unassigned users; add search/autocomplete when the
        // user base outgrows a plain <select>.
        $assignableUsers = User::query()
            ->whereNotIn('id', $store->operators()->pluck('users.id'))
            ->orderBy('name')
            ->get();

        return view('PaymentGateway::stores.show')->with(compact('store', 'assignableUsers'));
    }

    /**
     * Assign a User as an operator of this store (lets them POST /pos/login
     * against it). Not a StoreRequest: that form request treats every POST as
     * a "create" and would check store.create instead of store.update.
     *
     * @param Request $request
     * @param Store $store
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function assignOperator(Request $request, Store $store)
    {
        $this->authorize('update', $store);

        $request->validate(['user_id' => ['required']]);

        $userId = Hashids::decode($request->get('user_id'))[0] ?? null;

        abort_if(!$userId || !User::query()->whereKey($userId)->exists(), 404);

        OperatorStore::firstOrCreate(['user_id' => $userId, 'store_id' => $store->id]);

        flash(trans('PaymentGateway::module.store.operator_assigned'))->success();

        return back();
    }

    /**
     * Remove a User's operator access to this store.
     *
     * @param Store $store
     * @param string $user Hashid of the user.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function removeOperator(Store $store, string $user)
    {
        $this->authorize('update', $store);

        $userId = Hashids::decode($user)[0] ?? null;

        abort_if(!$userId, 404);

        $store->operators()->detach($userId);

        flash(trans('PaymentGateway::module.store.operator_removed'))->success();

        return back();
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
