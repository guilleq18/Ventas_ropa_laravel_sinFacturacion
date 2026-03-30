<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Core\Models\Sucursal;
use App\Domain\Fiscal\Support\ArcaCredentialManager;
use App\Domain\Fiscal\Support\ArcaHomologationProbe;
use App\Domain\Fiscal\Support\FiscalConfigManager;
use App\Http\Controllers\Controller;
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
        $branches = Sucursal::query()->where('activa', true)->orderBy('nombre')->get();
        $selectedBranch = null;

        if ($request->filled('sucursal')) {
            $selectedBranch = $branches->firstWhere('id', (int) $request->query('sucursal'));
        }

        $selectedBranch ??= $branches->first();

        return view('admin-panel.settings.index', [
            'branches' => $branches,
            'selectedBranch' => $selectedBranch,
            'tab' => $tab,
            'companyData' => $settingsManager->getCompanyData(),
            'arcaCredentials' => $arcaCredentialManager->status(),
            'settingsSections' => $selectedBranch ? [[
                'title' => 'Ventas',
                'subtitle' => 'Permisos operativos del POS para la sucursal seleccionada.',
                'options' => $settingsManager->salesFlagsUi($selectedBranch),
            ]] : [],
            'fiscalUi' => $selectedBranch ? $fiscalConfigManager->branchUi($selectedBranch) : null,
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

    protected function redirectToSettings(?int $branchId = null, string $tab = 'facturacion'): RedirectResponse
    {
        $params = ['tab' => $this->resolveTab($tab)];

        if ($branchId) {
            $params['sucursal'] = $branchId;
        }

        return redirect()->route('admin-panel.settings.index', $params);
    }
}
