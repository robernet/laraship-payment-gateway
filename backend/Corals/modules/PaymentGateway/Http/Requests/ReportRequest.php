<?php

namespace Corals\Modules\PaymentGateway\Http\Requests;

use Corals\Foundation\Http\Requests\BaseRequest;

class ReportRequest extends BaseRequest
{
    /**
     * Reports have no underlying model, so authorization is a direct
     * permission check rather than a Policy - ponytail: no Policy class for
     * a non-Eloquent resource; add one if reports grow additional abilities.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = user();

        return $user && ($user->hasPermissionTo('Administrations::admin.paymentgateway') || isSuperUser($user));
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ];
    }
}
