<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Core\Models\Sucursal;
use App\Domain\Fiscal\Models\ArcaCaeaPeriodo;
use App\Domain\Fiscal\Models\ArcaCaeaComprobante;
use App\Domain\Fiscal\Models\VentaComprobante;
use App\Domain\Fiscal\Support\ArcaCredentialManager;
use App\Domain\Fiscal\Support\ArcaHomologationProbe;
use App\Domain\Fiscal\Support\FiscalConfigManager;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function index(
        Request $request,
        AdminSettingsManager $settingsManager,
        FiscalConfigManager $fiscalConfigManager,
        ArcaCredentialManager $arcaCredentialManager,
    ): View
    {
        $tab = $this->resolveTab((string) $request->query('tab', ''));
        $facturacionTab = $this->resolveFacturacionTab((string) $request->query('facturacion_tab', ''));
        $arcaCredentials = $arcaCredentialManager->status();
        $branches = Sucursal::query()->where('activa', true)->orderBy('nombre')->get();
        $selectedBranch = null;
        $authorizedDocuments = null;
        $authorizedDocumentsFilters = [
            'q' => '',
            'from' => '',
            'to' => '',
            'sucursal' => '',
        ];
        $authorizedDocumentsSummary = [
            'count' => 0,
            'branches_count' => 0,
            'total_amount' => 0.0,
            'latest_issued_at' => null,
        ];
        $caeaPeriods = null;
        $caeaPeriodsFilters = [
            'entorno' => '',
            'estado_solicitud' => '',
            'estado_informacion' => '',
            'cuit' => '',
            'q' => '',
        ];
        $caeaPeriodsSummary = [
            'count' => 0,
            'authorized_count' => 0,
            'pending_information_count' => 0,
            'overdue_count' => 0,
            'latest_sync_at' => null,
        ];

        if ($request->filled('sucursal')) {
            $selectedBranch = $branches->firstWhere('id', (int) $request->query('sucursal'));
        }

        $selectedBranch ??= $branches->first();

        if ($tab === 'facturacion' && $facturacionTab === 'comprobantes') {
            $authorizedDocumentsIndex = $this->buildAuthorizedDocumentsIndex($request, $selectedBranch);
            $authorizedDocuments = $authorizedDocumentsIndex['documents'];
            $authorizedDocumentsFilters = $authorizedDocumentsIndex['filters'];
            $authorizedDocumentsSummary = $authorizedDocumentsIndex['summary'];
        }

        if ($tab === 'facturacion' && $facturacionTab === 'caea') {
            $caeaPeriodsIndex = $this->buildCaeaPeriodsIndex($request, $arcaCredentials);
            $caeaPeriods = $caeaPeriodsIndex['periods'];
            $caeaPeriodsFilters = $caeaPeriodsIndex['filters'];
            $caeaPeriodsSummary = $caeaPeriodsIndex['summary'];
        }

        return view('admin-panel.settings.index', [
            'branches' => $branches,
            'selectedBranch' => $selectedBranch,
            'tab' => $tab,
            'facturacionTab' => $facturacionTab,
            'companyData' => $settingsManager->getCompanyData(),
            'arcaCredentials' => $arcaCredentials,
            'settingsSections' => $selectedBranch ? [[
                'title' => 'Ventas',
                'subtitle' => 'Permisos operativos del POS para la sucursal seleccionada.',
                'options' => $settingsManager->salesFlagsUi($selectedBranch),
            ]] : [],
            'fiscalUi' => $selectedBranch ? $fiscalConfigManager->branchUi($selectedBranch) : null,
            'authorizedDocuments' => $authorizedDocuments,
            'authorizedDocumentsFilters' => $authorizedDocumentsFilters,
            'authorizedDocumentsSummary' => $authorizedDocumentsSummary,
            'caeaPeriods' => $caeaPeriods,
            'caeaPeriodsFilters' => $caeaPeriodsFilters,
            'caeaPeriodsSummary' => $caeaPeriodsSummary,
        ]);
    }

    public function update(
        Request $request,
        AdminSettingsManager $settingsManager,
        FiscalConfigManager $fiscalConfigManager,
    ): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
        ]);
        $tab = $this->resolveTab((string) $request->input('tab', ''));

        $branch = Sucursal::query()->findOrFail($validated['sucursal_id']);
        $flags = [];

        if ($tab === 'ventas') {
            foreach ($settingsManager->salesFlagsCatalog() as $flag) {
                $flags[$flag['name']] = $request->boolean($flag['name']);
            }
        }

        if ($request->hasAny([
            'fiscal_modo_operacion',
            'fiscal_entorno',
            'fiscal_punto_venta',
            'fiscal_facturacion_habilitada',
            'fiscal_requiere_receptor_en_todas',
            'fiscal_domicilio_fiscal_emision',
        ])) {
            $fiscalPayload = [
                'modo_operacion' => (string) $request->input('fiscal_modo_operacion', ''),
                'entorno' => (string) $request->input('fiscal_entorno', ''),
                'punto_venta' => $request->input('fiscal_punto_venta'),
                'facturacion_habilitada' => $request->boolean('fiscal_facturacion_habilitada'),
                'requiere_receptor_en_todas' => $request->boolean('fiscal_requiere_receptor_en_todas'),
                'domicilio_fiscal_emision' => (string) $request->input('fiscal_domicilio_fiscal_emision', ''),
            ];

            $errors = $fiscalConfigManager->validateBranchConfig($branch, $fiscalPayload);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $fiscalConfigManager->saveBranchConfig($branch, $fiscalPayload);
        }

        if ($tab === 'ventas') {
            $settingsManager->saveSalesFlags($branch, $flags);
        }

        return redirect()
            ->route('admin-panel.settings.index', ['sucursal' => $branch->id, 'tab' => $tab])
            ->with('success', $tab === 'facturacion'
                ? "Configuración fiscal actualizada para {$branch->nombre}."
                : "Configuración de ventas actualizada para {$branch->nombre}.");
    }

    public function generateArcaCsr(
        Request $request,
        ArcaCredentialManager $arcaCredentialManager,
    ): RedirectResponse {
        $validated = $request->validate([
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
            'arca_represented_cuit' => ['required', 'string', 'max:32'],
            'arca_alias' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9]+$/'],
            'arca_organization' => ['required', 'string', 'max:160'],
            'arca_common_name' => ['required', 'string', 'max:160'],
        ], [
            'arca_alias.regex' => 'El alias DN solo puede contener letras y números, sin espacios ni símbolos.',
        ]);

        try {
            $arcaCredentialManager->generateKeyAndCsr(
                (string) $validated['arca_represented_cuit'],
                (string) $validated['arca_alias'],
                (string) $validated['arca_organization'],
                (string) $validated['arca_common_name'],
            );
        } catch (\Throwable $exception) {
            return $this->redirectToSettings($validated['sucursal_id'] ?? null, 'credenciales')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return $this->redirectToSettings($validated['sucursal_id'] ?? null, 'credenciales')
            ->with('success', 'Se generaron la clave privada y la CSR. Copiá la CSR en WSASS y luego cargá el certificado devuelto por ARCA.');
    }

    public function uploadArcaCertificate(
        Request $request,
        ArcaCredentialManager $arcaCredentialManager,
    ): RedirectResponse {
        $validated = $request->validate([
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
            'arca_certificate_file' => ['nullable', 'file', 'max:512'],
            'arca_certificate_pem' => ['nullable', 'string'],
        ]);

        if (
            ! $request->hasFile('arca_certificate_file')
            && trim((string) ($validated['arca_certificate_pem'] ?? '')) === ''
        ) {
            throw ValidationException::withMessages([
                'arca_certificate_file' => 'Subí un archivo .crt/.pem o pegá el certificado completo.',
            ]);
        }

        try {
            $result = $arcaCredentialManager->storeCertificate(
                $request->file('arca_certificate_file'),
                (string) ($validated['arca_certificate_pem'] ?? ''),
            );
        } catch (\Throwable $exception) {
            return $this->redirectToSettings($validated['sucursal_id'] ?? null, 'credenciales')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return $this->redirectToSettings($validated['sucursal_id'] ?? null, 'credenciales')
            ->with('success', 'Certificado ARCA cargado y vinculado con la clave privada actual.')
            ->with('arca_validation', Arr::only($result['validation'], [
                'ok',
                'key_matches_certificate',
                'warnings',
                'represented_cuit',
                'subject',
                'issuer',
                'subject_cuit',
                'valid_from',
                'valid_to',
                'certificate_path',
                'private_key_path',
            ]));
    }

    public function validateArcaCredentials(
        Request $request,
        ArcaCredentialManager $arcaCredentialManager,
    ): RedirectResponse {
        $validated = $request->validate([
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
        ]);

        try {
            $result = $arcaCredentialManager->validateConfiguredCredentials();
        } catch (\Throwable $exception) {
            return $this->redirectToSettings($validated['sucursal_id'] ?? null, 'credenciales')
                ->with('error', $exception->getMessage());
        }

        return $this->redirectToSettings($validated['sucursal_id'] ?? null, 'credenciales')
            ->with($result['ok'] ? 'success' : 'warning', $result['ok']
                ? 'Validación local OK: la clave privada y el certificado coinciden.'
                : 'La validación local detectó observaciones en las credenciales ARCA.')
            ->with('arca_validation', $result);
    }

    public function probeArca(
        Request $request,
        ArcaHomologationProbe $arcaHomologationProbe,
    ): RedirectResponse {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
        ]);

        $branch = Sucursal::query()->findOrFail($validated['sucursal_id']);

        try {
            $report = $arcaHomologationProbe->probeBranch($branch);
        } catch (\Throwable $exception) {
            return $this->redirectToSettings($branch->id, 'credenciales')
                ->with('error', $exception->getMessage());
        }

        return $this->redirectToSettings($branch->id, 'credenciales')
            ->with('success', "Prueba ARCA completada para {$branch->nombre}.")
            ->with('arca_probe', [
                'branch' => $report['branch'] ?? ['id' => $branch->id, 'nombre' => $branch->nombre],
                'environment' => $report['environment'] ?? null,
                'point_of_sale' => $report['point_of_sale'] ?? null,
                'receipt_class' => $report['receipt_class'] ?? null,
                'receipt_code' => $report['receipt_code'] ?? null,
                'readiness' => $report['readiness'] ?? [],
                'wsfe_dummy' => Arr::except((array) ($report['wsfe_dummy'] ?? []), ['raw_xml']),
                'wsaa' => Arr::except((array) ($report['wsaa'] ?? []), ['raw_xml']),
                'last_authorized' => Arr::except((array) ($report['last_authorized'] ?? []), ['raw_xml']),
            ]);
    }

    protected function resolveTab(string $value): string
    {
        return in_array($value, ['credenciales', 'facturacion', 'ventas'], true) ? $value : 'ventas';
    }

    protected function resolveFacturacionTab(string $value): string
    {
        return in_array($value, ['configuracion', 'comprobantes', 'caea'], true) ? $value : 'configuracion';
    }

    protected function buildAuthorizedDocumentsIndex(Request $request, ?Sucursal $selectedBranch): array
    {
        $rawFrom = trim((string) $request->query('documentos_from', ''));
        $rawTo = trim((string) $request->query('documentos_to', ''));
        $search = trim((string) $request->query('documentos_q', ''));
        $defaultBranchId = $selectedBranch?->id ? (string) $selectedBranch->id : '';
        $sucursalId = trim((string) $request->query('documentos_sucursal', $defaultBranchId));
        $searchDigits = preg_replace('/\D+/', '', $search) ?: '';
        $searchCode = strtoupper($search);

        $query = VentaComprobante::query()
            ->with(['sucursal', 'venta'])
            ->where('modo_emision', VentaComprobante::MODO_ELECTRONICA_ARCA)
            ->where('estado', VentaComprobante::ESTADO_AUTORIZADO)
            ->whereNotNull('cae')
            ->when($rawFrom !== '', function (Builder $builder) use ($rawFrom): void {
                $builder->where('fecha_emision', '>=', CarbonImmutable::parse($rawFrom)->startOfDay());
            })
            ->when($rawTo !== '', function (Builder $builder) use ($rawTo): void {
                $builder->where('fecha_emision', '<=', CarbonImmutable::parse($rawTo)->endOfDay());
            })
            ->when(ctype_digit($sucursalId), fn (Builder $builder) => $builder->where('sucursal_id', (int) $sucursalId))
            ->when($search !== '', function (Builder $builder) use ($search, $searchDigits, $searchCode): void {
                $builder->where(function (Builder $query) use ($search, $searchDigits, $searchCode): void {
                    $query->orWhere('cae', 'like', "%{$search}%")
                        ->orWhere('receptor_nombre', 'like', "%{$search}%")
                        ->orWhere('doc_nro_receptor', 'like', '%'.($searchDigits !== '' ? $searchDigits : $search).'%')
                        ->orWhereHas('sucursal', fn (Builder $branchQuery) => $branchQuery->where('nombre', 'like', "%{$search}%"));

                    if ($searchDigits !== '') {
                        $query->orWhere('id', (int) $searchDigits)
                            ->orWhere('venta_id', (int) $searchDigits)
                            ->orWhere('numero_comprobante', (int) $searchDigits)
                            ->orWhereHas('venta', function (Builder $saleQuery) use ($searchDigits): void {
                                $saleQuery->where('id', (int) $searchDigits)
                                    ->orWhere('numero_sucursal', (int) $searchDigits);
                            });
                    }

                    if (
                        str_starts_with($searchCode, 'V')
                        && ctype_digit(substr($searchCode, 1))
                    ) {
                        $query->orWhereHas('venta', fn (Builder $saleQuery) => $saleQuery->where('numero_sucursal', (int) substr($searchCode, 1)));
                    }

                    if (preg_match('/^(\d{1,5})-(\d{1,8})$/', $search, $matches) === 1) {
                        $query->orWhere(function (Builder $numberQuery) use ($matches): void {
                            $numberQuery->where('punto_venta', (int) $matches[1])
                                ->where('numero_comprobante', (int) $matches[2]);
                        });
                    }
                });
            })
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id');

        $summaryBaseQuery = clone $query;
        $documents = $query->paginate(20)->withQueryString();
        $latestIssuedAt = (clone $summaryBaseQuery)->max('fecha_emision');

        return [
            'documents' => $documents,
            'filters' => [
                'q' => $search,
                'from' => $rawFrom,
                'to' => $rawTo,
                'sucursal' => $sucursalId,
            ],
            'summary' => [
                'count' => (clone $summaryBaseQuery)->count(),
                'branches_count' => (clone $summaryBaseQuery)->distinct()->count('sucursal_id'),
                'total_amount' => (float) ((clone $summaryBaseQuery)->sum('importe_total')),
                'latest_issued_at' => $latestIssuedAt ? CarbonImmutable::parse((string) $latestIssuedAt) : null,
            ],
        ];
    }

    protected function buildCaeaPeriodsIndex(Request $request, array $arcaCredentials): array
    {
        $defaultCuit = preg_replace('/\D+/', '', (string) data_get($arcaCredentials, 'represented_cuit.value', '')) ?: '';
        $environment = trim((string) $request->query('caea_entorno', ''));
        $requestState = trim((string) $request->query('caea_estado_solicitud', ''));
        $informationState = trim((string) $request->query('caea_estado_informacion', ''));
        $representedCuit = preg_replace('/\D+/', '', (string) $request->query('caea_cuit', $defaultCuit)) ?: '';
        $search = trim((string) $request->query('caea_q', ''));
        $searchDigits = preg_replace('/\D+/', '', $search) ?: '';

        $query = ArcaCaeaPeriodo::query()
            ->with([
                'comprobantes' => function ($builder): void {
                    $builder->with(['sucursal', 'ventaComprobante'])
                        ->orderBy('fecha_emision')
                        ->orderBy('punto_venta')
                        ->orderBy('numero_comprobante');
                },
            ])
            ->withCount('comprobantes')
            ->withCount([
                'comprobantes as comprobantes_informados_count' => fn (Builder $builder) => $builder->where('estado_rendicion', ArcaCaeaComprobante::ESTADO_RENDICION_INFORMADO),
                'comprobantes as comprobantes_pendientes_count' => fn (Builder $builder) => $builder->where('estado_rendicion', ArcaCaeaComprobante::ESTADO_RENDICION_PENDIENTE),
                'comprobantes as comprobantes_observados_count' => fn (Builder $builder) => $builder->where('estado_rendicion', ArcaCaeaComprobante::ESTADO_RENDICION_OBSERVADO),
                'comprobantes as comprobantes_rechazados_count' => fn (Builder $builder) => $builder->where('estado_rendicion', ArcaCaeaComprobante::ESTADO_RENDICION_RECHAZADO),
            ])
            ->when($environment !== '', fn (Builder $builder) => $builder->where('entorno', $environment))
            ->when($requestState !== '', fn (Builder $builder) => $builder->where('estado_solicitud', $requestState))
            ->when($informationState !== '', fn (Builder $builder) => $builder->where('estado_informacion', $informationState))
            ->when($representedCuit !== '', fn (Builder $builder) => $builder->where('cuit_representada', $representedCuit))
            ->when($search !== '', function (Builder $builder) use ($search, $searchDigits): void {
                $builder->where(function (Builder $query) use ($search, $searchDigits): void {
                    $query->orWhere('caea', 'like', "%{$search}%")
                        ->orWhere('cuit_representada', 'like', '%'.($searchDigits !== '' ? $searchDigits : $search).'%')
                        ->orWhere('periodo', 'like', '%'.($searchDigits !== '' ? $searchDigits : $search).'%');

                    if (preg_match('/^(\d{6})-(\d)$/', $search, $matches) === 1) {
                        $query->orWhere(function (Builder $periodQuery) use ($matches): void {
                            $periodQuery->where('periodo', (int) $matches[1])
                                ->where('orden', (int) $matches[2]);
                        });
                    }
                });
            })
            ->orderByDesc('periodo')
            ->orderByDesc('orden')
            ->orderByDesc('id');

        $summaryBaseQuery = clone $query;
        $periods = $query->paginate(20)->withQueryString();
        $latestSyncAt = (clone $summaryBaseQuery)->max('ultimo_synced_at');

        return [
            'periods' => $periods,
            'filters' => [
                'entorno' => $environment,
                'estado_solicitud' => $requestState,
                'estado_informacion' => $informationState,
                'cuit' => $representedCuit,
                'q' => $search,
            ],
            'summary' => [
                'count' => (clone $summaryBaseQuery)->count(),
                'authorized_count' => (clone $summaryBaseQuery)
                    ->where('estado_solicitud', ArcaCaeaPeriodo::ESTADO_SOLICITUD_AUTORIZADO)
                    ->count(),
                'pending_information_count' => (clone $summaryBaseQuery)
                    ->whereIn('estado_informacion', [
                        ArcaCaeaPeriodo::ESTADO_INFORMACION_PENDIENTE,
                        ArcaCaeaPeriodo::ESTADO_INFORMACION_PARCIAL,
                    ])
                    ->count(),
                'overdue_count' => (clone $summaryBaseQuery)
                    ->whereDate('fecha_tope_informar', '<', CarbonImmutable::today()->toDateString())
                    ->whereIn('estado_informacion', [
                        ArcaCaeaPeriodo::ESTADO_INFORMACION_PENDIENTE,
                        ArcaCaeaPeriodo::ESTADO_INFORMACION_PARCIAL,
                    ])
                    ->count(),
                'latest_sync_at' => $latestSyncAt ? CarbonImmutable::parse((string) $latestSyncAt) : null,
            ],
        ];
    }

    protected function redirectToSettings(?int $branchId = null, string $tab = 'facturacion'): RedirectResponse
    {
        $params = ['tab' => $this->resolveTab($tab)];

        if ($branchId) {
            $params['sucursal'] = $branchId;
        }

        return redirect()->route('admin-panel.settings.index', $params);
    }
}
