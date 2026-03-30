<?php

namespace App\Domain\Fiscal\Data;

class FiscalAuthorizationResult
{
    public function __construct(
        public readonly string $documentState,
        public readonly string $saleFiscalState,
        public readonly bool $saleHasFiscalDocument,
        public readonly string $eventType,
        public readonly string $eventDescription,
        public readonly array $eventPayload,
        public readonly array $documentAttributes = [],
    ) {
    }

    public function isAuthorized(): bool
    {
        return $this->documentState === 'AUTORIZADO';
    }
}
