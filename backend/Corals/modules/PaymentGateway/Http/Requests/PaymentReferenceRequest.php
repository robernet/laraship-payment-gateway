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
     * The web admin form submits a decimal amount as amount_input when
     * generating from a newly-created invoice (invoice_mode=new) - convert
     * it to amount_minor before validation runs, same as InvoiceRequest does.
     */
    public function validationData()
    {
        if ($this->isStore()
            && $this->input('invoice_mode', 'existing') === 'new'
            && $this->filled('amount_input')
            && !$this->filled('amount_minor')) {
            $this->merge(['amount_minor' => (int) round((float) $this->input('amount_input') * 100)]);
        }

        return parent::validationData();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The web admin form and the API both generate from an Invoice
     * (invoice_id) by default - it supplies the issuer, identifier, amount,
     * currency, and due date. When invoice_mode=new (admin form only), the
     * caller is instead creating that Invoice inline, so the Invoice's own
     * fields are required here and validated the same way InvoiceRequest
     * validates them.
     *
     * @return array
     */
    public function rules()
    {
        $this->setModel(PaymentReference::class);
        $rules = parent::rules();

        if ($this->isStore()) {
            $rules['invoice_mode'] = ['sometimes', 'in:existing,new'];

            if ($this->input('invoice_mode', 'existing') === 'new') {
                $rules = array_merge($rules, [
                    'issuer_id' => ['required'],
                    'customer_id' => ['required', 'string', 'max:255'],
                    'amount_minor' => ['required', 'integer', 'min:1'],
                    'currency' => ['required', 'string', 'size:3'],
                    'due_date' => ['required', 'date'],
                    'description' => ['nullable', 'string'],
                ]);
            } else {
                $rules['invoice_id'] = ['required'];
            }

            // AutoPay - schema/validation only (Phase 4 scaffold, no live gateway wiring yet).
            // required_if (not just required_with) so enabling autopay without both
            // sub-fields fails validation instead of reaching generateWithArtifacts()
            // with a null frequency, which would crash on now()->addDays(null).
            $rules = array_merge($rules, [
                'autopay_enabled' => ['sometimes', 'boolean'],
                'autopay_payment_number' => ['required_if:autopay_enabled,1', 'integer', 'min:1'],
                'autopay_frequency_days' => ['required_if:autopay_enabled,1', 'integer', 'min:1'],
            ]);
        }

        return $rules;
    }
}
