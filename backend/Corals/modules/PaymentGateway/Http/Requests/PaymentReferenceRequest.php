<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\PaymentReference;

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
     * Both the web admin form and the API generate from an Invoice
     * (invoice_id) - it supplies the issuer, identifier, amount, currency,
     * and due date, so none of those are separate inputs here.
     *
     * @return array
     */
    public function rules()
    {
        $this->setModel(PaymentReference::class);
        $rules = parent::rules();

        if ($this->isStore()) {
            $rules = array_merge($rules, [
                'invoice_id' => ['required'],

                // AutoPay - schema/validation only (Phase 4 scaffold, no live gateway wiring yet).
                'autopay_enabled' => ['sometimes', 'boolean'],
                'autopay_payment_number' => ['required_with:autopay_frequency_days', 'integer', 'min:1'],
                'autopay_frequency_days' => ['required_with:autopay_payment_number', 'integer', 'min:1'],
            ]);
        }

        return $rules;
    }
}
