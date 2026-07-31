<?php

namespace Corals\Modules\PaymentGateway\Transformers\API;

use Corals\Foundation\Transformers\APIBaseTransformer;
use Corals\Modules\PaymentGateway\Models\Issuer;

class IssuerTransformer extends APIBaseTransformer
{
    /**
     * @param Issuer $issuer
     * @return array
     * @throws \Throwable
     */
    public function transform(Issuer $issuer)
    {
        $transformedArray = [
            'id' => $issuer->id,
            'name' => $issuer->name,
            'sub_id' => $issuer->sub_id,
            'reference_layout' => $issuer->reference_layout,
            'reject_late_payment' => (bool) $issuer->reject_late_payment,
            'created_at' => format_date($issuer->created_at),
            'updated_at' => format_date($issuer->updated_at),
        ];

        return parent::transformResponse($transformedArray);
    }
}
