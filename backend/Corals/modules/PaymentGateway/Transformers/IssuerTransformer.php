<?php

namespace Corals\Modules\PaymentGateway\Transformers;

use Corals\Foundation\Transformers\BaseTransformer;
use Corals\Modules\PaymentGateway\Models\Issuer;

class IssuerTransformer extends BaseTransformer
{
    public function __construct($extras = [])
    {
        $this->resource_url = config('paymentgateway.models.issuer.resource_url');

        parent::__construct($extras);
    }

    /**
     * @param Issuer $issuer
     * @return array
     * @throws \Throwable
     */
    public function transform(Issuer $issuer)
    {
        $transformedArray = [
            'id' => $issuer->hashed_id,
            'name' => HtmlElement('a', ['href' => $issuer->getShowURL()], $issuer->name),
            'sub_id' => $issuer->sub_id,
            'reject_late_payment' => $issuer->reject_late_payment ? 'Yes' : 'No',
            'created_at' => format_date($issuer->created_at),
            'updated_at' => format_date($issuer->updated_at),
            'action' => $this->actions($issuer),
        ];

        return parent::transformResponse($transformedArray);
    }
}
