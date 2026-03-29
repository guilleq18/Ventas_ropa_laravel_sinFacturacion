<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Ventas\Models\PlanCuotas;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlanCuotasRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminTarjetasController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $editPlan = null;

        if ($request->filled('edit_plan')) {
            $editPlan = PlanCuotas::query()->find($request->integer('edit_plan'));
        }

        return view('admin-panel.planes.index', [
            'plans' => PlanCuotas::query()
                ->when($search !== '', fn ($query) => $query->where('tarjeta', 'like', "%{$search}%"))
                ->orderBy('tarjeta')
                ->orderBy('cuotas')
                ->get(),
            'search' => $search,
            'editPlan' => $editPlan,
        ]);
    }

    public function store(PlanCuotasRequest $request): RedirectResponse
    {
        $search = trim((string) $request->input('q_context', ''));

        try {
            $plan = PlanCuotas::query()->create($request->validatedPayload());
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'tarjeta' => 'Ya existe un plan para esa tarjeta y cantidad de cuotas.',
            ]);
        }

        return redirect()
            ->route('admin-panel.tarjetas.index', $search !== '' ? ['q' => $search] : [])
            ->with('success', "Plan creado: {$plan->tarjeta} {$plan->cuotas} cuotas.");
    }

    public function update(PlanCuotasRequest $request, PlanCuotas $planCuota): RedirectResponse
    {
        $search = trim((string) $request->input('q_context', ''));

        try {
            $planCuota->update($request->validatedPayload());
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'tarjeta' => 'Ya existe un plan para esa tarjeta y cantidad de cuotas.',
            ]);
        }

        return redirect()
            ->route('admin-panel.tarjetas.index', $search !== '' ? ['q' => $search] : [])
            ->with('success', "Plan actualizado: {$planCuota->tarjeta} {$planCuota->cuotas} cuotas.");
    }

    public function destroy(Request $request, PlanCuotas $planCuota): RedirectResponse
    {
        $search = trim((string) $request->input('q_context', ''));
        $description = "{$planCuota->tarjeta} {$planCuota->cuotas} cuotas";
        $planCuota->delete();

        return redirect()
            ->route('admin-panel.tarjetas.index', $search !== '' ? ['q' => $search] : [])
            ->with('success', "Plan eliminado: {$description}.");
    }
}
