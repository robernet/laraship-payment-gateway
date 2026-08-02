<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\Issuer;
use Corals\Modules\PaymentGateway\Models\PaymentReference;
use Illuminate\Contracts\Validation\Validator;

class PaymentReferenceRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Generation (store) is issuer-scoped - admin permission OR linked via
     * paymentgateway_issuer_users (see docs/api-contract.md: "offered to issuers
     * directly, not just admins") - but the target issuer isn't known until the
     * request body is read, so that check runs in the controller instead of here.
     * PaymentReferencePolicy::create() only knows the blanket admin permission and
     * would otherwise 403 legitimately-linked issuer callers before the controller
     * ever runs.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->setModel(PaymentReference::class);

        if ($this->isStore()) {
            return true;
        }

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
                'autopay_enabled' => ['sometimes', 'boolean'],
                'autopay_payment_number' => ['required_with:autopay_frequency_days', 'integer', 'min:1'],
                'autopay_frequency_days' => ['required_with:autopay_payment_number', 'integer', 'min:1'],
            ]);
        }

        return $rules;
    }

    /**
     * amount/due_date are required when the issuer's own reference_layout declares
     * amount_length/embed_due_date - see docs/api-contract.md. Enforced here (not in
     * ReferenceGeneratorService) so a failure is a proper 422 validation error, not a
     * generic exception.
     */
    public function withValidator(Validator $validator)
    {
        if (!$this->isStore()) {
            return;
        }

        $validator->after(function (Validator $validator) {
            $issuer = Issuer::findByHash($this->get('issuer_id'));

            if (!$issuer) {
                return;
            }

            if (data_get($issuer->reference_layout, 'amount_length') && !$this->filled('amount')) {
                $validator->errors()->add('amount', trans('validation.required', ['attribute' => 'amount']));
            }

            if (data_get($issuer->reference_layout, 'embed_due_date') && !$this->filled('due_date')) {
                $validator->errors()->add('due_date', trans('validation.required', ['attribute' => 'due date']));
            }
        });
    }
}
