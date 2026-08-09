<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\PosDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\PosRequest;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Services\PosService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PosController extends BaseController
{
    protected $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;

        $this->resource_url = config('paymentgateway.models.pos.resource_url');

        $this->resource_model = new Pos();

        $this->title = trans('PaymentGateway::module.pos.title');
        $this->title_singular = trans('PaymentGateway::module.pos.title_singular');

        parent::__construct();
    }

    /**
     * @param PosRequest $request
     * @param PosDataTable $dataTable
     * @return mixed
     */
    public function index(PosRequest $request, PosDataTable $dataTable)
    {
        return $dataTable->render('PaymentGateway::pos.index');
    }

    /**
     * @param PosRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(PosRequest $request)
    {
        $pos = new Pos();
        $stores = Store::query()->orderBy('name')->get();

        // Pre-select the store when arriving from a Store's POS Terminals panel.
        $selectedStoreId = $request->get('store_id');

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::pos.create_edit')->with(compact('pos', 'stores', 'selectedStoreId'));
    }

    /**
     * @param PosRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(PosRequest $request)
    {
        try {
            $store = Store::findByHash($request->get('store_id'));

            abort_if(!$store, 404);

            $deviceSecret = Str::random(40);

            $pos = $this->posService->store($request, Pos::class, [
                'store_id' => $store->id,
                'device_secret' => Hash::make($deviceSecret),
            ]);

            session()->flash('device_secret', $deviceSecret);

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Pos::class, 'store');
        }

        return redirectTo(isset($pos) ? $pos->getShowURL() : $this->resource_url);
    }

    /**
     * Rotate this POS terminal's device credentials. The plaintext secret is
     * only ever available once, right after this call.
     *
     * Deliberately not a PosRequest: that form request's generic authorize()
     * treats every POST as a "create", which would check pos.create instead
     * of pos.update against this specific record.
     *
     * @param Pos $pos
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function regenerateSecret(Pos $pos)
    {
        $this->authorize('update', $pos);

        session()->flash('device_secret', $pos->regenerateDeviceSecret());

        flash(trans('PaymentGateway::module.pos.secret_regenerated'))->success();

        // back() so the once-shown secret lands on whichever page triggered it -
        // the POS show page or a Store's POS Terminals panel.
        return back();
    }

    /**
     * @param PosRequest $request
     * @param Pos $pos
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(PosRequest $request, Pos $pos)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $pos->getIdentifier()]),
            'showModel' => $pos,
        ]);

        return view('PaymentGateway::pos.show')->with(compact('pos'));
    }

    /**
     * @param PosRequest $request
     * @param Pos $pos
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(PosRequest $request, Pos $pos)
    {
        $stores = Store::query()->orderBy('name')->get();

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.update_title', ['title' => $pos->getIdentifier()]),
        ]);

        return view('PaymentGateway::pos.create_edit')->with(compact('pos', 'stores'));
    }

    /**
     * @param PosRequest $request
     * @param Pos $pos
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(PosRequest $request, Pos $pos)
    {
        try {
            $store = Store::findByHash($request->get('store_id'));

            abort_if(!$store, 404);

            $this->posService->update($request, $pos, ['store_id' => $store->id]);

            flash(trans('Corals::messages.success.updated', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Pos::class, 'update');
        }

        return redirectTo($pos->getShowURL());
    }

    /**
     * @param PosRequest $request
     * @param Pos $pos
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PosRequest $request, Pos $pos)
    {
        try {
            $this->posService->destroy($request, $pos);

            $message = [
                'level' => 'success',
                'message' => trans('Corals::messages.success.deleted', ['item' => $this->title_singular]),
            ];
        } catch (\Exception $exception) {
            log_exception($exception, Pos::class, 'destroy');
            $message = ['level' => 'error', 'message' => $exception->getMessage()];
        }

        // The DataTable calls this over AJAX (wants JSON); the Store panel's
        // delete is a plain form, so send it back with a flash instead.
        if (!$request->wantsJson()) {
            flash($message['message'])->{$message['level']}();

            return back();
        }

        return response()->json($message);
    }
}
