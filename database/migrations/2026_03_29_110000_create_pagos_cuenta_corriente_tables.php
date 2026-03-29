<?php

use App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_cuenta_corriente', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cuenta_id')
                ->constrained('cuentas_corrientes')
                ->cascadeOnDelete();
            $table->foreignId('movimiento_credito_id')
                ->unique()
                ->constrained('movimientos_cuenta_corriente')
                ->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cuenta_id', 'created_at']);
        });

        Schema::create('pago_cuenta_corriente_aplicaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pago_cuenta_corriente_id')
                ->constrained('pagos_cuenta_corriente')
                ->cascadeOnDelete();
            $table->foreignId('movimiento_debito_id')
                ->constrained('movimientos_cuenta_corriente')
                ->cascadeOnDelete();
            $table->decimal('monto_aplicado', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['pago_cuenta_corriente_id', 'movimiento_debito_id'],
                'pago_cc_aplicacion_por_movimiento_unique',
            );
            $table->index('movimiento_debito_id', 'pago_cc_aplicacion_movimiento_debito_idx');
        });

        $this->backfillExistingCredits();
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_cuenta_corriente_aplicaciones');
        Schema::dropIfExists('pagos_cuenta_corriente');
    }

    protected function backfillExistingCredits(): void
    {
        $accountIds = DB::table('movimientos_cuenta_corriente')
            ->distinct()
            ->orderBy('cuenta_id')
            ->pluck('cuenta_id');

        foreach ($accountIds as $accountId) {
            $debits = DB::table('movimientos_cuenta_corriente')
                ->where('cuenta_id', $accountId)
                ->where('tipo', MovimientoCuentaCorriente::TIPO_DEBITO)
                ->orderBy('fecha')
                ->orderBy('id')
                ->get(['id', 'monto']);

            $pendingByDebit = [];

            foreach ($debits as $debit) {
                $pendingByDebit[(int) $debit->id] = $this->toMoneyString($debit->monto);
            }

            $credits = DB::table('movimientos_cuenta_corriente')
                ->where('cuenta_id', $accountId)
                ->where('tipo', MovimientoCuentaCorriente::TIPO_CREDITO)
                ->orderBy('fecha')
                ->orderBy('id')
                ->get(['id', 'fecha', 'created_at', 'monto']);

            foreach ($credits as $credit) {
                $paymentId = DB::table('pagos_cuenta_corriente')->insertGetId([
                    'cuenta_id' => $accountId,
                    'movimiento_credito_id' => $credit->id,
                    'created_at' => $credit->created_at ?? $credit->fecha ?? now(),
                ]);

                $remaining = BigDecimal::of($this->toMoneyString($credit->monto));

                foreach ($pendingByDebit as $debitId => $pendingAmount) {
                    if ($remaining->isLessThanOrEqualTo(BigDecimal::zero())) {
                        break;
                    }

                    $pending = BigDecimal::of($pendingAmount);

                    if ($pending->isLessThanOrEqualTo(BigDecimal::zero())) {
                        continue;
                    }

                    $applied = $remaining->isGreaterThan($pending)
                        ? $pending
                        : $remaining;

                    DB::table('pago_cuenta_corriente_aplicaciones')->insert([
                        'pago_cuenta_corriente_id' => $paymentId,
                        'movimiento_debito_id' => $debitId,
                        'monto_aplicado' => $applied->toScale(2, RoundingMode::HALF_UP)->__toString(),
                        'created_at' => $credit->created_at ?? $credit->fecha ?? now(),
                    ]);

                    $pendingByDebit[$debitId] = $pending
                        ->minus($applied)
                        ->toScale(2, RoundingMode::HALF_UP)
                        ->__toString();

                    $remaining = $remaining
                        ->minus($applied)
                        ->toScale(2, RoundingMode::HALF_UP);
                }
            }
        }
    }

    protected function toMoneyString(mixed $value): string
    {
        return BigDecimal::of((string) ($value ?? '0'))
            ->toScale(2, RoundingMode::HALF_UP)
            ->__toString();
    }
};
