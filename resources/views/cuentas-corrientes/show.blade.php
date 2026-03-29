@extends('layouts.admin-panel')

@section('title', 'Cuenta corriente')
@section('header_title', 'Cuenta corriente')

@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
    $tipoDisplay = fn ($tipo) => $tipo === \App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente::TIPO_DEBITO ? 'Debito' : 'Credito';
@endphp

@push('styles')
    <style>
        .cc-overdue-panel {
            margin-top: 14px;
            background: var(--ui-danger-bg);
            border-color: var(--ui-danger-border);
            color: var(--ui-danger-text);
        }

        .cc-overdue-panel strong {
            display: block;
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .cc-aplicaciones-line {
            margin-top: 4px;
        }
    </style>
@endpush

@section('content')
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px;">
        <a class="btn-flat" href="{{ route('cuentas-corrientes.index') }}">
            <i class="material-icons left">arrow_back</i>Volver
        </a>
        <a class="btn-flat" href="{{ route('cuentas-corrientes.payments.create', $cuenta) }}">
            <i class="material-icons left">payments</i>Registrar pago
        </a>
    </div>

    <div class="row">
        <div class="col s12 l5">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Cliente</span>
                    <p><b>DNI:</b> {{ $cliente->dni }}</p>
                    <p><b>Nombre:</b> {{ $cliente->apellido }}, {{ $cliente->nombre }}</p>
                    <p><b>Telefono:</b> {{ $cliente->telefono ?: '-' }}</p>
                    <p><b>Direccion:</b> {{ $cliente->direccion ?: '-' }}</p>

                    <p style="margin-top:12px;">
                        <b>Cuenta activa:</b> {{ $cuenta->activa ? 'Si' : 'No' }}
                    </p>

                    <p>
                        <b>Saldo:</b>
                        <span style="font-size:1.2rem; font-weight:700;">
                            {{ $money($saldo) }}
                        </span>
                    </p>

                    <div class="card-panel" style="margin-top:14px; margin-bottom:0;">
                        <b>Ventas pendientes:</b> {{ $ventasPendientesCount }}
                    </div>

                    @if (($alertaVencidas['count'] ?? 0) > 0)
                        <div class="card-panel cc-overdue-panel">
                            <strong>Alerta de mora +30 dias</strong>
                            {{ $alertaVencidas['count'] }} venta(s) siguen pendientes por {{ $money($alertaVencidas['total']) }}.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('cuentas-corrientes.toggle', $cuenta) }}" style="margin-top:14px;">
                        @csrf
                        @method('PATCH')
                        @if ($cuenta->activa)
                            <button class="btn red" type="submit">
                                <i class="material-icons left">pause</i>Pausar CC
                            </button>
                        @else
                            <button class="btn green" type="submit">
                                <i class="material-icons left">play_arrow</i>Reactivar CC
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col s12 l7">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Movimientos (ultimos 200)</span>

                    <div class="responsive-table">
                        <table class="striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Venta</th>
                                    <th>Ref</th>
                                    <th class="right-align">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movimientos as $movimiento)
                                    <tr>
                                        <td>{{ $movimiento->fecha?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td>{{ $tipoDisplay($movimiento->tipo) }}</td>
                                        <td>{{ $movimiento->venta?->codigo_sucursal ?? '-' }}</td>
                                        <td>{{ $movimiento->referencia ?: '-' }}</td>
                                        <td class="right-align">
                                            {{ $movimiento->tipo === \App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente::TIPO_DEBITO ? '+' : '-' }}
                                            {{ $money($movimiento->monto) }}
                                        </td>
                                    </tr>
                                    @if ($movimiento->observacion)
                                        <tr>
                                            <td colspan="5" class="grey-text" style="font-size:.9rem;">
                                                {{ $movimiento->observacion }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($movimiento->pagoCuentaCorriente?->aplicaciones?->isNotEmpty())
                                        <tr>
                                            <td colspan="5" class="grey-text cc-aplicaciones-line" style="font-size:.9rem;">
                                                Aplicado a:
                                                @foreach ($movimiento->pagoCuentaCorriente->aplicaciones as $aplicacion)
                                                    {{ $aplicacion->movimientoDebito?->venta?->codigo_sucursal ?? ('#' . $aplicacion->movimientoDebito?->venta_id) }}
                                                    ({{ $money($aplicacion->monto_aplicado) }})@if (! $loop->last) · @endif
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="5" class="grey-text">Sin movimientos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
