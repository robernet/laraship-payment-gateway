<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Illuminate\Validation\Rule;

class IssuerRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->setModel(Issuer::class);

        return $this->isAuthorized();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->setModel(Issuer::class);
        $rules = parent::rules();

        if ($this->isUpdate() || $this->isStore()) {
            $rules = array_merge($rules, [
                'name' => ['required', 'string', 'max:255'],
                'reference_layout' => ['required', 'array'],
                'reference_layout.identifier_length' => ['required', 'integer', 'min:1', 'max:22'],
                'reference_layout.amount_length' => ['sometimes', 'integer', 'min:1', 'max:15'],
                'reference_layout.embed_due_date' => ['sometimes', 'boolean'],
                'reject_late_payment' => ['sometimes', 'boolean'],
            ]);
        }

        if ($this->isStore()) {
            $rules = array_merge($rules, [
                'sub_id' => ['required', 'integer', 'min:0', 'max:999', Rule::unique('paymentgateway_issuers', 'sub_id')],
            ]);
        }

        if ($this->isUpdate()) {
            $issuer = $this->route('issuer');

            $rules = array_merge($rules, [
                'sub_id' => [
                    'required', 'integer', 'min:0', 'max:999',
                    Rule::unique('paymentgateway_issuers', 'sub_id')->ignore($issuer?->id),
                ],
            ]);
        }

        return $rules;
    }
}
