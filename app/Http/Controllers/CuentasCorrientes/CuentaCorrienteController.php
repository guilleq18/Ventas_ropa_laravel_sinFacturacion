<?php

namespace App\Http\Controllers\CuentasCorrientes;

use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use App\Domain\CuentasCorrientes\Support\CuentaCorrienteManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\CuentasCorrientes\RegisterPagoCuentaCorrienteRequest;
use App\Http\Requests\CuentasCorrientes\StoreCuentaCorrienteRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CuentaCorrienteController extends Controller
{
    public function index(Request $request, CuentaCorrienteManager $manager): View
    {
        $search = trim((string) $request->query('q', ''));
        $activa = (string) $request->query('activa', '1');
        $overdueByAccount = $manager->overdueSummaryByAccount();

        $cuentas = CuentaCorriente::query()
            ->select('cuentas_corrientes.*')
            ->join('clientes', 'clientes.id', '=', 'cuentas_corrientes.cliente_id')
            ->with('cliente')
            ->withSum([
                'movimientos as debitos' => fn (Builder $query) => $query->where('tipo', MovimientoCuentaCorriente::TIPO_DEBITO),
            ], 'monto')
            ->withSum([
                'movimientos as creditos' => fn (Builder $query) => $query->where('tipo', MovimientoCuentaCorriente::TIPO_CREDITO),
            ], 'monto')
            ->when(
                in_array($activa, ['0', '1'], true),
                fn (Builder $query) => $query->where('cuentas_corrientes.activa', $activa === '1'),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('clientes.dni', 'like', "%{$search}%")
                        ->orWhere('clientes.apellido', 'like', "%{$search}%")
                        ->orWhere('clientes.nombre', 'like', "%{$search}%");
                });
            })
            ->orderBy('clientes.apellido')
            ->orderBy('clientes.nombre')
            ->get()
            ->each(function (CuentaCorriente $cuenta) use ($manager): void {
                $cuenta->saldo_calc = $manager->computeSaldo(
                    (string) ($cuenta->debitos ?? '0'),
                    (string) ($cuenta->creditos ?? '0'),
                );
                $cuenta->overdue_30_calc = '0.00';
                $cuenta->has_overdue_30_calc = false;
            });

        $cuentas->each(function (CuentaCorriente $cuenta) use ($overdueByAccount): void {
            $cuenta->overdue_30_calc = (string) ($overdueByAccount[$cuenta->id] ?? '0.00');
            $cuenta->has_overdue_30_calc = $cuenta->overdue_30_calc !== '0.00';
        });

        $saldoTotal = $manager->computeSaldo(
            (string) (MovimientoCuentaCorriente::query()
                ->where('tipo', MovimientoCuentaCorriente::TIPO_DEBITO)
                ->sum('monto') ?: '0'),
            (string) (MovimientoCuentaCorriente::query()
                ->where('tipo', MovimientoCuentaCorriente::TIPO_CREDITO)
                ->sum('monto') ?: '0'),
        );

        return view('cuentas-corrientes.index', [
            'cuentas' => $cuentas,
            'filters' => [
                'q' => $search,
                'activa' => $activa,
            ],
            'stats' => [
                'cuentas_total' => CuentaCorriente::query()->count(),
                'cuentas_activas' => CuentaCorriente::query()->where('activa', true)->count(),
                'clientes_activos' => CuentaCorriente::query()
                    ->join('clientes', 'clientes.id', '=', 'cuentas_corrientes.cliente_id')
                    ->where('clientes.activo', true)
                    ->count(),
                'saldo_total' => $saldoTotal,
                'cuentas_con_alerta' => count($overdueByAccount),
                'saldo_vencido_30' => $manager->sumAmounts($overdueByAccount),
            ],
        ]);
    }

    public function store(
        StoreCuentaCorrienteRequest $request,
        CuentaCorrienteManager $manager,
    ): RedirectResponse {
        $cuenta = $manager->createCuentaCorriente($request->validatedPayload());

        return redirect()
            ->route('cuentas-corrientes.show', $cuenta)
            ->with('success', 'Cuenta corriente creada correctamente.');
    }

    public function lookupDni(Request $request): JsonResponse
    {
        $dni = trim((string) $request->query('dni', ''));

        if ($dni === '') {
            return response()->json([
                'status' => 'missing_dni',
                'message' => 'Ingresa un DNI para validar la cuenta corriente.',
            ], 422);
        }

        if (mb_strlen($dni) > 20) {
            return response()->json([
                'status' => 'invalid_dni',
                'message' => 'El DNI no puede superar los 20 caracteres.',
            ], 422);
        }

        $cliente = Cliente::query()
            ->with('cuentaCorriente')
            ->where('dni', $dni)
            ->first();

        if (! $cliente) {
            return response()->json([
                'status' => 'available',
                'message' => 'No existe un cliente con ese DNI. Se creara una cuenta nueva.',
                'dni' => $dni,
                'cliente' => null,
                'cuenta_corriente' => null,
            ]);
        }

        $payload = [
            'dni' => $dni,
            'cliente' => [
                'id' => $cliente->id,
                'dni' => $cliente->dni,
                'nombre' => $cliente->nombre,
                'apellido' => $cliente->apellido,
                'nombre_completo' => $cliente->nombre_completo,
                'telefono' => $cliente->telefono,
                'direccion' => $cliente->direccion,
                'fecha_nacimiento' => $cliente->fecha_nacimiento?->format('Y-m-d'),
                'activo' => $cliente->activo,
            ],
        ];

        if ($cliente->cuentaCorriente) {
            return response()->json([
                ...$payload,
                'status' => 'duplicate_account',
                'message' => "El cliente {$cliente->nombre_completo} ya tiene cuenta corriente.",
                'cuenta_corriente' => [
                    'id' => $cliente->cuentaCorriente->id,
                    'activa' => $cliente->cuentaCorriente->activa,
                    'show_url' => route('cuentas-corrientes.show', $cliente->cuentaCorriente),
                ],
            ]);
        }

        return response()->json([
            ...$payload,
            'status' => 'existing_client',
            'message' => "Ya existe el cliente {$cliente->nombre_completo}. Se reutilizaran sus datos.",
            'cuenta_corriente' => null,
        ]);
    }

    public function show(CuentaCorriente $cuentaCorriente, CuentaCorrienteManager $manager): View
    {
        $cuentaCorriente->load('cliente');
        [$pendingSales, $overdueSummary] = $this->paymentCollections($cuentaCorriente, $manager);

        return view('cuentas-corrientes.show', [
            'cuenta' => $cuentaCorriente,
            'cliente' => $cuentaCorriente->cliente,
            'saldo' => $cuentaCorriente->saldo(),
            'alertaVencidas' => $overdueSummary,
            'ventasPendientesCount' => $pendingSales->count(),
            'movimientos' => $cuentaCorriente->movimientos()
                ->with([
                    'venta',
                    'pagoCuentaCorriente.aplicaciones.movimientoDebito.venta',
                ])
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->limit(200)
                ->get(),
        ]);
    }

    public function createPayment(CuentaCorriente $cuentaCorriente, CuentaCorrienteManager $manager): View
    {
        $cuentaCorriente->load('cliente');
        [$pendingSales, $overdueSummary] = $this->paymentCollections($cuentaCorriente, $manager);

        return view('cuentas-corrientes.payment', [
            'cuenta' => $cuentaCorriente,
            'cliente' => $cuentaCorriente->cliente,
            'saldo' => $cuentaCorriente->saldo(),
            'ventasPendientes' => $pendingSales,
            'alertaVencidas' => $overdueSummary,
        ]);
    }

    public function toggle(CuentaCorriente $cuentaCorriente): RedirectResponse
    {
        $cuentaCorriente->update([
            'activa' => ! $cuentaCorriente->activa,
        ]);

        return redirect()
            ->route('cuentas-corrientes.show', $cuentaCorriente)
            ->with('success', 'Estado de la cuenta corriente actualizado.');
    }

    public function registerPayment(
        RegisterPagoCuentaCorrienteRequest $request,
        CuentaCorriente $cuentaCorriente,
        CuentaCorrienteManager $manager,
    ): RedirectResponse {
        $manager->registerCredit($cuentaCorriente, $request->validatedPayload());

        return redirect()
            ->route('cuentas-corrientes.payments.create', $cuentaCorriente)
            ->with('success', 'Pago registrado y aplicado a las ventas seleccionadas.');
    }

    protected function paymentCollections(
        CuentaCorriente $cuentaCorriente,
        CuentaCorrienteManager $manager,
    ): array {
        $pendingSales = $manager->pendingSales($cuentaCorriente);
        $overdueSummary = $manager->overdueSummaryForAccount($cuentaCorriente, $pendingSales);

        return [$pendingSales, $overdueSummary];
    }
}
