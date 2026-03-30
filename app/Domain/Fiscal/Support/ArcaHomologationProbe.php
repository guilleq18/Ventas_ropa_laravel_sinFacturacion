<?php

namespace App\Domain\Fiscal\Support;

use App\Domain\Core\Models\Sucursal;
use DomainException;

class ArcaHomologationProbe
{
    public function __construct(
        protected FiscalConfigManager $configManager,
        protected FiscalDocumentBuilder $documentBuilder,
        protected ArcaWsaaClient $wsaaClient,
        protected ArcaWsfeClient $wsfeClient,
        protected ArcaCredentialManager $arcaCredentialManager,
    ) {
    }

    public function probeBranch(Sucursal $branch, bool $attemptAuthorization = true): array
    {
        $ui = $this->configManager->branchUi($branch);
        $environment = (string) ($ui['entorno'] ?? 'HOMOLOGACION');
        $class = (string) ($ui['default_class'] ?? 'C');
        $receiptCode = $this->documentBuilder->receiptCodeForClass($class);
        $probe = [
            'branch' => $branch->only(['id', 'nombre']),
            'environment' => $environment,
            'point_of_sale' => (int) ($ui['punto_venta'] ?? 0),
            'receipt_class' => $class,
            'receipt_code' => $receiptCode,
            'readiness' => [
                'ready' => $ui['electronic_ready'] ?? false,
                'issues' => $ui['electronic_issues'] ?? [],
                'credentials' => $ui['arca_credentials'] ?? [],
            ],
            'wsfe_dummy' => null,
            'wsaa' => null,
            'last_authorized' => null,
        ];

        $probe['wsfe_dummy'] = $this->wsfeClient->dummy($environment);

        if (! $attemptAuthorization) {
            return $probe;
        }

        if (! ($ui['electronic_ready'] ?? false)) {
            throw new DomainException(
                'La sucursal no está lista para homologación real. Revisa el estado de preparación antes de probar WSAA.',
            );
        }

        $representedCuit = $this->resolvedRepresentedCuit();
        $ticket = $this->wsaaClient->accessTicket($environment);
        $probe['wsaa'] = [
            'expiration_time' => (string) ($ticket['expiration_time'] ?? ''),
        ];

        $probe['last_authorized'] = $this->wsfeClient->getLastAuthorized($environment, [
            'token' => $ticket['token'],
            'sign' => $ticket['sign'],
            'cuit' => $representedCuit,
        ], (int) $probe['point_of_sale'], $receiptCode);

        return $probe;
    }

    protected function resolvedRepresentedCuit(): int
    {
        $configured = preg_replace('/\D+/', '', $this->arcaCredentialManager->resolvedRepresentedCuit());

        if (strlen((string) $configured) !== 11) {
            throw new DomainException('Configura ARCA_REPRESENTED_CUIT con un CUIT válido para homologación.');
        }

        return (int) $configured;
    }
}
