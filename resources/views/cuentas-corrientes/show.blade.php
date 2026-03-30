@extends('layouts.admin-panel')

@section('title', 'Cuenta corriente')
@section('header_title', 'Cuenta corriente')

@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
    $tipoDisplay = fn ($tipo) => $tipo === \App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente::TIPO_DEBITO ? 'Debito' : 'Credito';
@endphp

@push('styles')
    <style>
        .cc-detail-page {
            display: grid;
            gap: 16px;
        }

        .cc-detail-actions-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cc-detail-layout {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(320px, 0.92fr) minmax(0, 1.08fr);
        }

        .cc-detail-card .card-content,
        .cc-movements-card .card-content {
            display: grid;
            gap: 14px;
        }

        .cc-facts-grid,
        .cc-kpi-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .cc-fact,
        .cc-kpi {
            padding: 12px 14px;
            border: 1px solid var(--ui-border);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(243, 248, 252, 0.96) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .cc-fact span,
        .cc-kpi span {
            display: block;
            margin-bottom: 4px;
            color: var(--ui-text-soft);
            font-size: 0.73rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .cc-fact strong,
        .cc-kpi strong {
            display: block;
            color: var(--ui-text);
            font-size: 0.96rem;
            line-height: 1.35;
            font-weight: 800;
            word-break: break-word;
        }

        .cc-kpi strong {
            font-size: 1.15rem;
        }

        .cc-detail-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .cc-overdue-panel {
            margin-top: 0;
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

        @media (max-width: 992px) {
            .cc-detail-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .cc-facts-grid,
            .cc-kpi-grid {
                grid-template-columns: 1fr;
            }

            .cc-detail-actions-bar .btn-flat,
            .cc-detail-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="cc-detail-page">
        <div class="cc-detail-actions-bar">
            <a class="btn-flat" href="{{ route('cuentas-corrientes.index') }}">
                <i class="material-icons left">arrow_back</i>Volver
            </a>
            <a class="btn-flat" href="{{ route('cuentas-corrientes.payments.create', $cuenta) }}">
                <i class="material-icons left">payments</i>Registrar pago
            </a>
        </div>

        <div class="cc-detail-layout">
            <div class="card cc-detail-card">
                <div class="card-content">
                    <span class="card-title">Cliente</span>

                    <div class="cc-facts-grid">
                        <div class="cc-fact">
                            <span>DNI</span>
                            <strong>{{ $cliente->dni }}</strong>
                        </div>
                        <div class="cc-fact">
                            <span>Nombre</span>
                            <strong>{{ $cliente->apellido }}, {{ $cliente->nombre }}</strong>
                        </div>
                        <div class="cc-fact">
                            <span>Telefono</span>
                            <strong>{{ $cliente->telefono ?: '-' }}</strong>
                        </div>
                        <div class="cc-fact">
                            <span>Direccion</span>
                            <strong>{{ $cliente->direccion ?: '-' }}</strong>
                        </div>
                    </div>

                    <div class="cc-kpi-grid">
                        <div class="cc-kpi">
                            <span>Cuenta activa</span>
                            <strong>{{ $cuenta->activa ? 'Si' : 'No' }}</strong>
                        </div>
                        <div class="cc-kpi">
                            <span>Saldo</span>
                            <strong>{{ $money($saldo) }}</strong>
                        </div>
                        <div class="cc-kpi">
                            <span>Ventas pendientes</span>
                            <strong>{{ $ventasPendientesCount }}</strong>
                        </div>
                    </div>

                    @if (($alertaVencidas['count'] ?? 0) > 0)
                        <div class="card-panel cc-overdue-panel">
                            <strong>Alerta de mora +30 dias</strong>
                            {{ $alertaVencidas['count'] }} venta(s) siguen pendientes por {{ $money($alertaVencidas['total']) }}.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('cuentas-corrientes.toggle', $cuenta) }}" class="cc-detail-actions">
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

            <div class="card cc-movements-card">
                <div class="card-content">
                    <span class="card-title">Movimientos (ultimos 200)</span>

                    <div class="responsive-table">
                        <table class="striped responsive-stack-table">
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
                                        <td data-label="Fecha">{{ $movimiento->fecha?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td data-label="Tipo">{{ $tipoDisplay($movimiento->tipo) }}</td>
                                        <td data-label="Venta">{{ $movimiento->venta?->codigo_sucursal ?? '-' }}</td>
                                        <td data-label="Ref">{{ $movimiento->referencia ?: '-' }}</td>
                                        <td data-label="Monto" class="right-align">
                                            {{ $movimiento->tipo === \App\Domain\CuentasCorrientes\Models\MovimientoCuentaCorriente::TIPO_DEBITO ? '+' : '-' }}
                                            {{ $money($movimiento->monto) }}
                                        </td>
                                    </tr>
                                    @if ($movimiento->observacion)
                                        <tr class="responsive-stack-note-row">
                                            <td colspan="5" class="grey-text" style="font-size:.9rem;">
                                                {{ $movimiento->observacion }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($movimiento->pagoCuentaCorriente?->aplicaciones?->isNotEmpty())
                                        <tr class="responsive-stack-note-row">
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
