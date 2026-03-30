@extends('layouts.admin-panel')

@section('title', 'Tarjetas')
@section('header_title', 'Tarjetas')

@php
    $createModalOpen = old('form_context') === 'plan_create';
@endphp

@push('styles')
    <style>
        .tarjetas-toolbar {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }
        .tarjetas-filter-form {
            margin: 0;
            flex: 1 1 320px;
            min-width: 240px;
        }
        .tarjetas-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        #tarjetas_table input[type="text"],
        #tarjetas_table input[type="number"] {
            min-width: 90px;
        }
        .table-field-error {
            color: #c62828;
            font-size: 11px;
            margin-top: 4px;
            line-height: 1.25;
        }
        #tarjetas_table {
            min-width: 720px;
        }
        #tarjetas_table td input[type="text"],
        #tarjetas_table td input[type="number"] {
            height: 40px !important;
            min-height: 40px;
            padding: 0 12px !important;
            border-radius: 14px !important;
            font-size: .88rem;
        }
        @media only screen and (max-width: 992px) {
            .tarjetas-toolbar {
                align-items: stretch;
            }
            .tarjetas-filter-form {
                flex-basis: 100%;
                min-width: 0;
            }
            .tarjetas-actions {
                width: 100%;
                margin-left: 0;
                justify-content: flex-start;
            }
            .tarjetas-actions .btn,
            .tarjetas-actions .btn-flat {
                width: auto;
            }
        }
        @media only screen and (max-width: 600px) {
            .tarjetas-actions .btn,
            .tarjetas-actions .btn-flat {
                width: 100%;
                text-align: center;
            }
            #tarjetas_table td {
                padding-top: 8px;
                padding-bottom: 8px;
            }
            #tarjetas_table td input[type="text"],
            #tarjetas_table td input[type="number"] {
                width: 100%;
                min-width: 0;
                box-sizing: border-box;
            }
            #tarjetas_table .btn-small {
                margin-top: 4px;
            }
            #plan_create_modal .modal-footer {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            #plan_create_modal .modal-footer .btn,
            #plan_create_modal .modal-footer .btn-flat {
                width: 100%;
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-content">
            <span class="card-title">Tarjetas / Cuotas / Recargos</span>

            <p class="grey-text" style="margin-top:0;">
                Administrá los planes de cuotas para tarjetas de crédito usados en el POS.
            </p>

            <div class="card-panel grey lighten-5" style="margin-top:12px; padding:12px 16px;">
                <div class="row tarjetas-toolbar" style="margin:0;">
                    <form id="tarjetas_filter_form" class="tarjetas-filter-form" method="get" action="{{ route('admin-panel.tarjetas.index') }}">
                        <div class="input-field" style="margin:0;">
                            <i class="material-icons prefix grey-text text-darken-1">search</i>
                            <input id="q_tarjeta" type="text" name="q" value="{{ $search }}" placeholder="Ej: VISA, MASTER, AMEX" autocomplete="off">
                            <label for="q_tarjeta" class="active">Buscar tarjeta</label>
                        </div>
                    </form>

                    <div class="tarjetas-actions">
                        @if ($search !== '')
                            <a href="{{ route('admin-panel.tarjetas.index') }}" class="btn-flat waves-effect">Limpiar</a>
                        @endif
                        <a href="#plan_create_modal" class="btn green waves-effect waves-light modal-trigger">
                            <i class="material-icons left">add</i>Agregar
                        </a>
                    </div>
                </div>
            </div>

            @if ($plans->isNotEmpty())
                <div class="table-wrap">
                    <table id="tarjetas_table" class="striped" style="font-size:13px;">
                        <thead>
                            <tr>
                                <th>Tarjeta</th>
                                <th>Cuotas</th>
                                <th>Recargo %</th>
                                <th>Activo</th>
                                <th class="right-align">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans as $plan)
                                @php
                                    $rowContext = 'plan_update_' . $plan->id;
                                    $rowHasErrors = old('form_context') === $rowContext;
                                @endphp
                                <tr>
                                    <td style="min-width:180px;">
                                        <form id="plan_update_{{ $plan->id }}" method="POST" action="{{ route('admin-panel.tarjetas.update', $plan) }}" style="display:none;">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="form_context" value="{{ $rowContext }}">
                                            <input type="hidden" name="q_context" value="{{ $search }}">
                                            <input type="hidden" name="activo" value="0">
                                        </form>
                                        <input type="text" name="tarjeta" form="plan_update_{{ $plan->id }}" value="{{ $rowHasErrors ? old('tarjeta') : $plan->tarjeta }}" maxlength="30" required style="margin:0; height:2rem;">
                                        @if ($rowHasErrors)
                                            @error('tarjeta')<div class="table-field-error">{{ $message }}</div>@enderror
                                        @endif
                                    </td>
                                    <td style="width:110px;">
                                        <input type="number" name="cuotas" form="plan_update_{{ $plan->id }}" min="1" value="{{ $rowHasErrors ? old('cuotas') : $plan->cuotas }}" required style="margin:0; height:2rem;">
                                        @if ($rowHasErrors)
                                            @error('cuotas')<div class="table-field-error">{{ $message }}</div>@enderror
                                        @endif
                                    </td>
                                    <td style="width:140px;">
                                        <input type="number" name="recargo_pct" form="plan_update_{{ $plan->id }}" step="0.01" min="0" value="{{ $rowHasErrors ? old('recargo_pct') : number_format((float) $plan->recargo_pct, 2, '.', '') }}" required style="margin:0; height:2rem;">
                                        @if ($rowHasErrors)
                                            @error('recargo_pct')<div class="table-field-error">{{ $message }}</div>@enderror
                                        @endif
                                    </td>
                                    <td style="width:90px;">
                                        <label>
                                            <input type="checkbox" name="activo" value="1" form="plan_update_{{ $plan->id }}" @checked($rowHasErrors ? old('activo') : $plan->activo)>
                                            <span></span>
                                        </label>
                                    </td>
                                    <td class="right-align" style="white-space:nowrap;">
                                        <button class="btn-small blue" type="submit" form="plan_update_{{ $plan->id }}" title="Guardar" aria-label="Guardar">
                                            <i class="material-icons">save</i>
                                        </button>
                                        <form method="POST" action="{{ route('admin-panel.tarjetas.destroy', $plan) }}" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('¿Eliminar este plan?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="q_context" value="{{ $search }}">
                                            <button class="btn-small red" type="submit" title="Eliminar" aria-label="Eliminar">
                                                <i class="material-icons">delete</i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p id="tarjetas_filter_empty" class="grey-text" style="display:none; margin-top:12px;">
                    No se encontraron tarjetas para esa búsqueda.
                </p>
            @else
                <p class="grey-text">No hay planes de cuotas cargados todavía.</p>
            @endif
        </div>
    </div>

    <div id="plan_create_modal" class="modal modal-fixed-footer" @if ($createModalOpen) data-auto-open="true" @endif>
        <div class="modal-content">
            <div class="admin-modal-head">
                <h5 class="admin-modal-title">Agregar plan de tarjeta</h5>
                <p class="admin-modal-subtitle">Cargá tarjeta, cantidad de cuotas y recargo para usar en el POS.</p>
            </div>

            <div class="admin-modal-body">
                <form id="plan_create_form" method="POST" action="{{ route('admin-panel.tarjetas.store') }}">
                    @csrf
                    <input type="hidden" name="form_context" value="plan_create">
                    <input type="hidden" name="q_context" value="{{ $search }}">
                    <input type="hidden" name="activo" value="0">

                    <div class="row" style="margin-bottom:0;">
                        <div class="input-field col s12 m6">
                            <input id="plan_tarjeta_new" type="text" name="tarjeta" maxlength="30" required placeholder="Ej: VISA" value="{{ old('form_context') === 'plan_create' ? old('tarjeta') : '' }}">
                            <label for="plan_tarjeta_new" class="active">Tarjeta</label>
                            @if ($createModalOpen)
                                @error('tarjeta')<div class="table-field-error">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="input-field col s12 m3">
                            <input id="plan_cuotas_new" type="number" name="cuotas" min="1" required placeholder="3" value="{{ old('form_context') === 'plan_create' ? old('cuotas') : '' }}">
                            <label for="plan_cuotas_new" class="active">Cuotas</label>
                            @if ($createModalOpen)
                                @error('cuotas')<div class="table-field-error">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="input-field col s12 m3">
                            <input id="plan_recargo_new" type="number" name="recargo_pct" step="0.01" min="0" required value="{{ old('form_context') === 'plan_create' ? old('recargo_pct', '0.00') : '0.00' }}">
                            <label for="plan_recargo_new" class="active">Recargo %</label>
                            @if ($createModalOpen)
                                @error('recargo_pct')<div class="table-field-error">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    </div>

                    <p style="margin-top:0;">
                        <label>
                            <input type="checkbox" name="activo" value="1" @checked(old('form_context') === 'plan_create' ? old('activo', true) : true)>
                            <span>Activo</span>
                        </label>
                    </p>
                </form>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Cancelar</a>
            <button type="submit" form="plan_create_form" class="btn green waves-effect waves-light">
                <i class="material-icons left">add</i>Agregar
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('q_tarjeta');
            const table = document.getElementById('tarjetas_table');

            if (!input || !table) {
                return;
            }

            const rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
            const emptyMsg = document.getElementById('tarjetas_filter_empty');

            function normalizeText(text) {
                return (text || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
            }

            function applyFilter() {
                const query = normalizeText(input.value);
                let visible = 0;

                rows.forEach(function (row) {
                    const tarjetaInput = row.querySelector('input[name="tarjeta"]');
                    const key = normalizeText(tarjetaInput ? tarjetaInput.value : row.textContent);
                    const match = !query || key.indexOf(query) !== -1;

                    row.style.display = match ? '' : 'none';
                    if (match) {
                        visible += 1;
                    }
                });

                if (emptyMsg) {
                    emptyMsg.style.display = visible === 0 ? '' : 'none';
                }
            }

            input.addEventListener('input', applyFilter);
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            applyFilter();
        })();
    </script>
@endpush
