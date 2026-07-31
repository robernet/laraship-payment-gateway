<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\PaymentReference;

class PaymentReferenceRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->setModel(PaymentReference::class);

        return $this->isAuthorized();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->setModel(PaymentReference::class);
        $rules = parent::rules();

        if ($this->isStore()) {
            $rules = array_merge($rules, [
                'issuer_id' => ['required'],
                'customer_id' => ['required', 'string', 'max:22'],
                'amount' => ['sometimes', 'integer', 'min:1'],
                'currency' => ['required_with:amount', 'string', 'size:3'],
                'due_date' => ['sometimes', 'date'],
                // AutoPay - schema/validation only (Phase 4 scaffold, no live gateway wiring yet).
                'autopay_payment_number' => ['required_with:autopay_frequency_days', 'integer', 'min:1'],
                'autopay_frequency_days' => ['required_with:autopay_payment_number', 'integer', 'min:1'],
            ]);
        }

        return $rules;
    }
}
