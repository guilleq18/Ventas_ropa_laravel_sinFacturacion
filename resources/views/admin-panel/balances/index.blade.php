@extends('layouts.admin-panel')

@section('title', 'Balances')
@section('header_title', 'Balances y gráficos')

@php
    $today = \Carbon\CarbonImmutable::today();
    $todayInput = $today->format('Y-m-d');
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
    $quickRanges = [
        '1m' => ['label' => '1 mes', 'from' => $today->subMonthNoOverflow()->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
        '3m' => ['label' => '3 meses', 'from' => $today->subMonthsNoOverflow(3)->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
        '6m' => ['label' => '6 meses', 'from' => $today->subMonthsNoOverflow(6)->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
        '1y' => ['label' => '1 año', 'from' => $today->subYearNoOverflow()->format('Y-m-d'), 'to' => $today->format('Y-m-d')],
    ];
    $hasVentasPorDia = count($report['labels_day']) > 0;
    $hasVentasPorSucursal = count($report['labels_branch']) > 0;
    $hasVentasPorHora = array_sum($report['data_hour_count']) > 0;
    $hasVentasPorCategoria = count($report['labels_category']) > 0;
    $hasVentasPorProducto = count($report['labels_product']) > 0;
    $hasVentasPorMedio = count($report['labels_payment']) > 0;
    $latestAvailableDateLabel = ! empty($report['latest_available_date'])
        ? \Carbon\CarbonImmutable::parse($report['latest_available_date'])->format('d/m/Y')
        : null;
@endphp

@section('content')
    <div class="card">
        <div class="card-content">
            <span class="card-title">Filtro</span>

            <form method="GET" action="{{ route('admin-panel.balances.index') }}">
                <input type="hidden" name="vista" value="{{ $report['view'] }}">
                <div class="row" style="margin-bottom:0;">
                    <div class="input-field col s12 m4">
                        <input type="date" name="from" value="{{ $report['from'] }}">
                        <label class="active">Desde</label>
                    </div>

                    <div class="input-field col s12 m4">
                        <input type="date" name="to" value="{{ $report['to'] }}">
                        <label class="active">Hasta</label>
                    </div>

                    <div class="input-field col s12 m4" style="margin-top:22px;">
                        <button class="btn" type="submit">
                            <i class="material-icons left">search</i>Aplicar
                        </button>
                        <a class="btn-flat" href="{{ route('admin-panel.balances.index', ['vista' => $report['view'], 'from' => $todayInput, 'to' => $todayInput]) }}">Hoy</a>
                    </div>
                </div>

                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px;">
                    <span class="grey-text text-darken-1" style="align-self:center; font-size:.9rem;">Rangos rápidos:</span>
                    @foreach ($quickRanges as $range)
                        <a class="btn-flat waves-effect" href="{{ route('admin-panel.balances.index', ['vista' => $report['view'], 'from' => $range['from'], 'to' => $range['to']]) }}">{{ $range['label'] }}</a>
                    @endforeach
                </div>

                @if ($report['defaulted_to_latest_available'] && $latestAvailableDateLabel)
                    <div class="card-panel blue lighten-5 blue-text text-darken-3" style="margin:16px 0 0;">
                        No habia ventas para hoy. Se muestran automaticamente los datos del ultimo dia con movimientos: <strong>{{ $latestAvailableDateLabel }}</strong>.
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-content" style="padding:10px 16px;">
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a class="btn {{ $report['view'] === 'ventas' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect"
                   href="{{ route('admin-panel.balances.index', ['vista' => 'ventas', 'from' => $report['from'], 'to' => $report['to']]) }}">
                    <i class="material-icons left">show_chart</i>Ventas
                </a>
                <a class="btn {{ $report['view'] === 'productos' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect"
                   href="{{ route('admin-panel.balances.index', ['vista' => 'productos', 'from' => $report['from'], 'to' => $report['to']]) }}">
                    <i class="material-icons left">inventory_2</i>Productos
                </a>
                <a class="btn {{ $report['view'] === 'pagos' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect"
                   href="{{ route('admin-panel.balances.index', ['vista' => 'pagos', 'from' => $report['from'], 'to' => $report['to']]) }}">
                    <i class="material-icons left">payments</i>Formas de pago
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col s12 m4">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Total</span>
                    <div style="font-size:1.6rem; font-weight:700;">{{ $money($report['total']) }}</div>
                </div>
            </div>
        </div>

        <div class="col s12 m4">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Ventas</span>
                    <div style="font-size:1.6rem; font-weight:700;">{{ $report['cantidad'] }}</div>
                </div>
            </div>
        </div>

        <div class="col s12 m4">
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Ticket promedio</span>
                    <div style="font-size:1.6rem; font-weight:700;">{{ $money($report['ticket_promedio']) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($report['view'] === 'ventas')
        <div class="row">
            <div class="col s12 l8">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Ventas por día</span>
                        @if ($hasVentasPorDia)
                            <canvas id="chartDia" height="96"></canvas>
                        @else
                            <div class="card-panel amber lighten-5 brown-text text-darken-3" style="margin:0;">
                                No hay ventas registradas para el rango seleccionado.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col s12 l4">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Ventas por sucursal</span>
                        @if ($hasVentasPorSucursal)
                            <canvas id="chartSucursal" height="160"></canvas>
                            <div id="sucursalResumen" style="margin-top:12px;"></div>
                        @else
                            <div class="card-panel amber lighten-5 brown-text text-darken-3" style="margin:0;">
                                No hay ventas por sucursal para el rango seleccionado.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Ventas por hora del día</span>
                        @if ($hasVentasPorHora)
                            <canvas id="chartHora" height="72"></canvas>
                        @else
                            <div class="card-panel amber lighten-5 brown-text text-darken-3" style="margin:0;">
                                No hay ventas registradas para distribuir por hora en este rango.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif ($report['view'] === 'productos')
        <div class="row">
            <div class="col s12 l6">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Ventas por categoría</span>
                        @if ($hasVentasPorCategoria)
                            <canvas id="chartCategoria" height="115"></canvas>
                            <div id="categoriaResumen" style="margin-top:12px;"></div>
                        @else
                            <div class="card-panel amber lighten-5 brown-text text-darken-3" style="margin:0;">
                                No hay productos vendidos para agrupar por categoria en este rango.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col s12 l6">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Ventas por producto (Top)</span>
                        @if ($hasVentasPorProducto)
                            <canvas id="chartProducto" height="115"></canvas>
                            <div id="productoResumen" style="margin-top:12px;"></div>
                        @else
                            <div class="card-panel amber lighten-5 brown-text text-darken-3" style="margin:0;">
                                No hay productos vendidos para armar el ranking en este rango.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif ($report['view'] === 'pagos')
        <div class="row">
            <div class="col s12 l6">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Por forma de pago</span>
                        @if ($hasVentasPorMedio)
                            <canvas id="chartMedio" height="176"></canvas>
                            <div id="medioResumen" style="margin-top:12px;"></div>
                        @else
                            <div class="card-panel amber lighten-5 brown-text text-darken-3" style="margin:0;">
                                No hay pagos registrados para desglosar en este rango.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const labelsDia = @json($report['labels_day']);
        const dataTotalDia = @json($report['data_day_total']);
        const dataCantidadDia = @json($report['data_day_count']);
        const labelsHora = @json($report['labels_hour']);
        const dataHora = @json($report['data_hour_total']);
        const dataHoraCantidad = @json($report['data_hour_count']);

        const labelsMedio = @json($report['labels_payment']);
        const dataMedio = @json($report['data_payment_total']);
        const dataMedioCantidadVentas = @json($report['data_payment_count']);

        const labelsSucursal = @json($report['labels_branch']);
        const dataSucursal = @json($report['data_branch_total']);
        const dataSucursalCantidad = @json($report['data_branch_count']);

        const labelsCategoria = @json($report['labels_category']);
        const dataCategoria = @json($report['data_category_total']);
        const dataCategoriaCantidad = @json($report['data_category_count']);

        const labelsProducto = @json($report['labels_product']);
        const dataProducto = @json($report['data_product_total']);
        const dataProductoCantidad = @json($report['data_product_count']);

        function fmtMoney(value) {
            try {
                return new Intl.NumberFormat('es-AR', {
                    style: 'currency',
                    currency: 'ARS',
                    maximumFractionDigits: 2
                }).format(Number(value || 0));
            } catch (error) {
                return '$' + Number(value || 0).toFixed(2);
            }
        }

        function renderResumenList(containerId, labels, montos, cantidades, qtyLabel) {
            const container = document.getElementById(containerId);

            if (!container) {
                return;
            }

            if (!labels || !labels.length) {
                container.innerHTML = '<p class="grey-text" style="margin:0;">Sin datos para el rango seleccionado.</p>';
                return;
            }

            const rows = labels.map((label, index) => (
                `<tr>
                    <td style="padding:6px 8px;">${label}</td>
                    <td class="right-align" style="padding:6px 8px;">${fmtMoney(montos[index])}</td>
                    <td class="right-align" style="padding:6px 8px;">${Number(cantidades[index] || 0)}</td>
                </tr>`
            )).join('');

            container.innerHTML = `
                <table class="striped responsive-table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th class="right-align">Monto</th>
                            <th class="right-align">${qtyLabel}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        function canRenderChart(canvasEl) {
            if (!canvasEl) {
                return false;
            }

            if (typeof window.Chart === 'function') {
                return true;
            }

            const fallback = document.createElement('div');
            fallback.className = 'card-panel red lighten-5 red-text text-darken-3';
            fallback.style.margin = '0';
            fallback.textContent = 'No se pudo cargar la libreria de graficos en el navegador.';
            canvasEl.replaceWith(fallback);

            return false;
        }

        const chartDiaEl = document.getElementById('chartDia');
        if (canRenderChart(chartDiaEl)) {
            new Chart(chartDiaEl, {
                type: 'line',
                data: {
                    labels: labelsDia,
                    datasets: [
                        { label: 'Monto', data: dataTotalDia, borderColor: '#1e88e5', backgroundColor: 'rgba(30,136,229,.15)', tension: .25, fill: true, yAxisID: 'y' },
                        { label: 'Cant. ventas', data: dataCantidadDia, borderColor: '#43a047', backgroundColor: 'rgba(67,160,71,.15)', tension: .25, fill: false, yAxisID: 'y1' }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, position: 'left' },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
                    }
                }
            });
        }

        renderResumenList('sucursalResumen', labelsSucursal, dataSucursal, dataSucursalCantidad, 'Cant. ventas');
        const chartSucursalEl = document.getElementById('chartSucursal');
        if (canRenderChart(chartSucursalEl)) {
            new Chart(chartSucursalEl, {
                type: 'bar',
                data: {
                    labels: labelsSucursal,
                    datasets: [{ label: 'Monto', data: dataSucursal, backgroundColor: '#26a69a' }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        const chartHoraEl = document.getElementById('chartHora');
        if (canRenderChart(chartHoraEl)) {
            new Chart(chartHoraEl, {
                type: 'bar',
                data: {
                    labels: labelsHora,
                    datasets: [
                        { label: 'Monto', data: dataHora, backgroundColor: '#5c6bc0', yAxisID: 'y' },
                        { label: 'Cant. ventas', data: dataHoraCantidad, backgroundColor: '#c5cae9', yAxisID: 'y1' }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, position: 'left' },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
                    }
                }
            });
        }

        renderResumenList('categoriaResumen', labelsCategoria, dataCategoria, dataCategoriaCantidad, 'Cant. vendida');
        const chartCategoriaEl = document.getElementById('chartCategoria');
        if (canRenderChart(chartCategoriaEl)) {
            new Chart(chartCategoriaEl, {
                type: 'doughnut',
                data: {
                    labels: labelsCategoria,
                    datasets: [{ label: 'Monto', data: dataCategoria, backgroundColor: '#26a69a' }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: true, position: 'bottom' } }
                }
            });
        }

        renderResumenList('productoResumen', labelsProducto, dataProducto, dataProductoCantidad, 'Cant. vendida');
        const chartProductoEl = document.getElementById('chartProducto');
        if (canRenderChart(chartProductoEl)) {
            new Chart(chartProductoEl, {
                type: 'bar',
                data: {
                    labels: labelsProducto,
                    datasets: [{ label: 'Monto', data: dataProducto, backgroundColor: '#66bb6a' }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } }
                }
            });
        }

        renderResumenList('medioResumen', labelsMedio, dataMedio, dataMedioCantidadVentas, 'Cant. ventas');
        const chartMedioEl = document.getElementById('chartMedio');
        if (canRenderChart(chartMedioEl)) {
            new Chart(chartMedioEl, {
                type: 'bar',
                data: {
                    labels: labelsMedio,
                    datasets: [{ label: 'Monto vendido', data: dataMedio, backgroundColor: '#42a5f5' }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    </script>
@endpush
