<?php

namespace App\Domain\Caja\Support;

use App\Domain\Admin\Support\AdminSettingsManager;
use App\Domain\Caja\Models\CajaSesion;
use App\Domain\Core\Models\Sucursal;
use App\Domain\Ventas\Models\Venta;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CajaManager
{
    public function __construct(
        protected AdminSettingsManager $settingsManager,
    ) {
    }

    public function userCanUsePos(User $user): bool
    {
        $hasExplicitAccessModel = $user->roles()->exists() || $user->permissions()->exists();

        if (! $hasExplicitAccessModel) {
            return true;
        }

        return $user->can('ventas.usar_caja_pos');
    }

    public function resolveSucursalForUser(User $user): Sucursal
    {
        if (! $this->userCanUsePos($user)) {
            throw new DomainException(
                'No tenes permisos para operar Caja POS. Asignalo desde Admin > Usuarios y roles.',
            );
        }

        $user->loadMissing('panelProfile.sucursal');
        $branch = $user->panelProfile?->sucursal;

        if ($branch?->activa) {
            return $branch;
        }

        $fallback = Sucursal::query()
            ->where('activa', true)
            ->orderBy('id')
            ->first();

        if ($fallback) {
            return $fallback;
        }

        throw new DomainException('No hay una sucursal activa disponible para operar la caja.');
    }

    public function activeSession(Sucursal $branch, bool $forUpdate = false): ?CajaSesion
    {
        $query = CajaSesion::query()
            ->with('cajeroApertura')
            ->where('sucursal_id', $branch->id)
            ->whereNull('cerrada_en');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function assertOperable(User $user, ?Sucursal $branch = null, bool $forUpdate = false): CajaSesion
    {
        $branch ??= $this->resolveSucursalForUser($user);

        if (! $branch->activa) {
            throw new DomainException("La sucursal {$branch->nombre} esta inactiva.");
        }

        $session = $this->activeSession($branch, $forUpdate);

        if (! $session) {
            throw new DomainException("La caja de {$branch->nombre} esta cerrada. Abrila para poder vender.");
        }

        if ($session->cajero_apertura_id !== $user->id) {
            $cashierName = $session->cajeroApertura?->nombre_completo ?? $session->cajeroApertura?->username ?? 'otro usuario';

            throw new DomainException(
                "La caja de {$branch->nombre} esta abierta por {$cashierName}. Solo ese cajero puede operar hasta cerrarla.",
            );
        }

        return $session;
    }

    public function buildState(User $user, Sucursal $branch): array
    {
        $session = $this->activeSession($branch);

        if (! $session) {
            return [
                'session' => null,
                'is_open' => false,
                'can_sell' => false,
                'opened_by_other' => false,
                'message' => 'Caja cerrada. Abri la caja para habilitar ventas.',
            ];
        }

        $isCurrentCashier = $session->cajero_apertura_id === $user->id;
        $cashierName = $session->cajeroApertura?->nombre_completo ?? $session->cajeroApertura?->username ?? 'otro usuario';

        return [
            'session' => $session,
            'is_open' => true,
            'can_sell' => $isCurrentCashier,
            'opened_by_other' => ! $isCurrentCashier,
            'message' => $isCurrentCashier
                ? "Caja abierta por vos desde {$session->abierta_en?->format('d/m/Y H:i')}."
                : "Caja abierta por {$cashierName} desde {$session->abierta_en?->format('d/m/Y H:i')}.",
        ];
    }

    public function open(User $user, Sucursal $branch): CajaSesion
    {
        return DB::transaction(function () use ($user, $branch): CajaSesion {
            Sucursal::query()
                ->whereKey($branch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $active = $this->activeSession($branch, true);

            if ($active && $active->cajero_apertura_id !== $user->id) {
                $cashierName = $active->cajeroApertura?->nombre_completo ?? $active->cajeroApertura?->username ?? 'otro usuario';

                throw new DomainException("La caja de {$branch->nombre} ya esta abierta por {$cashierName}.");
            }

            if ($active) {
                return $active;
            }

            return CajaSesion::query()->create([
                'sucursal_id' => $branch->id,
                'cajero_apertura_id' => $user->id,
                'abierta_en' => now(),
            ]);
        });
    }

    public function close(User $user, Sucursal $branch): array
    {
        return DB::transaction(function () use ($user, $branch): array {
            $session = $this->assertOperable($user, $branch, true);
            $session->cerrar($user);
            $session->save();

            $summary = Venta::query()
                ->where('caja_sesion_id', $session->id)
                ->where('estado', Venta::ESTADO_CONFIRMADA)
                ->selectRaw('COUNT(*) as cantidad')
                ->selectRaw('COALESCE(SUM(total), 0) as total')
                ->first();

            return [
                'session' => $session,
                'summary' => [
                    'cantidad' => (int) ($summary?->cantidad ?? 0),
                    'total' => number_format((float) ($summary?->total ?? 0), 2, '.', ''),
                ],
            ];
        });
    }

    public function allowSellWithoutStock(Sucursal $branch): bool
    {
        return $this->settingsManager->salesFlagValue($branch, 'permitir_sin_stock');
    }

    public function allowChangePrice(Sucursal $branch): bool
    {
        return $this->settingsManager->salesFlagValue($branch, 'permitir_cambiar_precio_venta');
    }
}
