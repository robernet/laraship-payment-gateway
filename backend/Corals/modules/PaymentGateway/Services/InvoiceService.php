<?php

namespace Corals\Modules\PaymentGateway\Services;

use Corals\Foundation\Services\BaseServiceClass;

class InvoiceService extends BaseServiceClass
{
    /**
     * amount_input isn't a paymentgateway_invoices column - only the
     * converted amount_minor (set by InvoiceRequest::validationData()) is.
     */
    protected $excludedRequestParams = ['amount_input'];
}
