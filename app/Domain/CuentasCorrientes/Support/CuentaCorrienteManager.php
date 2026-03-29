<?php

namespace App\Domain\CuentasCorrientes\Support;

use App\Domain\CuentasCorrientes\Models\Cliente;
use App\Domain\CuentasCorrientes\Models\CuentaCorriente;
use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use App\Domain\CuentasCorrientes\Models\PagoCuentaCorriente;
use App\Domain\CuentasCorrientes\Models\PagoCuentaCorrienteAplicacion;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CuentaCorrienteManager
{
    public function createCuentaCorriente(array $payload): CuentaCorriente
    {
        $dni = trim((string) $payload['dni']);
        $cliente = Cliente::query()->where('dni', $dni)->first();

        if ($cliente?->cuentaCorriente()->exists()) {
            throw ValidationException::withMessages([
                'dni' => "El cliente {$dni} ya tiene cuenta corriente.",
            ]);
        }

        return DB::transaction(function () use ($payload, $cliente, $dni): CuentaCorriente {
            $cliente ??= Cliente::query()->create([
                'dni' => $dni,
                'nombre' => trim((string) $payload['nombre']),
                'apellido' => trim((string) $payload['apellido']),
                'telefono' => trim((string) ($payload['telefono'] ?? '')) ?: null,
                'direccion' => trim((string) ($payload['direccion'] ?? '')) ?: null,
                'fecha_nacimiento' => $payload['fecha_nacimiento'] ?? null,
                'activo' => true,
            ]);

            /** @var CuentaCorriente $cuenta */
            $cuenta = CuentaCorriente::query()->create([
                'cliente_id' => $cliente->id,
                'activa' => true,
            ]);

            return $cuenta->load('cliente');
        });
    }

    public function registerCredit(CuentaCorriente $cuenta, array $payload): MovimientoCuentaCorriente
    {
        $selectedSaleIds = collect($payload['ventas'] ?? [])
            ->map(fn (mixed $saleId) => (int) $saleId)
            ->filter(fn (int $saleId) => $saleId > 0)
            ->unique()
            ->values();

        if ($selectedSaleIds->isEmpty()) {
            throw ValidationException::withMessages([
                'ventas' => 'Selecciona al menos una venta para asignar el pago.',
            ]);
        }

        return DB::transaction(function () use ($cuenta, $payload, $selectedSaleIds): MovimientoCuentaCorriente {
            $pendingSales = $this->pendingSales($cuenta, true)
                ->keyBy(fn (MovimientoCuentaCorriente $movement) => (int) $movement->venta_id);

            $selectedDebits = $selectedSaleIds
                ->map(fn (int $saleId) => $pendingSales->get($saleId))
                ->filter(fn (mixed $movement) => $movement instanceof MovimientoCuentaCorriente)
                ->values();

            if ($selectedDebits->count() !== $selectedSaleIds->count()) {
                throw ValidationException::withMessages([
                    'ventas' => 'Las ventas seleccionadas no pertenecen a la cuenta o ya no tienen saldo pendiente.',
                ]);
            }

            $paymentAmount = $this->decimal($payload['monto'] ?? '0');
            $selectedPendingTotal = $this->sumMovementPendingAmounts($selectedDebits);

            if ($paymentAmount->isGreaterThan($selectedPendingTotal)) {
                throw ValidationException::withMessages([
                    'monto' => 'El monto supera el saldo pendiente de las ventas seleccionadas.',
                ]);
            }

            /** @var MovimientoCuentaCorriente $movement */
            $movement = $cuenta->movimientos()->create([
                'tipo' => MovimientoCuentaCorriente::TIPO_CREDITO,
                'monto' => $paymentAmount->toScale(2, RoundingMode::HALF_UP)->__toString(),
                'fecha' => now(),
                'referencia' => trim((string) ($payload['referencia'] ?? '')) ?: null,
                'observacion' => trim((string) ($payload['observacion'] ?? '')) ?: null,
                'venta_id' => null,
            ]);

            /** @var PagoCuentaCorriente $payment */
            $payment = PagoCuentaCorriente::query()->create([
                'cuenta_id' => $cuenta->id,
                'movimiento_credito_id' => $movement->id,
            ]);

            $remaining = $paymentAmount;

            foreach ($selectedDebits as $debit) {
                if ($remaining->isLessThanOrEqualTo(BigDecimal::zero())) {
                    break;
                }

                $pending = $this->decimal($debit->monto_pendiente_calc ?? '0');

                if ($pending->isLessThanOrEqualTo(BigDecimal::zero())) {
                    continue;
                }

                $applied = $remaining->isGreaterThan($pending)
                    ? $pending
                    : $remaining;

                PagoCuentaCorrienteAplicacion::query()->create([
                    'pago_cuenta_corriente_id' => $payment->id,
                    'movimiento_debito_id' => $debit->id,
                    'monto_aplicado' => $applied->toScale(2, RoundingMode::HALF_UP)->__toString(),
                ]);

                $remaining = $remaining
                    ->minus($applied)
                    ->toScale(2, RoundingMode::HALF_UP);
            }

            return $movement->load('pagoCuentaCorriente.aplicaciones.movimientoDebito.venta');
        });
    }

    public function pendingSales(CuentaCorriente $cuenta, bool $lockForUpdate = false): Collection
    {
        $query = MovimientoCuentaCorriente::query()
            ->from('movimientos_cuenta_corriente as debitos')
            ->select('debitos.*')
            ->selectRaw('COALESCE(aplicaciones.total_aplicado, 0) as monto_aplicado_total')
            ->leftJoinSub($this->debitApplicationsSubquery(), 'aplicaciones', function ($join): void {
                $join->on('aplicaciones.movimiento_debito_id', '=', 'debitos.id');
            })
            ->where('debitos.cuenta_id', $cuenta->id)
            ->where('debitos.tipo', MovimientoCuentaCorriente::TIPO_DEBITO)
            ->orderBy('debitos.fecha')
            ->orderBy('debitos.id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $movements = $query->get();
        $movements->load('venta');

        $referenceNow = now();
        $cutoff = $referenceNow->copy()->subDays(30);

        return $movements
            ->map(function (MovimientoCuentaCorriente $movement) use ($referenceNow, $cutoff): MovimientoCuentaCorriente {
                $applied = $this->decimal($movement->monto_aplicado_total ?? '0');
                $pending = $this->decimal($movement->monto)->minus($applied);

                $movement->monto_aplicado_calc = $applied
                    ->toScale(2, RoundingMode::HALF_UP)
                    ->__toString();
                $movement->monto_pendiente_calc = $pending
                    ->toScale(2, RoundingMode::HALF_UP)
                    ->__toString();
                $movement->antiguedad_dias_calc = $movement->fecha
                    ? (int) $movement->fecha->diffInDays($referenceNow)
                    : 0;
                $movement->vencida_30_calc = $movement->fecha !== null
                    && $movement->fecha->lt($cutoff)
                    && $pending->isGreaterThan(BigDecimal::zero());

                return $movement;
            })
            ->filter(fn (MovimientoCuentaCorriente $movement) => $this->decimal($movement->monto_pendiente_calc ?? '0')->isGreaterThan(BigDecimal::zero()))
            ->values();
    }

    public function overdueSummaryForAccount(CuentaCorriente $cuenta, ?Collection $pendingSales = null): array
    {
        $pendingSales ??= $this->pendingSales($cuenta);
        $overdueSales = $pendingSales
            ->filter(fn (MovimientoCuentaCorriente $movement) => (bool) ($movement->vencida_30_calc ?? false))
            ->values();

        return [
            'items' => $overdueSales,
            'count' => $overdueSales->count(),
            'total' => $this->sumMovementPendingAmounts($overdueSales)
                ->toScale(2, RoundingMode::HALF_UP)
                ->__toString(),
        ];
    }

    public function overdueSummaryByAccount(): array
    {
        $cutoff = now()->subDays(30);

        return DB::table('movimientos_cuenta_corriente as debitos')
            ->leftJoinSub($this->debitApplicationsSubquery(), 'aplicaciones', function ($join): void {
                $join->on('aplicaciones.movimiento_debito_id', '=', 'debitos.id');
            })
            ->where('debitos.tipo', MovimientoCuentaCorriente::TIPO_DEBITO)
            ->where('debitos.fecha', '<', $cutoff)
            ->selectRaw('debitos.cuenta_id, SUM(debitos.monto - COALESCE(aplicaciones.total_aplicado, 0)) as total_vencido')
            ->groupBy('debitos.cuenta_id')
            ->havingRaw('SUM(debitos.monto - COALESCE(aplicaciones.total_aplicado, 0)) > 0')
            ->pluck('total_vencido', 'debitos.cuenta_id')
            ->map(fn (mixed $value) => $this->money($value))
            ->all();
    }

    public function computeSaldo(?string $debitos, ?string $creditos): string
    {
        return $this->decimal($debitos ?: '0')
            ->minus($this->decimal($creditos ?: '0'))
            ->toScale(2, RoundingMode::HALF_UP)
            ->__toString();
    }

    public function sumAmounts(iterable $values): string
    {
        $total = BigDecimal::zero();

        foreach ($values as $value) {
            $total = $total->plus($this->decimal($value));
        }

        return $total->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    protected function sumMovementPendingAmounts(Collection $movements): BigDecimal
    {
        return $movements->reduce(
            fn (BigDecimal $carry, MovimientoCuentaCorriente $movement) => $carry->plus(
                $this->decimal($movement->monto_pendiente_calc ?? '0'),
            ),
            BigDecimal::zero(),
        );
    }

    protected function debitApplicationsSubquery()
    {
        return DB::table('pago_cuenta_corriente_aplicaciones')
            ->selectRaw('movimiento_debito_id, SUM(monto_aplicado) as total_aplicado')
            ->groupBy('movimiento_debito_id');
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
}
