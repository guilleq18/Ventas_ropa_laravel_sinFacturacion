<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Admin\Support\AdminReportService;
use App\Domain\Catalogo\Models\Producto;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(AdminReportService $reportService): View
    {
        $dashboard = $reportService->dashboardData();

        return view('admin-panel.dashboard', [
            'stats' => [
                ...$dashboard,
                'productos_activos' => Producto::query()->where('activo', true)->count(),
                'cuentas_activas' => CuentaCorriente::query()->where('activa', true)->count(),
                'usuarios_activos' => User::query()->where('is_active', true)->count(),
            ],
        ]);
    }
}
