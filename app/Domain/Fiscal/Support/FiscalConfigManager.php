<?php

namespace App\Domain\Fiscal\Support;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Core\Models\Sucursal;
use App\Domain\Fiscal\Models\SucursalFiscalConfig;
use App\Domain\Fiscal\Support\FiscalDocumentBuilder;
use App\Domain\Ventas\Models\Venta;
use DomainException;
use Illuminate\Support\Facades\File;

class FiscalConfigManager
{
    public const array MODE_LABELS = [
        SucursalFiscalConfig::MODO_SOLO_REGISTRO => 'Solo registro',
        SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA => 'Facturar si se solicita',
        SucursalFiscalConfig::MODO_FACTURACION_OBLIGATORIA => 'Facturación obligatoria',
    ];

    public const array ENVIRONMENT_LABELS = [
        SucursalFiscalConfig::ENTORNO_HOMOLOGACION => 'Homologación',
        SucursalFiscalConfig::ENTORNO_PRODUCCION => 'Producción',
    ];

    public function __construct(
        protected AdminSettingsManager $settingsManager,
        protected FiscalDocumentBuilder $fiscalDocumentBuilder,
        protected ArcaCredentialManager $arcaCredentialManager,
    ) {
    }

    public function modes(): array
    {
        return self::MODE_LABELS;
    }

    public function environments(): array
    {
        return self::ENVIRONMENT_LABELS;
    }

    public function branchConfig(?Sucursal $branch): array
    {
        $config = $branch?->relationLoaded('fiscalConfig')
            ? $branch?->fiscalConfig
            : $branch?->fiscalConfig()->first();

        return [
            'modo_operacion' => $this->normalizeMode((string) ($config?->modo_operacion ?? '')),
            'entorno' => $this->normalizeEnvironment((string) ($config?->entorno ?? '')),
            'punto_venta' => $config?->punto_venta,
            'facturacion_habilitada' => (bool) ($config?->facturacion_habilitada ?? false),
            'requiere_receptor_en_todas' => (bool) ($config?->requiere_receptor_en_todas ?? false),
            'domicilio_fiscal_emision' => trim((string) ($config?->domicilio_fiscal_emision ?? '')),
            'ultimo_synced_at' => $config?->ultimo_synced_at,
            'model' => $config,
        ];
    }

    public function branchUi(?Sucursal $branch): array
    {
        $config = $this->branchConfig($branch);
        $issues = $this->electronicReadinessIssues($branch, $config);
        $actions = $this->allowedActions($config, $issues);
        $credentials = $this->arcaCredentialStatus();

        return [
            ...$config,
            'mode_label' => self::MODE_LABELS[$config['modo_operacion']] ?? self::MODE_LABELS[SucursalFiscalConfig::MODO_SOLO_REGISTRO],
            'environment_label' => self::ENVIRONMENT_LABELS[$config['entorno']] ?? self::ENVIRONMENT_LABELS[SucursalFiscalConfig::ENTORNO_HOMOLOGACION],
            'modes' => $this->modes(),
            'environments' => $this->environments(),
            'electronic_ready' => $issues === [] && $config['facturacion_habilitada'],
            'electronic_issues' => $issues,
            'allowed_actions' => $actions,
            'default_action' => $this->defaultAction($config, $actions),
            'default_class' => $this->defaultDocumentClass(),
            'receiver_doc_types' => $this->fiscalDocumentBuilder->electronicReceiverOptions(),
            'receiver_vat_conditions' => $this->fiscalDocumentBuilder->electronicReceiverVatConditionOptions($this->defaultDocumentClass()),
            'default_receiver' => $this->fiscalDocumentBuilder->defaultReceiverDraft($this->defaultDocumentClass()),
            'consumer_final_threshold' => (float) config('fiscal.consumer_final_identification_threshold', 10000000),
            'gateway' => (string) config('fiscal.gateway', 'fake'),
            'arca_credentials' => $credentials,
        ];
    }

    public function saveBranchConfig(Sucursal $branch, array $payload): SucursalFiscalConfig
    {
        return SucursalFiscalConfig::query()->updateOrCreate(
            ['sucursal_id' => $branch->id],
            [
                'modo_operacion' => $this->normalizeMode((string) ($payload['modo_operacion'] ?? '')),
                'entorno' => $this->normalizeEnvironment((string) ($payload['entorno'] ?? '')),
                'punto_venta' => $this->normalizePointOfSale($payload['punto_venta'] ?? null),
                'facturacion_habilitada' => (bool) ($payload['facturacion_habilitada'] ?? false),
                'requiere_receptor_en_todas' => (bool) ($payload['requiere_receptor_en_todas'] ?? false),
                'domicilio_fiscal_emision' => trim((string) ($payload['domicilio_fiscal_emision'] ?? '')) ?: null,
            ],
        );
    }

    public function validateBranchConfig(Sucursal $branch, array $payload): array
    {
        $errors = [];
        $facturacionHabilitada = (bool) ($payload['facturacion_habilitada'] ?? false);
        $pointOfSale = $this->normalizePointOfSale($payload['punto_venta'] ?? null);

        if ($facturacionHabilitada && $pointOfSale === null) {
            $errors['fiscal_punto_venta'] = 'Define el punto de venta para habilitar facturación electrónica.';
        }

        if ($facturacionHabilitada) {
            $issues = $this->electronicReadinessIssues($branch, [
                ...$this->branchConfig($branch),
                'facturacion_habilitada' => true,
                'punto_venta' => $pointOfSale,
            ]);

            if ($issues !== []) {
                $errors['fiscal_facturacion_habilitada'] = implode(' ', $issues);
            }
        }

        return $errors;
    }

    public function resolveAction(Sucursal $branch, ?string $requestedAction): string
    {
        $ui = $this->branchUi($branch);
        $action = Venta::normalizeFiscalAction($requestedAction);

        if (! ($ui['allowed_actions'][$action]['allowed'] ?? false)) {
            return $ui['default_action'];
        }

        return $action;
    }

    public function assertActionAllowed(Sucursal $branch, string $action): array
    {
        $ui = $this->branchUi($branch);
        $normalized = Venta::normalizeFiscalAction($action);
        $row = $ui['allowed_actions'][$normalized] ?? null;

        if (! $row || ! $row['allowed']) {
            throw new DomainException($row['reason'] ?? 'La acción fiscal seleccionada no está disponible para esta sucursal.');
        }

        return $ui;
    }

    public function defaultDocumentClass(): string
    {
        return $this->settingsManager->getFiscalCondition() === AdminSettingsManager::FISCAL_RESPONSABLE_INSCRIPTO
            ? 'B'
            : 'C';
    }

    protected function allowedActions(array $config, array $issues): array
    {
        $mode = $config['modo_operacion'];
        $electronicAllowed = $mode !== SucursalFiscalConfig::MODO_SOLO_REGISTRO
            && $config['facturacion_habilitada']
            && $issues === [];
        $electronicReason = null;

        if ($mode === SucursalFiscalConfig::MODO_SOLO_REGISTRO) {
            $electronicReason = 'La sucursal está configurada solo para registro interno.';
        } elseif (! $config['facturacion_habilitada']) {
            $electronicReason = 'La facturación electrónica no está habilitada en la sucursal.';
        } elseif ($issues !== []) {
            $electronicReason = implode(' ', $issues);
        }

        $externalAllowed = $mode !== SucursalFiscalConfig::MODO_SOLO_REGISTRO;
        $soloAllowed = $mode !== SucursalFiscalConfig::MODO_FACTURACION_OBLIGATORIA;

        return [
            Venta::ACCION_FISCAL_SOLO_REGISTRO => [
                'allowed' => $soloAllowed,
                'label' => 'Solo registro',
                'help' => 'Registra la venta y mantiene el ticket interno como documento no fiscal.',
                'reason' => $soloAllowed ? null : 'La sucursal requiere un comprobante fiscal o una referencia externa.',
            ],
            Venta::ACCION_FISCAL_FACTURA_ELECTRONICA => [
                'allowed' => $electronicAllowed,
                'label' => 'Factura electrónica',
                'help' => 'Solicita autorización a ARCA, guarda CAE y genera el comprobante fiscal imprimible.',
                'reason' => $electronicReason,
            ],
            Venta::ACCION_FISCAL_FACTURA_EXTERNA_REFERENCIADA => [
                'allowed' => $externalAllowed,
                'label' => 'Comprobante externo',
                'help' => 'Registra la venta y referencia un comprobante emitido por fuera del sistema.',
                'reason' => $externalAllowed ? null : 'La sucursal está configurada solo para registro interno.',
            ],
        ];
    }

    protected function defaultAction(array $config, array $actions): string
    {
        if ($config['modo_operacion'] === SucursalFiscalConfig::MODO_FACTURACION_OBLIGATORIA) {
            return $actions[Venta::ACCION_FISCAL_FACTURA_ELECTRONICA]['allowed']
                ? Venta::ACCION_FISCAL_FACTURA_ELECTRONICA
                : Venta::ACCION_FISCAL_FACTURA_EXTERNA_REFERENCIADA;
        }

        return Venta::ACCION_FISCAL_SOLO_REGISTRO;
    }

    protected function electronicReadinessIssues(?Sucursal $branch, ?array $config = null): array
    {
        if (! $branch) {
            return ['No hay una sucursal operativa seleccionada.'];
        }

        $config ??= $this->branchConfig($branch);
        $company = $this->settingsManager->getCompanyData();
        $issues = [];

        if (trim((string) ($company['razon_social'] ?? '')) === '') {
            $issues[] = 'Completa la razón social de la empresa.';
        }

        if (trim((string) ($company['cuit'] ?? '')) === '') {
            $issues[] = 'Completa el CUIT de la empresa.';
        }

        if (trim((string) ($company['direccion'] ?? '')) === '') {
            $issues[] = 'Completa la dirección de la empresa.';
        }

        if ($this->normalizePointOfSale($config['punto_venta'] ?? null) === null) {
            $issues[] = 'Define el punto de venta fiscal de la sucursal.';
        }

        if ((string) config('fiscal.gateway', 'fake') === 'arca') {
            $representedCuit = preg_replace('/\D+/', '', $this->arcaCredentialManager->resolvedRepresentedCuit());
            $companyCuit = preg_replace('/\D+/', '', (string) ($company['cuit'] ?? ''));

            if (strlen((string) ($representedCuit ?: $companyCuit)) !== 11) {
                $issues[] = 'Configura un CUIT representado válido para ARCA.';
            }

            $certificatePath = trim($this->arcaCredentialManager->resolvedCertificatePath());
            $privateKeyPath = trim($this->arcaCredentialManager->resolvedPrivateKeyPath());

            if ($certificatePath === '' || ! File::exists($this->credentialAbsolutePath($certificatePath))) {
                $issues[] = 'Configura un certificado válido en ARCA_CERTIFICATE_PATH.';
            }

            if ($privateKeyPath === '' || ! File::exists($this->credentialAbsolutePath($privateKeyPath))) {
                $issues[] = 'Configura una clave privada válida en ARCA_PRIVATE_KEY_PATH.';
            }
        }

        return $issues;
    }

    protected function arcaCredentialStatus(): array
    {
        return $this->arcaCredentialManager->status();
    }

    protected function normalizeMode(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return match ($normalized) {
            SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA => SucursalFiscalConfig::MODO_FACTURAR_SI_SE_SOLICITA,
            SucursalFiscalConfig::MODO_FACTURACION_OBLIGATORIA => SucursalFiscalConfig::MODO_FACTURACION_OBLIGATORIA,
            default => SucursalFiscalConfig::MODO_SOLO_REGISTRO,
        };
    }

    protected function normalizeEnvironment(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));

        return match ($normalized) {
            SucursalFiscalConfig::ENTORNO_PRODUCCION => SucursalFiscalConfig::ENTORNO_PRODUCCION,
            default => SucursalFiscalConfig::ENTORNO_HOMOLOGACION,
        };
    }

    protected function normalizePointOfSale(mixed $value): ?int
    {
        $string = trim((string) ($value ?? ''));

        if ($string === '' || ! ctype_digit($string)) {
            return null;
        }

        $pointOfSale = (int) $string;

        return $pointOfSale > 0 ? $pointOfSale : null;
    }

    protected function credentialAbsolutePath(string $path): string
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\\\\\\\|\\/)/', $path) === 1
            ? $path
            : base_path($path);
    }
}
