@extends('layouts.admin-panel')

@section('title', $venta->codigo_sucursal)
@section('header_title', 'Detalle de venta ' . $venta->codigo_sucursal)

@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
@endphp

@section('content')
    <div style="margin-bottom:12px;">
        <a class="btn-flat" href="{{ route('admin-panel.ventas.index') }}">
            <i class="material-icons left">arrow_back</i>Volver
        </a>
        @if ($venta->estado === \App\Domain\Ventas\Models\Venta::ESTADO_CONFIRMADA)
            <a class="btn green darken-1" href="{{ route('caja.ticket', $venta) }}?print=1" target="_blank" rel="noopener" style="margin-left:8px;">
                <i class="material-icons left">print</i>Reimprimir ticket
            </a>
        @endif
    </div>

    <div class="row">
        <div class="col s12 l5">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Resumen</span>
                    <p><b>Fecha:</b> {{ $venta->fecha?->format('d/m/Y H:i') ?? '-' }}</p>
                    <p><b>Código:</b> {{ $venta->codigo_sucursal }}</p>
                    <p><b>Sucursal:</b> {{ $venta->sucursal?->nombre ?? '-' }}</p>
                    <p><b>Estado:</b> {{ ucfirst(strtolower($venta->estado)) }}</p>
                    <p><b>Medios de pago:</b> {{ $medio_pago_ui }}</p>
                    @if ($venta->cliente)
                        <p><b>Cliente:</b> {{ $venta->cliente->nombre_completo ?? 'Consumidor final' }}</p>
                    @endif
                    <p><b>Total ítems:</b> {{ $money($total_items) }}</p>
                    <p><b>Recargos:</b> {{ $money($total_recargos) }}</p>
                    <p><b>Total final:</b> {{ $money($venta->total) }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <span class="card-title">Pagos</span>

                    <div class="responsive-table">
                        <table class="striped responsive-stack-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Detalle</th>
                                    <th class="right-align">Base</th>
                                    <th class="right-align">Recargo</th>
                                    <th class="right-align">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pagos as $pago)
                                    <tr>
                                        <td data-label="Tipo"><strong>{{ $pago->tipo ?: '—' }}</strong></td>
                                        <td data-label="Detalle">
                                            @if ($pago->tipo === \App\Domain\Ventas\Models\VentaPago::TIPO_CREDITO)
                                                <div class="grey-text" style="font-size:.85rem;">
                                                    @if ($pago->plan?->tarjeta)
                                                        {{ $pago->plan->tarjeta }} ·
                                                    @endif
                                                    {{ $pago->cuotas }} cuotas
                                                    @if ((float) ($pago->recargo_pct ?? 0) > 0)
                                                        · Recargo {{ number_format((float) $pago->recargo_pct, 2, ',', '.') }}%
                                                    @endif
                                                </div>
                                            @elseif ($pago->referencia)
                                                <div class="grey-text" style="font-size:.85rem;">Ref: {{ $pago->referencia }}</div>
                                            @else
                                                <span class="grey-text">—</span>
                                            @endif
                                        </td>
                                        <td data-label="Base" class="right-align">{{ $money($pago->monto) }}</td>
                                        <td data-label="Recargo" class="right-align">{{ $money($pago->recargo_monto_safe) }}</td>
                                        <td data-label="Total" class="right-align"><strong>{{ $money($pago->total_pago_admin) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="grey-text">Sin pagos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($pagos->isNotEmpty())
                        <div class="right-align" style="margin-top:10px;">
                            <span class="grey-text">Total pagado:</span>
                            <strong>{{ $money($total_pagado) }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col s12 l7">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Ítems</span>

                    <div class="responsive-table">
                        <table class="striped responsive-stack-table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Producto</th>
                                    <th class="right-align">Cant.</th>
                                    <th class="right-align">Precio</th>
                                    <th class="right-align">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td data-label="SKU">{{ $item->variante?->sku ?? '-' }}</td>
                                        <td data-label="Producto">{{ $item->nombre_admin }}</td>
                                        <td data-label="Cant." class="right-align">{{ $item->cantidad }}</td>
                                        <td data-label="Precio" class="right-align">{{ $money($item->precio_unitario) }}</td>
                                        <td data-label="Subtotal" class="right-align">{{ $money($item->subtotal) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="grey-text">Sin ítems.</td>
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
