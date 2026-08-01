<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers;

use Corals\Foundation\Http\Controllers\BaseController;
use Corals\Modules\PaymentGateway\DataTables\ShiftsDataTable;
use Corals\Modules\PaymentGateway\Http\Requests\ShiftRequest;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Services\ShiftService;

class ShiftsController extends BaseController
{
    protected $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;

        $this->resource_url = config('paymentgateway.models.shift.resource_url');

        $this->resource_model = new Shift();

        $this->title = trans('PaymentGateway::module.shift.title');
        $this->title_singular = trans('PaymentGateway::module.shift.title_singular');

        parent::__construct();
    }

    /**
     * @param ShiftRequest $request
     * @param ShiftsDataTable $dataTable
     * @return mixed
     */
    public function index(ShiftRequest $request, ShiftsDataTable $dataTable)
    {
        return $dataTable->render('PaymentGateway::shifts.index');
    }

    /**
     * @param ShiftRequest $request
     * @param Shift $shift
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(ShiftRequest $request, Shift $shift)
    {
        $shift->load(['transactions.paymentReference']);

        $this->setViewSharedData([
            'title_singular' => trans('Corals::labels.show_title', ['title' => $shift->getIdentifier()]),
            'showModel' => $shift,
        ]);

        return view('PaymentGateway::shifts.show')->with(compact('shift'));
    }
}
