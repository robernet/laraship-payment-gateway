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

    /**
     * identifier_length and amount_length each pass their own `max` rule, but
     * ReferenceGeneratorService builds every reference as a fixed-width
     * PREFIX(3) + SUB_ID(3) + IDENTIFIER + AMOUNT + [DUE_DATE(8)] payload capped
     * at 28 digits - so the segments also have to fit together, not just alone.
     * Without this check an issuer can be saved with a layout that can never
     * generate a single valid reference (see ReferenceGeneratorService::generate()).
     *
     * @return array
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if (! $this->isStore() && ! $this->isUpdate()) {
                    return;
                }

                $budget = 28 - 3 - 3;

                $length = (int) $this->input('reference_layout.identifier_length')
                    + (int) $this->input('reference_layout.amount_length', 0)
                    + ($this->boolean('reference_layout.embed_due_date') ? 8 : 0);

                if ($length > $budget) {
                    $validator->errors()->add(
                        'reference_layout.identifier_length',
                        "The identifier, amount, and due-date lengths together must not exceed {$budget} digits (currently {$length})."
                    );
                }
            },
        ];
    }
}
