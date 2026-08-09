<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\Classes\CollectionValidator;
use Corals\Modules\PaymentGateway\Http\Requests\TransactionRequest;
use Corals\Modules\PaymentGateway\Models\Branch;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Pos;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\Modules\PaymentGateway\Services\TransactionService;
use Corals\Modules\PaymentGateway\Transformers\API\TransactionPresenter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionsController extends APIBaseController
{
    protected $transactionService;

    /**
     * @param TransactionService $transactionService
     * @throws \Exception
     */
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
        $this->transactionService->setPresenter(new TransactionPresenter());

        parent::__construct();
    }

    /**
     * Collect cash against a Reference. The shift is always the requesting
     * token's own currently-open shift - never client-supplied - matched by
     * pos_id for a device-login token or operator_id for a user-login token,
     * so a caller can never record a transaction against someone else's shift.
     *
     * @param TransactionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TransactionRequest $request, CollectionValidator $collectionValidator)
    {
        try {
            $this->authorize('create', Transaction::class);

            $paymentReference = PaymentReference::findByHash($request->get('payment_reference_id'));

            if (!$paymentReference) {
                throw ValidationException::withMessages(['payment_reference_id' => [trans('Corals::messages.errors.not_found')]]);
            }

            $collectionValidator->assertAmountMatches($paymentReference, (int) $request->get('amount'));
            $collectionValidator->assertNotOverdue($paymentReference);

            $user = $request->user();

            $token = $request->user()->currentAccessToken();

            $branchAbility = collect($token?->abilities ?? [])
                ->first(fn ($ability) => Str::startsWith($ability, 'branch:'));

            $branch = $branchAbility ? Branch::findByHash(Str::after($branchAbility, 'branch:')) : null;

            if (!$branch) {
                throw ValidationException::withMessages(['shift' => ['This token is not scoped to a branch.']]);
            }

            $openShiftQuery = Shift::query()->whereNull('closed_at')->where('branch_id', $branch->id)->latest('opened_at');

            $shift = $user instanceof Pos
                ? $openShiftQuery->where('pos_id', $user->id)->first()
                : $openShiftQuery->where('operator_id', $user->id)->first();

            if (!$shift) {
                throw ValidationException::withMessages(['shift' => ['No open shift for this operator - open a shift before collecting.']]);
            }

            $transaction = $this->transactionService->store($request, Transaction::class, [
                'payment_reference_id' => $paymentReference->id,
                'shift_id' => $shift->id,
                'amount_minor' => $request->get('amount'),
                'currency' => $request->get('currency'),
                'collected_at' => now(),
                'status' => 'settled',
            ]);

            $paymentReference->update(['status' => 'collected']);

            if ($paymentReference->invoice_id) {
                $paymentReference->invoice->update(['status' => 'paid']);
            }

            return apiResponse($this->transactionService->getModelDetails(), trans('Corals::messages.success.created', ['item' => 'transaction']));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }
}
