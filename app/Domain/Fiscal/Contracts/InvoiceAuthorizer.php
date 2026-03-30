<?php

namespace App\Domain\Fiscal\Contracts;

use App\Domain\Fiscal\Data\FiscalAuthorizationResult;

interface InvoiceAuthorizer
{
    public function authorize(array $context): FiscalAuthorizationResult;
}
