<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\Invoice;

class InvoiceRequest extends BaseRequest
{
    /**
     * Same reasoning as PaymentReferenceRequest::authorize() - the target
     * issuer isn't known until the request body is read, so the specific
     * issuer-access check runs in the controller instead of here.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->setModel(Invoice::class);

        if ($this->isStore()) {
            return true;
        }

        return $this->isAuthorized();
    }

    /**
     * The create/edit form submits a decimal amount as amount_input - convert
     * it to the minor-units amount_minor column before validation runs.
     */
    public function validationData()
    {
        if (($this->isStore() || $this->isUpdate()) && $this->filled('amount_input') && !$this->filled('amount_minor')) {
            $this->merge(['amount_minor' => (int) round((float) $this->input('amount_input') * 100)]);
        }

        return parent::validationData();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->setModel(Invoice::class);
        $rules = parent::rules();

        if ($this->isUpdate() || $this->isStore()) {
            $rules = array_merge($rules, [
                'issuer_id' => ['required'],
                'customer_id' => ['required', 'string', 'max:255'],
                'amount_minor' => ['required', 'integer', 'min:1'],
                'currency' => ['required', 'string', 'size:3'],
                'due_date' => ['required', 'date'],
                'description' => ['nullable', 'string'],
            ]);
        }

        return $rules;
    }
}
