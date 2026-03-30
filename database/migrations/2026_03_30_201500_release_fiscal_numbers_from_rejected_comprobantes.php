<?php

use App\Domain\Fiscal\Models\VentaComprobante;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('venta_comprobantes')
            ->where('estado', VentaComprobante::ESTADO_RECHAZADO)
            ->whereNotNull('numero_comprobante')
            ->update([
                'numero_comprobante' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No se restaura la numeración descartada de comprobantes rechazados.
    }
};
