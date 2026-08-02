<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\IssuersDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\IssuerRequest;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Services\IssuerService;

class IssuersController extends BaseController
{
    protected $issuerService;

    public function __construct(IssuerService $issuerService)
    {
        $this->issuerService = $issuerService;

        $this->resource_url = config('paymentgateway.models.issuer.resource_url');

        $this->resource_model = new Issuer();

        $this->title = trans('PaymentGateway::module.issuer.title');
        $this->title_singular = trans('PaymentGateway::module.issuer.title_singular');

        parent::__construct();
    }

    /**
     * @param IssuerRequest $request
     * @param IssuersDataTable $dataTable
     * @return mixed
     */
    public function index(IssuerRequest $request, IssuersDataTable $dataTable)
    {
        return $dataTable->render('PaymentGateway::issuers.index');
    }

    /**
     * @param IssuerRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(IssuerRequest $request)
    {
        $issuer = new Issuer();

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.create_title', ['title' => $this->title_singular]),
        ]);

        return view('PaymentGateway::issuers.create_edit')->with(compact('issuer'));
    }

    /**
     * @param IssuerRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(IssuerRequest $request)
    {
        try {
            $issuer = $this->issuerService->store($request, Issuer::class);

            flash(trans('Corals::messages.success.created', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Issuer::class, 'store');
        }

        return redirectTo(isset($issuer) ? $issuer->getShowURL() : $this->resource_url);
    }

    /**
     * @param IssuerRequest $request
     * @param Issuer $issuer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(IssuerRequest $request, Issuer $issuer)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $issuer->getIdentifier()]),
            'showModel' => $issuer,
        ]);

        return view('PaymentGateway::issuers.show')->with(compact('issuer'));
    }

    /**
     * @param IssuerRequest $request
     * @param Issuer $issuer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(IssuerRequest $request, Issuer $issuer)
    {
        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.update_title', ['title' => $issuer->getIdentifier()]),
        ]);

        return view('PaymentGateway::issuers.create_edit')->with(compact('issuer'));
    }

    /**
     * @param IssuerRequest $request
     * @param Issuer $issuer
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(IssuerRequest $request, Issuer $issuer)
    {
        try {
            $this->issuerService->update($request, $issuer);

            flash(trans('Corals::messages.success.updated', ['item' => $this->title_singular]))->success();
        } catch (\Exception $exception) {
            log_exception($exception, Issuer::class, 'update');
        }

        return redirectTo($issuer->getShowURL());
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

            $message = [
                'level' => 'success',
                'message' => trans('Corals::messages.success.deleted', ['item' => $this->title_singular]),
            ];
        } catch (\Exception $exception) {
            log_exception($exception, Issuer::class, 'destroy');
            $message = ['level' => 'error', 'message' => $exception->getMessage()];
        }

        return response()->json($message);
    }
}
