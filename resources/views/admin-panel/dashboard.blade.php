@extends('layouts.admin-panel')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')

@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
@endphp

@push('styles')
    <style>
        .admin-dashboard-grid {
            display: grid;
            gap: 18px;
        }
        .admin-dashboard-kpis {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .admin-dashboard-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            flex-wrap: wrap;
        }
        .admin-dashboard-hero-copy {
            display: grid;
            gap: 8px;
            max-width: 720px;
        }
        .admin-dashboard-hero-title {
            margin: 0;
            font-size: 2rem;
            line-height: 1.02;
            font-weight: 900;
            color: #1f2937;
        }
        .admin-dashboard-hero-note {
            margin: 0;
            color: #667085;
            font-size: 0.96rem;
            line-height: 1.5;
        }
        .admin-dashboard-kpi {
            display: grid;
            gap: 8px;
        }
        .admin-dashboard-kpi-label {
            color: #667085;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .admin-dashboard-kpi-value {
            color: #1f2937;
            font-size: 1.8rem;
            line-height: 1;
            font-weight: 900;
        }
        .admin-dashboard-list {
            display: grid;
            gap: 12px;
        }
        .admin-dashboard-sale {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid var(--ui-border);
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
        }
        .admin-dashboard-sale-code {
            margin: 0;
            font-size: 0.96rem;
            font-weight: 900;
            color: #1f2937;
        }
        .admin-dashboard-sale-meta {
            margin: 4px 0 0;
            color: #667085;
            font-size: 0.86rem;
            line-height: 1.4;
        }
        .admin-dashboard-sale-total {
            font-size: 1rem;
            font-weight: 900;
            color: #182032;
            white-space: nowrap;
        }
        @media (max-width: 1100px) {
            .admin-dashboard-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 640px) {
            .admin-dashboard-kpis {
                grid-template-columns: 1fr;
            }
            .admin-dashboard-sale {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="admin-dashboard-grid">
        <div class="card">
            <div class="card-content admin-dashboard-hero">
                <div class="admin-dashboard-hero-copy">
                    <span class="admin-chip"><i class="material-icons">space_dashboard</i>Panel central</span>
                    <h2 class="admin-dashboard-hero-title">Bienvenido</h2>
                    <p class="admin-dashboard-hero-note">Desde acá vas a administrar catálogo, ventas y usuarios dentro del mismo lenguaje visual operativo del sistema.</p>
                </div>

                <div class="admin-chip"><i class="material-icons">query_stats</i>{{ $stats['ventas_confirmadas_total'] }} ventas confirmadas</div>
            </div>
        </div>

        <div class="admin-dashboard-kpis">
            <div class="card">
                <div class="card-content admin-dashboard-kpi">
                    <span class="admin-dashboard-kpi-label">Facturación del día</span>
                    <div class="admin-dashboard-kpi-value">{{ $money($stats['ventas_hoy_total']) }}</div>
                </div>
            </div>
            <div class="card">
                <div class="card-content admin-dashboard-kpi">
                    <span class="admin-dashboard-kpi-label">Ventas del día</span>
                    <div class="admin-dashboard-kpi-value">{{ $stats['ventas_hoy_cantidad'] }}</div>
                </div>
            </div>
            <div class="card">
                <div class="card-content admin-dashboard-kpi">
                    <span class="admin-dashboard-kpi-label">Productos activos</span>
                    <div class="admin-dashboard-kpi-value">{{ $stats['productos_activos'] }}</div>
                </div>
            </div>
            <div class="card">
                <div class="card-content admin-dashboard-kpi">
                    <span class="admin-dashboard-kpi-label">Usuarios activos</span>
                    <div class="admin-dashboard-kpi-value">{{ $stats['usuarios_activos'] }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-content">
                <span class="card-title">Ventas recientes</span>

                @if ($stats['ventas_recientes']->isNotEmpty())
                    <div class="admin-dashboard-list">
                        @foreach ($stats['ventas_recientes'] as $venta)
                            <div class="admin-dashboard-sale">
                                <div>
                                    <p class="admin-dashboard-sale-code">{{ $venta->codigo_sucursal }}</p>
                                    <p class="admin-dashboard-sale-meta">
                                        {{ $venta->sucursal?->nombre ?? 'Sin sucursal' }}
                                        ·
                                        {{ $venta->fecha?->format('d/m/Y H:i') ?? '-' }}
                                    </p>
                                </div>

                                <div class="admin-dashboard-sale-total">{{ $money($venta->total) }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="admin-empty-state">Todavía no hay ventas recientes para mostrar.</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-content">
                <span class="card-title">Estado operativo</span>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <span class="admin-chip"><i class="material-icons">inventory_2</i>{{ $stats['productos_activos'] }} productos</span>
                    <span class="admin-chip"><i class="material-icons">account_balance_wallet</i>{{ $stats['cuentas_activas'] }} cuentas corrientes activas</span>
                    <span class="admin-chip"><i class="material-icons">people</i>{{ $stats['usuarios_activos'] }} usuarios activos</span>
                </div>
            </div>
        </div>
    </div>
@endsection
