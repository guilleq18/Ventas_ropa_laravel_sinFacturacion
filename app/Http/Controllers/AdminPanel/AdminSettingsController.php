<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Core\Models\Sucursal;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function index(Request $request, AdminSettingsManager $settingsManager): View
    {
        $branches = Sucursal::query()->where('activa', true)->orderBy('nombre')->get();
        $selectedBranch = null;

        if ($request->filled('sucursal')) {
            $selectedBranch = $branches->firstWhere('id', (int) $request->query('sucursal'));
        }

        $selectedBranch ??= $branches->first();

        return view('admin-panel.settings.index', [
            'branches' => $branches,
            'selectedBranch' => $selectedBranch,
            'settingsSections' => $selectedBranch ? [[
                'title' => 'Ventas',
                'subtitle' => 'Permisos operativos del POS para la sucursal seleccionada.',
                'options' => $settingsManager->salesFlagsUi($selectedBranch),
            ]] : [],
        ]);
    }

    public function update(Request $request, AdminSettingsManager $settingsManager): RedirectResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
        ]);

        $branch = Sucursal::query()->findOrFail($validated['sucursal_id']);
        $flags = [];

        foreach ($settingsManager->salesFlagsCatalog() as $flag) {
            $flags[$flag['name']] = $request->boolean($flag['name']);
        }

        $settingsManager->saveSalesFlags($branch, $flags);

        return redirect()
            ->route('admin-panel.settings.index', ['sucursal' => $branch->id])
            ->with('success', "Configuracion de ventas actualizada para {$branch->nombre}.");
    }
}
