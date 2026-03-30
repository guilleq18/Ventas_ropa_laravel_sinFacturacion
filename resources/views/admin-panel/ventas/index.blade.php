@extends('layouts.admin-panel')

@section('title', 'Ventas')
@section('header_title', 'Ventas realizadas')

@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
@endphp

@section('content')
    <div class="card">
        <div class="card-content">
            <span class="card-title">Filtros</span>

            <form method="GET" action="{{ route('admin-panel.ventas.index') }}">
                <div class="row" style="margin-bottom:0;">
                    <div class="input-field col s12 m3">
                        <input type="date" name="from" value="{{ $filters['from'] }}">
                        <label class="active">Desde</label>
                    </div>

                    <div class="input-field col s12 m3">
                        <input type="date" name="to" value="{{ $filters['to'] }}">
                        <label class="active">Hasta</label>
                    </div>

                    <div class="input-field col s12 m3">
                        <select name="sucursal">
                            <option value="" @selected($filters['sucursal'] === '')>Todas</option>
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}" @selected((string) $filters['sucursal'] === (string) $sucursal->id)>{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                        <label>Sucursal</label>
                    </div>

                    <div class="input-field col s12 m3">
                        <select name="estado">
                            <option value="" @selected($filters['estado'] === '')>Todos</option>
                            @foreach ($estados as $value => $label)
                                <option value="{{ $value }}" @selected((string) $filters['estado'] === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <label>Estado</label>
                    </div>

                    <div class="input-field col s12">
                        <input type="text" name="q" value="{{ $filters['q'] }}">
                        <label class="active">Buscar (ID / código / sucursal)</label>
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <button class="btn" type="submit">
                        <i class="material-icons left">search</i>Aplicar
                    </button>
                    <a class="btn-flat" href="{{ route('admin-panel.ventas.index') }}">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-content">
            <span class="card-title">Resultados</span>

            <div class="responsive-table">
                <table class="striped responsive-stack-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Sucursal</th>
                            <th>Estado</th>
                            <th>Fiscal</th>
                            <th>Estado fiscal</th>
                            <th>Medio</th>
                            <th class="right-align">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $venta)
                            <tr>
                                <td data-label="Código">{{ $venta->codigo_sucursal }}</td>
                                <td data-label="Fecha">{{ $venta->fecha?->format('d/m/Y') ?? '-' }}</td>
                                <td data-label="Sucursal">{{ $venta->sucursal?->nombre ?? '-' }}</td>
                                <td data-label="Estado">{{ $estados[$venta->estado] ?? $venta->estado }}</td>
                                <td data-label="Fiscal">{{ $venta->accion_fiscal_label }}</td>
                                <td data-label="Estado fiscal">{{ $venta->estado_fiscal_label }}</td>
                                <td data-label="Medio">{{ $venta->medio_pago_ui }}</td>
                                <td data-label="Total" class="right-align">{{ $money($venta->total) }}</td>
                                <td data-label="Acciones" class="right-align">
                                    <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">
                                        <a class="btn-small" href="{{ route('admin-panel.ventas.show', $venta) }}">Ver</a>
                                        @if ($venta->estado === \App\Domain\Ventas\Models\Venta::ESTADO_CONFIRMADA)
                                            <a class="btn-small grey lighten-1 black-text" href="{{ route('caja.ticket', $venta) }}?print=1" target="_blank" rel="noopener">Ticket</a>
                                        @endif
                                        @if ($venta->comprobantePrincipal?->es_imprimible)
                                            <a class="btn-small blue-grey darken-2" href="{{ route('fiscal.comprobantes.show', $venta->comprobantePrincipal) }}?print=1" target="_blank" rel="noopener">Fiscal</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="grey-text">Sin resultados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sales->lastPage() > 1)
                <div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <div class="grey-text">
                        Página {{ $sales->currentPage() }} de {{ $sales->lastPage() }}
                    </div>

                    <ul class="pagination" style="margin:0;">
                        @if ($sales->onFirstPage())
                            <li class="disabled"><a href="#!"><i class="material-icons">chevron_left</i></a></li>
                        @else
                            <li class="waves-effect">
                                <a href="{{ $sales->previousPageUrl() }}">
                                    <i class="material-icons">chevron_left</i>
                                </a>
                            </li>
                        @endif

                        <li class="active"><a href="#!">{{ $sales->currentPage() }}</a></li>

                        @if ($sales->hasMorePages())
                            <li class="waves-effect">
                                <a href="{{ $sales->nextPageUrl() }}">
                                    <i class="material-icons">chevron_right</i>
                                </a>
                            </li>
                        @else
                            <li class="disabled"><a href="#!"><i class="material-icons">chevron_right</i></a></li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection
