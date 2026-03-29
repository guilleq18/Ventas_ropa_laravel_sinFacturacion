<?php

namespace App\Http\Controllers\AdminPanel;

use App\Domain\Admin\Support\AdminReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBalancesController extends Controller
{
    public function index(Request $request, AdminReportService $reportService): View
    {
        return view('admin-panel.balances.index', [
            'report' => $reportService->balancesData(
                $request->query('from'),
                $request->query('to'),
                (string) $request->query('vista', 'ventas'),
            ),
        ]);
    }
}
