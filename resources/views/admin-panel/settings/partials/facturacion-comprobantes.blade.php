@php
    $money = $money ?? fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
    $authorizedDocuments = $authorizedDocuments ?? null;
    $authorizedDocumentsFilters = $authorizedDocumentsFilters ?? ['q' => '', 'from' => '', 'to' => '', 'sucursal' => ''];
    $authorizedDocumentsSummary = $authorizedDocumentsSummary ?? [
        'count' => 0,
        'branches_count' => 0,
        'total_amount' => 0,
        'latest_issued_at' => null,
    ];
    $clearAuthorizedDocumentsRoute = route('admin-panel.settings.index', array_filter([
        'tab' => 'facturacion',
        'facturacion_tab' => 'comprobantes',
        'sucursal' => $selectedBranch?->id,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

<div class="card cfg-section">
    <div class="card-content">
        <div class="cfg-section-head">
            <div>
                <h4 class="cfg-section-title">Comprobantes autorizados con CAE</h4>
                <p class="cfg-section-subtitle">Listado operativo para revisar los comprobantes electrónicos ya autorizados por ARCA y acceder rápido a su impresión o a la venta relacionada.</p>
            </div>
            <span class="cfg-chip"><i class="material-icons">verified</i>Solo autorizados</span>
        </div>

        <div class="cfg-note">
            Se listan únicamente comprobantes con modo <b>ARCA electrónica</b>, estado <b>autorizado</b> y <b>CAE informado</b>.
        </div>

        <div class="cfg-result-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); margin-top:14px;">
            <div class="cfg-result-card">
                <h5>Comprobantes</h5>
                <p><strong>{{ number_format((int) ($authorizedDocumentsSummary['count'] ?? 0), 0, ',', '.') }}</strong> autorizados</p>
            </div>
            <div class="cfg-result-card">
                <h5>Importe total</h5>
                <p><strong>{{ $money($authorizedDocumentsSummary['total_amount'] ?? 0) }}</strong> emitido</p>
            </div>
            <div class="cfg-result-card">
                <h5>Sucursales</h5>
                <p><strong>{{ number_format((int) ($authorizedDocumentsSummary['branches_count'] ?? 0), 0, ',', '.') }}</strong> con comprobantes</p>
            </div>
            <div class="cfg-result-card">
                <h5>Última emisión</h5>
                <p><strong>{{ data_get($authorizedDocumentsSummary, 'latest_issued_at')?->format('d/m/Y H:i') ?: '-' }}</strong></p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin-panel.settings.index') }}" style="margin-top:14px;">
            <input type="hidden" name="tab" value="facturacion">
            <input type="hidden" name="facturacion_tab" value="comprobantes">
            @if ($selectedBranch)
                <input type="hidden" name="sucursal" value="{{ $selectedBranch->id }}">
            @endif

            <div class="row" style="margin-bottom:0;">
                <div class="input-field col s12 m3">
                    <select name="documentos_sucursal">
                        <option value="" @selected(($authorizedDocumentsFilters['sucursal'] ?? '') === '')>Todas</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($authorizedDocumentsFilters['sucursal'] ?? '') === (string) $branch->id)>{{ $branch->nombre }}</option>
                        @endforeach
                    </select>
                    <label>Sucursal del comprobante</label>
                </div>

                <div class="input-field col s12 m3">
                    <input type="date" name="documentos_from" value="{{ $authorizedDocumentsFilters['from'] ?? '' }}">
                    <label class="active">Desde emisión</label>
                </div>

                <div class="input-field col s12 m3">
                    <input type="date" name="documentos_to" value="{{ $authorizedDocumentsFilters['to'] ?? '' }}">
                    <label class="active">Hasta emisión</label>
                </div>

                <div class="input-field col s12 m3">
                    <input type="text" name="documentos_q" value="{{ $authorizedDocumentsFilters['q'] ?? '' }}">
                    <label class="active">Buscar</label>
                </div>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <button class="btn waves-effect waves-light" type="submit">
                    <i class="material-icons left">search</i>Aplicar filtros
                </button>
                <a class="btn-flat waves-effect" href="{{ $clearAuthorizedDocumentsRoute }}">Limpiar</a>
            </div>
        </form>

        <div class="responsive-table" style="margin-top:14px;">
            <table class="striped responsive-stack-table">
                <thead>
                    <tr>
                        <th>Emisión</th>
                        <th>Sucursal</th>
                        <th>Comprobante</th>
                        <th>Número</th>
                        <th>CAE</th>
                        <th>Vto. CAE</th>
                        <th>Receptor</th>
                        <th class="right-align">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($authorizedDocuments ?? [] as $document)
                        <tr>
                            <td data-label="Emisión">{{ $document->fecha_emision?->format('d/m/Y H:i') ?: '-' }}</td>
                            <td data-label="Sucursal">{{ $document->sucursal?->nombre ?: '-' }}</td>
                            <td data-label="Comprobante">
                                {{ $document->descripcion_completa }}
                                <div class="grey-text text-darken-1" style="font-size:12px;">Cod. {{ str_pad((string) ((int) ($document->codigo_arca ?? 0)), 3, '0', STR_PAD_LEFT) }}</div>
                            </td>
                            <td data-label="Número" class="cfg-mono">{{ $document->numero_completo ?: '-' }}</td>
                            <td data-label="CAE" class="cfg-mono">{{ $document->cae ?: '-' }}</td>
                            <td data-label="Vto. CAE">{{ $document->cae_vto?->format('d/m/Y') ?: '-' }}</td>
                            <td data-label="Receptor">
                                {{ $document->receptor_nombre ?: 'Consumidor Final' }}
                                <div class="grey-text text-darken-1" style="font-size:12px;">
                                    {{ $document->doc_nro_receptor ?: 'Sin documento' }}
                                </div>
                            </td>
                            <td data-label="Total" class="right-align">{{ $money($document->importe_total) }}</td>
                            <td data-label="Acciones" class="right-align">
                                <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">
                                    @if ($document->venta)
                                        <a class="btn-small grey lighten-2 black-text" href="{{ route('admin-panel.ventas.show', $document->venta) }}">Venta</a>
                                    @endif
                                    <a class="btn-small blue-grey darken-2" href="{{ route('fiscal.comprobantes.show', $document) }}?print=1" target="_blank" rel="noopener">Fiscal</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="grey-text">No hay comprobantes autorizados con los filtros actuales.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($authorizedDocuments && $authorizedDocuments->lastPage() > 1)
            <div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div class="grey-text">
                    Página {{ $authorizedDocuments->currentPage() }} de {{ $authorizedDocuments->lastPage() }}
                </div>

                <ul class="pagination" style="margin:0;">
                    @if ($authorizedDocuments->onFirstPage())
                        <li class="disabled"><a href="#!"><i class="material-icons">chevron_left</i></a></li>
                    @else
                        <li class="waves-effect">
                            <a href="{{ $authorizedDocuments->previousPageUrl() }}">
                                <i class="material-icons">chevron_left</i>
                            </a>
                        </li>
                    @endif

                    <li class="active"><a href="#!">{{ $authorizedDocuments->currentPage() }}</a></li>

                    @if ($authorizedDocuments->hasMorePages())
                        <li class="waves-effect">
                            <a href="{{ $authorizedDocuments->nextPageUrl() }}">
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
