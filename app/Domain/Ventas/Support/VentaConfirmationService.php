<?php

namespace App\Domain\Ventas\Support;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Caja\Support\CajaManager;
use App\Domain\Catalogo\Models\StockSucursal;
use App\Domain\Catalogo\Models\Variante;
use App\Domain\Core\Models\Sucursal;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use App\Domain\Ventas\Models\PlanCuotas;
use App\Domain\Ventas\Models\Venta;
use App\Domain\Ventas\Models\VentaItem;
use App\Domain\Ventas\Models\VentaPago;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DomainException;
use Illuminate\Support\Facades\DB;

class VentaConfirmationService
{
    public function __construct(
        protected AdminSettingsManager $settingsManager,
        protected CajaManager $cajaManager,
    ) {
    }

    public function confirmFromPos(
        User $user,
        Sucursal $branch,
        array $cartRows,
        array $paymentRows,
    ): Venta {
        if ($cartRows === []) {
            throw new DomainException('El carrito esta vacio.');
        }

        if ($paymentRows === []) {
            throw new DomainException('No hay pagos cargados.');
        }

        return DB::transaction(function () use ($user, $branch, $cartRows, $paymentRows): Venta {
            $branch = Sucursal::query()
                ->whereKey($branch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $branch->activa) {
                throw new DomainException("La sucursal {$branch->nombre} esta inactiva.");
            }

            $cashSession = $this->cajaManager->assertOperable($user, $branch, true);
            $allowWithoutStock = $this->cajaManager->allowSellWithoutStock($branch);

            $sale = Venta::query()->create([
                'sucursal_id' => $branch->id,
                'caja_sesion_id' => $cashSession->id,
                'cajero_id' => $user->id,
                'fecha' => now(),
                'cliente_id' => $this->resolveSaleClientId($paymentRows),
                'estado' => Venta::ESTADO_BORRADOR,
                'medio_pago' => $this->resolveSalePaymentMethod($paymentRows),
                'total' => '0.00',
            ]);

            $items = [];
            $itemsTotal = BigDecimal::zero();

            foreach ($cartRows as $row) {
                /** @var Variante $variant */
                $variant = Variante::query()
                    ->with('producto')
                    ->findOrFail($row['variant']->id);

                if (! $variant->activo || ! $variant->producto?->activo) {
                    throw new DomainException("La variante {$variant->sku} ya no esta disponible para vender.");
                }

                $item = VentaItem::query()->create([
                    'venta_id' => $sale->id,
                    'variante_id' => $variant->id,
                    'cantidad' => (int) $row['qty'],
                    'precio_unitario' => $this->money($row['price']),
                ]);

                $items[] = $item;
                $itemsTotal = $itemsTotal->plus($this->decimal($item->subtotal));

                if (! $allowWithoutStock) {
                    StockSucursal::query()->firstOrCreate(
                        [
                            'sucursal_id' => $branch->id,
                            'variante_id' => $variant->id,
                        ],
                        ['cantidad' => 0],
                    );

                    $stock = StockSucursal::query()
                        ->where('sucursal_id', $branch->id)
                        ->where('variante_id', $variant->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($stock->cantidad < (int) $row['qty']) {
                        throw new DomainException(
                            "Stock insuficiente para {$variant->sku}. Disponible: {$stock->cantidad}, requerido: {$row['qty']}.",
                        );
                    }

                    $stock->cantidad -= (int) $row['qty'];
                    $stock->save();
                }
            }

            $paymentsTotalBase = BigDecimal::zero();
            $paymentsTotalFinal = BigDecimal::zero();
            $paymentsRecargos = BigDecimal::zero();

            foreach ($paymentRows as $row) {
                $amount = $this->decimal($row['monto']);

                if ($amount->isLessThanOrEqualTo(BigDecimal::zero())) {
                    throw new DomainException('Todos los pagos deben tener un monto mayor a cero.');
                }

                $recargoPct = $this->decimal($row['recargo_pct'] ?? '0');
                $recargoAmount = $this->decimal($row['recargo_monto'] ?? '0');
                $lineTotal = $this->decimal($row['line_total'] ?? $row['monto']);
                $plan = null;

                if (($row['plan_id'] ?? '') !== '') {
                    $plan = PlanCuotas::query()
                        ->whereKey((int) $row['plan_id'])
                        ->where('activo', true)
                        ->first();
                }

                if ($row['tipo'] === VentaPago::TIPO_CUENTA_CORRIENTE) {
                    $account = CuentaCorriente::query()
                        ->with('cliente')
                        ->where('cliente_id', (int) ($row['cc_cliente_id'] ?? 0))
                        ->where('activa', true)
                        ->whereHas('cliente', fn ($builder) => $builder->where('activo', true))
                        ->lockForUpdate()
                        ->first();

                    if (! $account) {
                        throw new DomainException('Cuenta corriente: selecciona un cliente con cuenta activa.');
                    }
                }

                VentaPago::query()->create([
                    'venta_id' => $sale->id,
                    'tipo' => $row['tipo'],
                    'monto' => $this->money($amount),
                    'cuotas' => (int) ($row['cuotas'] ?? 1),
                    'coeficiente' => $this->coefficientFromPercentage($recargoPct),
                    'recargo_pct' => $this->money($recargoPct),
                    'recargo_monto' => $this->money($recargoAmount),
                    'plan_id' => $plan?->id,
                    'referencia' => (string) ($row['referencia'] ?? ''),
                ]);

                $paymentsTotalBase = $paymentsTotalBase->plus($amount);
                $paymentsRecargos = $paymentsRecargos->plus($recargoAmount);
                $paymentsTotalFinal = $paymentsTotalFinal->plus($lineTotal);
            }

            $expectedFinal = $itemsTotal->plus($paymentsRecargos)->toScale(2, RoundingMode::HALF_UP);

            if (! $paymentsTotalBase->toScale(2, RoundingMode::HALF_UP)->isEqualTo($itemsTotal->toScale(2, RoundingMode::HALF_UP))) {
                throw new DomainException(
                    "Pagos base incompletos. Total items $ {$this->money($itemsTotal)} - Base cargada $ {$this->money($paymentsTotalBase)}.",
                );
            }

            if (! $paymentsTotalFinal->toScale(2, RoundingMode::HALF_UP)->isEqualTo($expectedFinal)) {
                throw new DomainException(
                    "Pagos incompletos. Total a cobrar $ {$this->money($expectedFinal)} - Pagado $ {$this->money($paymentsTotalFinal)}.",
                );
            }

            $sale->numero_sucursal = $this->nextBranchNumber($branch);
            $sale->total = $this->money($expectedFinal);
            $sale->estado = Venta::ESTADO_CONFIRMADA;
            $this->applyCompanySnapshot($sale, $items);
            $sale->save();

            foreach ($paymentRows as $row) {
                if ($row['tipo'] !== VentaPago::TIPO_CUENTA_CORRIENTE) {
                    continue;
                }

                $account = CuentaCorriente::query()
                    ->where('cliente_id', (int) ($row['cc_cliente_id'] ?? 0))
                    ->where('activa', true)
                    ->lockForUpdate()
                    ->first();

                if (! $account) {
                    throw new DomainException('Cuenta corriente: selecciona un cliente con cuenta activa.');
                }

                MovimientoCuentaCorriente::query()->create([
                    'cuenta_id' => $account->id,
                    'tipo' => MovimientoCuentaCorriente::TIPO_DEBITO,
                    'monto' => $this->money($row['monto']),
                    'fecha' => now(),
                    'venta_id' => $sale->id,
                    'referencia' => "Venta #{$sale->id}",
                    'observacion' => 'Debito generado desde POS Laravel',
                ]);
            }

            return $sale->load(['items.variante.producto', 'pagos.plan']);
        });
    }

    protected function resolveSaleClientId(array $paymentRows): ?int
    {
        $clientIds = collect($paymentRows)
            ->filter(fn (array $row) => $row['tipo'] === VentaPago::TIPO_CUENTA_CORRIENTE)
            ->pluck('cc_cliente_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($clientIds->count() > 1) {
            throw new DomainException(
                'Cuenta corriente: todos los pagos deben corresponder al mismo cliente.',
            );
        }

        return $clientIds->first();
    }

    protected function resolveSalePaymentMethod(array $paymentRows): string
    {
        $types = collect($paymentRows)
            ->pluck('tipo')
            ->filter()
            ->unique()
            ->values();

        if ($types->count() === 1) {
            return match ((string) $types->first()) {
                VentaPago::TIPO_CONTADO => Venta::MEDIO_PAGO_EFECTIVO,
                VentaPago::TIPO_DEBITO => Venta::MEDIO_PAGO_DEBITO,
                VentaPago::TIPO_CREDITO => Venta::MEDIO_PAGO_CREDITO,
                VentaPago::TIPO_TRANSFERENCIA, VentaPago::TIPO_QR => Venta::MEDIO_PAGO_TRANSFERENCIA,
                VentaPago::TIPO_CUENTA_CORRIENTE => Venta::MEDIO_PAGO_CUENTA_CORRIENTE,
                default => Venta::MEDIO_PAGO_EFECTIVO,
            };
        }

        return $types->isEmpty()
            ? Venta::MEDIO_PAGO_EFECTIVO
            : Venta::MEDIO_PAGO_MIXTO;
    }

    protected function nextBranchNumber(Sucursal $branch): int
    {
        $currentMax = (int) (
            Venta::query()
                ->where('sucursal_id', $branch->id)
                ->whereNotNull('numero_sucursal')
                ->max('numero_sucursal') ?? 0
        );

        return $currentMax + 1;
    }

    protected function applyCompanySnapshot(Venta $sale, array $items): void
    {
        $company = $this->settingsManager->getCompanyData();

        $net = BigDecimal::zero();
        $iva = BigDecimal::zero();
        $other = BigDecimal::zero();

        foreach ($items as $item) {
            $net = $net->plus($this->decimal($item->subtotal_sin_impuestos_nacionales));
            $iva = $iva->plus($this->decimal($item->subtotal_iva_contenido));
            $other = $other->plus($this->decimal($item->subtotal_otros_impuestos_nacionales_indirectos));
        }

        $sale->empresa_nombre_snapshot = (string) ($company['nombre'] ?? '');
        $sale->empresa_razon_social_snapshot = (string) ($company['razon_social'] ?? '');
        $sale->empresa_cuit_snapshot = (string) ($company['cuit'] ?? '');
        $sale->empresa_direccion_snapshot = (string) ($company['direccion'] ?? '');
        $sale->empresa_condicion_fiscal_snapshot = (string) ($company['condicion_fiscal'] ?? '');
        $sale->fiscal_items_sin_impuestos_nacionales = $this->money($net);
        $sale->fiscal_items_iva_contenido = $this->money($iva);
        $sale->fiscal_items_otros_impuestos_nacionales_indirectos = $this->money($other);
    }

    protected function decimal(mixed $value): BigDecimal
    {
        $string = trim((string) ($value ?? '0'));

        if ($string === '') {
            $string = '0';
        }

        return BigDecimal::of($string)->toScale(2, RoundingMode::HALF_UP);
    }

    protected function money(mixed $value): string
    {
        return $this->decimal($value)->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    protected function coefficientFromPercentage(BigDecimal $percentage): string
    {
        return BigDecimal::one()
            ->plus($percentage->dividedBy('100', 4, RoundingMode::HALF_UP))
            ->toScale(4, RoundingMode::HALF_UP)
            ->__toString();
    }
}
