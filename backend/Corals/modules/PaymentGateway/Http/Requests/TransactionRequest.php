<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\Transaction;

class TransactionRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->setModel(Transaction::class);

        return $this->isAuthorized();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->setModel(Transaction::class);
        $rules = parent::rules();

        if ($this->isStore()) {
            $rules = array_merge($rules, [
                'payment_reference_id' => ['required'],
                'amount' => ['required', 'integer', 'min:1'],
                'currency' => ['required', 'string', 'size:3'],
            ]);
        }

        return $rules;
    }
}
