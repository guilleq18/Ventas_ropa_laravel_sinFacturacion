@php
    $caeaPeriods = $caeaPeriods ?? null;
    $caeaPeriodsFilters = $caeaPeriodsFilters ?? [
        'entorno' => '',
        'estado_solicitud' => '',
        'estado_informacion' => '',
        'cuit' => '',
        'q' => '',
    ];
    $caeaPeriodsSummary = $caeaPeriodsSummary ?? [
        'count' => 0,
        'authorized_count' => 0,
        'pending_information_count' => 0,
        'overdue_count' => 0,
        'latest_sync_at' => null,
    ];
    $caeaRequestStates = [
        \App\Domain\Fiscal\Models\ArcaCaeaPeriodo::ESTADO_SOLICITUD_AUTORIZADO => 'Autorizado',
        \App\Domain\Fiscal\Models\ArcaCaeaPeriodo::ESTADO_SOLICITUD_OBSERVADO => 'Observado',
        \App\Domain\Fiscal\Models\ArcaCaeaPeriodo::ESTADO_SOLICITUD_ERROR => 'Error',
    ];
    $caeaInformationStates = [
        \App\Domain\Fiscal\Models\ArcaCaeaPeriodo::ESTADO_INFORMACION_PENDIENTE => 'Pendiente',
        \App\Domain\Fiscal\Models\ArcaCaeaPeriodo::ESTADO_INFORMACION_PARCIAL => 'Parcial',
        \App\Domain\Fiscal\Models\ArcaCaeaPeriodo::ESTADO_INFORMACION_COMPLETA => 'Completa',
        \App\Domain\Fiscal\Models\ArcaCaeaPeriodo::ESTADO_INFORMACION_SIN_MOVIMIENTO => 'Sin movimiento',
        \App\Domain\Fiscal\Models\ArcaCaeaPeriodo::ESTADO_INFORMACION_VENCIDO => 'Vencido',
    ];
    $clearCaeaRoute = route('admin-panel.settings.index', array_filter([
        'tab' => 'facturacion',
        'facturacion_tab' => 'caea',
        'sucursal' => $selectedBranch?->id,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

<div class="card cfg-section">
    <div class="card-content">
        <div class="cfg-section-head">
            <div>
                <h4 class="cfg-section-title">Períodos CAEA autorizados e informables</h4>
                <p class="cfg-section-subtitle">Seguimiento operativo de los códigos CAEA por quincena, con vigencia, fecha tope de información y estado general de rendición.</p>
            </div>
            <span class="cfg-chip"><i class="material-icons">event_repeat</i>Por quincena</span>
        </div>

        <div class="cfg-note">
            Esta vista trabaja sobre períodos <b>CAEA</b> y ahora también muestra el detalle de rendición por comprobante dentro de cada período.
        </div>

        <div class="cfg-result-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-top:14px;">
            <div class="cfg-result-card">
                <h5>Períodos</h5>
                <p><strong>{{ number_format((int) ($caeaPeriodsSummary['count'] ?? 0), 0, ',', '.') }}</strong> cargados</p>
            </div>
            <div class="cfg-result-card">
                <h5>Autorizados</h5>
                <p><strong>{{ number_format((int) ($caeaPeriodsSummary['authorized_count'] ?? 0), 0, ',', '.') }}</strong> con CAEA</p>
            </div>
            <div class="cfg-result-card">
                <h5>Pendientes</h5>
                <p><strong>{{ number_format((int) ($caeaPeriodsSummary['pending_information_count'] ?? 0), 0, ',', '.') }}</strong> por informar</p>
            </div>
            <div class="cfg-result-card">
                <h5>Vencidos</h5>
                <p><strong>{{ number_format((int) ($caeaPeriodsSummary['overdue_count'] ?? 0), 0, ',', '.') }}</strong> fuera de plazo</p>
            </div>
            <div class="cfg-result-card">
                <h5>Último sync</h5>
                <p><strong>{{ data_get($caeaPeriodsSummary, 'latest_sync_at')?->format('d/m/Y H:i') ?: '-' }}</strong></p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin-panel.settings.index') }}" style="margin-top:14px;">
            <input type="hidden" name="tab" value="facturacion">
            <input type="hidden" name="facturacion_tab" value="caea">
            @if ($selectedBranch)
                <input type="hidden" name="sucursal" value="{{ $selectedBranch->id }}">
            @endif

            <div class="row" style="margin-bottom:0;">
                <div class="input-field col s12 m2">
                    <select name="caea_entorno">
                        <option value="" @selected(($caeaPeriodsFilters['entorno'] ?? '') === '')>Todos</option>
                        <option value="HOMOLOGACION" @selected(($caeaPeriodsFilters['entorno'] ?? '') === 'HOMOLOGACION')>Homologación</option>
                        <option value="PRODUCCION" @selected(($caeaPeriodsFilters['entorno'] ?? '') === 'PRODUCCION')>Producción</option>
                    </select>
                    <label>Entorno</label>
                </div>

                <div class="input-field col s12 m2">
                    <select name="caea_estado_solicitud">
                        <option value="" @selected(($caeaPeriodsFilters['estado_solicitud'] ?? '') === '')>Todos</option>
                        @foreach ($caeaRequestStates as $value => $label)
                            <option value="{{ $value }}" @selected(($caeaPeriodsFilters['estado_solicitud'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label>Solicitud</label>
                </div>

                <div class="input-field col s12 m3">
                    <select name="caea_estado_informacion">
                        <option value="" @selected(($caeaPeriodsFilters['estado_informacion'] ?? '') === '')>Todos</option>
                        @foreach ($caeaInformationStates as $value => $label)
                            <option value="{{ $value }}" @selected(($caeaPeriodsFilters['estado_informacion'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label>Información</label>
                </div>

                <div class="input-field col s12 m2">
                    <input type="text" name="caea_cuit" value="{{ $caeaPeriodsFilters['cuit'] ?? '' }}">
                    <label class="active">CUIT representada</label>
                </div>

                <div class="input-field col s12 m3">
                    <input type="text" name="caea_q" value="{{ $caeaPeriodsFilters['q'] ?? '' }}">
                    <label class="active">Buscar CAEA / período</label>
                </div>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <button class="btn waves-effect waves-light" type="submit">
                    <i class="material-icons left">search</i>Aplicar filtros
                </button>
                <a class="btn-flat waves-effect" href="{{ $clearCaeaRoute }}">Limpiar</a>
            </div>
        </form>

        <div class="responsive-table" style="margin-top:14px;">
            <table class="striped responsive-stack-table">
                <thead>
                    <tr>
                        <th>Entorno</th>
                        <th>CUIT</th>
                        <th>CAEA</th>
                        <th>Período</th>
                        <th>Quincena</th>
                        <th>Vigencia</th>
                        <th>Tope informar</th>
                        <th>Solicitud</th>
                        <th>Información</th>
                        <th class="right-align">Informados</th>
                        <th class="right-align">Pendientes</th>
                        <th>Sync</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($caeaPeriods ?? [] as $period)
                        @php($rendition = $period->resumen_rendicion)
                        <tr>
                            <td data-label="Entorno">{{ $period->entorno }}</td>
                            <td data-label="CUIT" class="cfg-mono">{{ $period->cuit_representada }}</td>
                            <td data-label="CAEA" class="cfg-mono">{{ $period->caea ?: '-' }}</td>
                            <td data-label="Período">{{ $period->periodo_label }}</td>
                            <td data-label="Quincena">{{ $period->orden_label }}</td>
                            <td data-label="Vigencia">{{ $period->rango_vigencia_label }}</td>
                            <td data-label="Tope informar">
                                {{ $period->fecha_tope_informar?->format('d/m/Y') ?: '-' }}
                                @if ($period->informacion_vencida)
                                    <div class="red-text text-darken-2" style="font-size:12px; font-weight:700;">Vencido</div>
                                @endif
                            </td>
                            <td data-label="Solicitud">{{ $period->estado_solicitud_label }}</td>
                            <td data-label="Información">{{ $period->estado_informacion_label }}</td>
                            <td data-label="Informados" class="right-align">{{ number_format((int) ($rendition['informados'] ?? 0), 0, ',', '.') }}</td>
                            <td data-label="Pendientes" class="right-align">{{ number_format((int) ($rendition['pendientes'] ?? 0), 0, ',', '.') }}</td>
                            <td data-label="Sync">{{ $period->ultimo_synced_at?->format('d/m/Y H:i') ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td colspan="12" style="background:rgba(248,250,252,.7); padding:14px 16px;">
                                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                                    <span class="cfg-chip"><i class="material-icons">receipt_long</i>Total {{ number_format((int) ($rendition['total'] ?? 0), 0, ',', '.') }}</span>
                                    <span class="cfg-chip"><i class="material-icons">check_circle</i>Informados {{ number_format((int) ($rendition['informados'] ?? 0), 0, ',', '.') }}</span>
                                    <span class="cfg-chip"><i class="material-icons">schedule</i>Pendientes {{ number_format((int) ($rendition['pendientes'] ?? 0), 0, ',', '.') }}</span>
                                    <span class="cfg-chip"><i class="material-icons">warning</i>Observados {{ number_format((int) ($rendition['observados'] ?? 0), 0, ',', '.') }}</span>
                                    <span class="cfg-chip"><i class="material-icons">cancel</i>Rechazados {{ number_format((int) ($rendition['rechazados'] ?? 0), 0, ',', '.') }}</span>
                                </div>

                                @if ($period->comprobantes->isEmpty())
                                    <div class="grey-text">Todavía no hay comprobantes asociados a este período CAEA.</div>
                                @else
                                    <div class="responsive-table">
                                        <table class="striped responsive-stack-table" style="margin:0;">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Sucursal</th>
                                                    <th>Comprobante</th>
                                                    <th>Receptor</th>
                                                    <th class="right-align">Total</th>
                                                    <th>Estado rendición</th>
                                                    <th>Informado en</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($period->comprobantes as $receipt)
                                                    <tr>
                                                        <td data-label="Fecha">{{ $receipt->fecha_emision?->format('d/m/Y') ?: '-' }}</td>
                                                        <td data-label="Sucursal">{{ $receipt->sucursal?->nombre ?: ($receipt->ventaComprobante?->sucursal?->nombre ?: '-') }}</td>
                                                        <td data-label="Comprobante">
                                                            <span class="cfg-mono">{{ $receipt->numero_completo ?: '-' }}</span>
                                                            @if ($receipt->codigo_arca)
                                                                <div class="grey-text text-darken-1" style="font-size:12px;">
                                                                    Cod. {{ str_pad((string) ((int) $receipt->codigo_arca), 3, '0', STR_PAD_LEFT) }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td data-label="Receptor">
                                                            {{ $receipt->receptor_nombre ?: 'Consumidor Final' }}
                                                            <div class="grey-text text-darken-1" style="font-size:12px;">
                                                                {{ $receipt->doc_nro_receptor ?: 'Sin documento' }}
                                                            </div>
                                                        </td>
                                                        <td data-label="Total" class="right-align">{{ $money($receipt->importe_total) }}</td>
                                                        <td data-label="Estado rendición">{{ $receipt->estado_rendicion_label }}</td>
                                                        <td data-label="Informado en">{{ $receipt->informado_en?->format('d/m/Y H:i') ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="grey-text">No hay períodos CAEA cargados con los filtros actuales.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($caeaPeriods && $caeaPeriods->lastPage() > 1)
            <div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div class="grey-text">
                    Página {{ $caeaPeriods->currentPage() }} de {{ $caeaPeriods->lastPage() }}
                </div>

                <ul class="pagination" style="margin:0;">
                    @if ($caeaPeriods->onFirstPage())
                        <li class="disabled"><a href="#!"><i class="material-icons">chevron_left</i></a></li>
                    @else
                        <li class="waves-effect">
                            <a href="{{ $caeaPeriods->previousPageUrl() }}">
                                <i class="material-icons">chevron_left</i>
                            </a>
                        </li>
                    @endif

                    <li class="active"><a href="#!">{{ $caeaPeriods->currentPage() }}</a></li>

                    @if ($caeaPeriods->hasMorePages())
                        <li class="waves-effect">
                            <a href="{{ $caeaPeriods->nextPageUrl() }}">
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
