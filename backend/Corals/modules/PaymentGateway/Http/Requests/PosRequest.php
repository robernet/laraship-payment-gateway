<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\Pos;
use Illuminate\Validation\Rule;

class PosRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->setModel(Pos::class);

        return $this->isAuthorized();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->setModel(Pos::class);
        $rules = parent::rules();

        if ($this->isUpdate() || $this->isStore()) {
            $rules = array_merge($rules, [
                'store_id' => ['required'],
                'name' => ['required', 'string', 'max:255'],
            ]);
        }

        if ($this->isStore()) {
            $rules = array_merge($rules, [
                'code' => ['required', 'string', 'max:255', Rule::unique('paymentgateway_pos', 'code')],
            ]);
        }

        if ($this->isUpdate()) {
            $pos = $this->route('pos');

            $rules = array_merge($rules, [
                'code' => [
                    'required', 'string', 'max:255',
                    Rule::unique('paymentgateway_pos', 'code')->ignore($pos?->id),
                ],
            ]);
        }

        return $rules;
    }
}
