<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\Http\Requests\ShiftRequest;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Shift;
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
     * Open a shift. The requesting token must be scoped to the target branch
     * (a synthetic `branch:{hashid}` ability set at login). A device-login
     * token (Pos) records pos_id and leaves operator_id null; an
     * operator-login token (User) does the opposite.
     *
     * @param ShiftRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ShiftRequest $request)
    {
        try {
            $this->authorize('create', Shift::class);

            $branch = Branch::findByHash($request->get('branch_id'));

            if (!$branch) {
                throw ValidationException::withMessages(['branch_id' => [trans('Corals::messages.errors.not_found')]]);
            }

            $token = $request->user()->currentAccessToken();

            abort_if(
                !$token || !$token->can('branch:' . $branch->getHashedIdAttribute()),
                403,
                'This token is not scoped to the requested branch.'
            );

            $identity = $request->user() instanceof Pos
                ? ['pos_id' => $request->user()->id]
                : ['operator_id' => $request->user()->id];

            $shift = $this->shiftService->store($request, Shift::class, array_merge([
                'branch_id' => $branch->id,
                // ponytail: store_id denormalized from branch.store_id to avoid a
                // column-modify migration + report refactor; drop it and join
                // through branch when it becomes a maintenance burden.
                'store_id' => $branch->store_id,
                'opened_at' => now(),
            ], $identity));

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
     * Assumes all of a shift's transactions share a single currency — nothing
     * currently enforces this (a pre-existing Phase 2 gap), so a mixed-currency
     * shift would sum `amount_minor` across currencies meaninglessly. Not fixed
     * in this phase.
     *
     * @param ShiftRequest $request
     * @param Shift $shift
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShiftRequest $request, Shift $shift)
    {
        try {
            $user = $request->user();

            $isOwner = $user instanceof Pos
                ? $shift->pos_id === $user->id
                : $shift->operator_id === $user->id;

            abort_if(!$isOwner, 403, 'This shift belongs to a different operator.');
            $this->authorize('update', $shift);
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
