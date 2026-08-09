<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;
use Corals\Modules\PaymentGateway\Models\Branch;

class BranchRequest extends BaseRequest
{
    public function authorize()
    {
        $this->setModel(Branch::class);

        return $this->isAuthorized();
    }

    public function rules()
    {
        $this->setModel(Branch::class);
        $rules = parent::rules();

        if ($this->isUpdate() || $this->isStore()) {
            $rules = array_merge($rules, [
                'name' => ['required', 'string', 'max:255'],
            ]);
        }

        if ($this->isStore()) {
            $rules = array_merge($rules, [
                'store_id' => ['required'],
            ]);
        }

        return $rules;
    }
}
