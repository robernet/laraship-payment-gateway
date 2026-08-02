<?php

namespace Corals\Modules\PaymentGateway\Http\Controllers\API;

use Corals\Foundation\Http\Controllers\APIBaseController;
use Corals\Modules\PaymentGateway\Classes\CollectionValidator;
use Corals\Modules\PaymentGateway\Http\Requests\TransactionRequest;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Corals\Modules\PaymentGateway\Models\Shift;
use Corals\Modules\PaymentGateway\Models\Transaction;
use Corals\Modules\PaymentGateway\Services\TransactionService;
use Corals\Modules\PaymentGateway\Transformers\API\TransactionPresenter;
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
     * operator's own currently-open shift - never client-supplied - so an
     * operator can never record a transaction against someone else's shift.
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

            $shift = Shift::query()
                ->where('operator_id', $request->user()->id)
                ->whereNull('closed_at')
                ->latest('opened_at')
                ->first();

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

            return apiResponse($this->transactionService->getModelDetails(), trans('Corals::messages.success.created', ['item' => 'transaction']));
        } catch (\Exception $exception) {
            return apiExceptionResponse($exception);
        }
    }
}
