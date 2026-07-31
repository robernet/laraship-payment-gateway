<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\Http\Requests\ShiftRequest;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Store;
use Corals\Modules\PaymentGateway\Services\ShiftService;
use Corals\Modules\PaymentGateway\Transformers\API\ShiftPresenter;
use Illuminate\Validation\ValidationException;

class ShiftsController extends APIBaseController
{
    protected $shiftService;

    /**
     * @param ShiftService $shiftService
     * @throws \Exception
     */
    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
        $this->shiftService->setPresenter(new ShiftPresenter());

        parent::__construct();
    }

    /**
     * Open a shift. The requesting operator's token must be scoped to the
     * target store (a synthetic `store:{hashid}` ability set at login).
     *
     * @param ShiftRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ShiftRequest $request)
    {
        try {
            $store = Store::findByHash($request->get('store_id'));

            if (!$store) {
                throw ValidationException::withMessages(['store_id' => [trans('Corals::messages.errors.not_found')]]);
            }

            $token = $request->user()->currentAccessToken();

            abort_if(
                !$token || !$token->can('store:' . $store->getHashedIdAttribute()),
                403,
                'This token is not scoped to the requested store.'
            );

            $shift = $this->shiftService->store($request, Shift::class, [
                'store_id' => $store->id,
                'operator_id' => $request->user()->id,
                'opened_at' => now(),
            ]);

            return apiResponse($this->shiftService->getModelDetails(), trans('Corals::messages.success.created', ['item' => 'shift']));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }

    /**
     * Close a shift. Only the operator who opened it may close it. Computes
     * the discrepancy between the operator's counted cash and the sum of the
     * shift's settled transactions (positive = over, negative = short).
     *
     * @param ShiftRequest $request
     * @param Shift $shift
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShiftRequest $request, Shift $shift)
    {
        try {
            abort_if($shift->operator_id !== $request->user()->id, 403, 'This shift belongs to a different operator.');
            abort_if(!$shift->isOpen(), 422, 'This shift is already closed.');

            $collectedMinor = (int) $shift->transactions()->sum('amount_minor');
            $countedMinor = (int) $request->get('counted_amount');

            $this->shiftService->update($request, $shift, [
                'closed_at' => now(),
                'counted_amount_minor' => $countedMinor,
                'discrepancy_minor' => $countedMinor - $collectedMinor,
            ]);

            return apiResponse($this->shiftService->getModelDetails(), trans('Corals::messages.success.updated', ['item' => 'shift']));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }
}
