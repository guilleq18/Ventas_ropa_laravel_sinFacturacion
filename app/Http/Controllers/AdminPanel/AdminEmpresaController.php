<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Core\Models\Sucursal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyDataRequest;
use App\Http\Requests\Admin\SucursalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEmpresaController extends Controller
{
    public function index(Request $request, AdminSettingsManager $settingsManager): View
    {
        $tab = in_array($request->query('tab'), ['empresa', 'sucursales'], true)
            ? (string) $request->query('tab')
            : 'empresa';
        $editingBranch = null;

        if ($request->filled('edit_sucursal')) {
            $editingBranch = Sucursal::query()->find($request->integer('edit_sucursal'));
            $tab = 'sucursales';
        }

        return view('admin-panel.empresa.index', [
            'tab' => $tab,
            'company' => $settingsManager->getCompanyData(),
            'fiscalChoices' => $settingsManager->fiscalChoices(),
            'branches' => Sucursal::query()->orderByDesc('activa')->orderBy('nombre')->get(),
            'editingBranch' => $editingBranch,
        ]);
    }

    public function updateCompany(
        CompanyDataRequest $request,
        AdminSettingsManager $settingsManager,
    ): RedirectResponse {
        $settingsManager->saveCompanyData($request->validatedPayload());

        return redirect()
            ->route('admin-panel.empresa.index', ['tab' => 'empresa'])
            ->with('success', 'Datos de empresa actualizados.');
    }

    public function storeBranch(SucursalRequest $request): RedirectResponse
    {
        $branch = Sucursal::query()->create($request->validatedPayload());

        return redirect()
            ->route('admin-panel.empresa.index', ['tab' => 'sucursales'])
            ->with('success', "Sucursal creada: {$branch->nombre}.");
    }

    public function updateBranch(SucursalRequest $request, Sucursal $sucursal): RedirectResponse
    {
        $sucursal->update($request->validatedPayload());

        return redirect()
            ->route('admin-panel.empresa.index', ['tab' => 'sucursales'])
            ->with('success', "Sucursal actualizada: {$sucursal->nombre}.");
    }

    public function toggleBranch(Sucursal $sucursal): RedirectResponse
    {
        $sucursal->update([
            'activa' => ! $sucursal->activa,
        ]);

        $state = $sucursal->activa ? 'activada' : 'desactivada';

        return redirect()
            ->route('admin-panel.empresa.index', ['tab' => 'sucursales'])
            ->with('success', "Sucursal {$sucursal->nombre} {$state}.");
    }
}
