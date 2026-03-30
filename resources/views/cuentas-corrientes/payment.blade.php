@extends('layouts.admin-panel')

@section('title', 'Registrar pago')
@section('header_title', 'Registrar pago de cuenta corriente')

@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
    $selectedVentas = collect(old('ventas', []))->map(fn ($ventaId) => (int) $ventaId)->all();
    $openPaymentModal = $errors->any();
@endphp

@push('styles')
    <style>
        .cc-payment-page {
            display: grid;
            gap: 16px;
        }

        .cc-payment-actions-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cc-account-card .card-content,
        .cc-pending-card .card-content {
            display: grid;
            gap: 14px;
        }

        .cc-account-grid,
        .cc-account-kpis {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .cc-account-item,
        .cc-account-kpi {
            padding: 12px 14px;
            border: 1px solid var(--ui-border);
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(243, 248, 252, 0.96) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .cc-account-item span,
        .cc-account-kpi span {
            display: block;
            margin-bottom: 4px;
            color: var(--ui-text-soft);
            font-size: 0.73rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .cc-account-item strong,
        .cc-account-kpi strong {
            display: block;
            color: var(--ui-text);
            font-size: 0.96rem;
            line-height: 1.35;
            font-weight: 800;
            word-break: break-word;
        }

        .cc-account-kpi strong {
            font-size: 1.15rem;
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

        .cc-payment-layout {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(320px, 0.92fr) minmax(0, 1.08fr);
        }

        .cc-payment-toolbar {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }

        .cc-payment-toolbar .btn {
            flex-shrink: 0;
        }

        .cc-selection-summary {
            min-width: 0;
            width: 100%;
            margin: 0;
            border: 1px solid var(--ui-border);
            border-radius: 18px;
            padding: 12px 14px;
            background: var(--ui-card-soft);
            color: var(--ui-text);
        }

        .cc-selection-summary strong {
            display: block;
            margin-bottom: 4px;
        }

        .cc-selection-summary.is-danger {
            background: var(--ui-danger-bg);
            border-color: var(--ui-danger-border);
            color: var(--ui-danger-text);
        }

        .cc-selection-summary.is-info {
            background: var(--ui-info-bg);
            border-color: var(--ui-info-border);
            color: var(--ui-info-text);
        }

        .cc-selection-summary.is-success {
            background: var(--ui-success-bg);
            border-color: var(--ui-success-border);
            color: var(--ui-success-text);
        }

        .cc-payment-table {
            border: 1px solid var(--ui-border);
            border-radius: 18px;
            overflow: hidden;
            margin-top: 8px;
        }

        .cc-payment-table table {
            margin-bottom: 0;
            width: 100%;
        }

        .cc-payment-table tr.is-overdue {
            background: rgba(180, 35, 24, 0.05);
        }

        .cc-sale-chip {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.25;
            text-align: center;
            white-space: normal;
            background: var(--ui-danger-bg);
            border: 1px solid var(--ui-danger-border);
            color: var(--ui-danger-text);
        }

        .cc-sale-chip.is-info {
            background: var(--ui-info-bg);
            border-color: var(--ui-info-border);
            color: var(--ui-info-text);
        }

        .cc-sale-chip.is-neutral {
            background: var(--ui-card-soft);
            border-color: var(--ui-border);
            color: var(--ui-text-soft);
        }

        .cc-sale-status {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 6px;
            min-width: 0;
        }

        .cc-payment-empty {
            margin-top: 10px;
            border: 1px dashed var(--ui-border-strong);
            border-radius: 18px;
            padding: 14px;
            color: var(--ui-text-soft);
            background: var(--ui-card-soft);
        }

        .cc-modal-summary {
            margin-top: 8px;
            border: 1px solid var(--ui-border);
            border-radius: 16px;
            padding: 12px 14px;
            background: var(--ui-card-soft);
            color: var(--ui-text);
        }

        .cc-modal-summary strong {
            display: block;
            margin-bottom: 4px;
        }

        .cc-modal-summary.is-danger {
            background: var(--ui-danger-bg);
            border-color: var(--ui-danger-border);
            color: var(--ui-danger-text);
        }

        .cc-modal-summary.is-info {
            background: var(--ui-info-bg);
            border-color: var(--ui-info-border);
            color: var(--ui-info-text);
        }

        .cc-modal-summary.is-success {
            background: var(--ui-success-bg);
            border-color: var(--ui-success-border);
            color: var(--ui-success-text);
        }

        @media (max-width: 992px) {
            .cc-payment-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .cc-payment-toolbar .btn {
                width: 100%;
            }

            .cc-payment-table td[data-label="Selección"] {
                justify-content: flex-start;
            }

            .cc-payment-table td[data-label="Selección"] label {
                margin-left: auto;
            }

            .cc-payment-table td[data-label="Estado"] .cc-sale-status {
                flex: 1 1 auto;
            }
        }

        @media (max-width: 640px) {
            .cc-account-grid,
            .cc-account-kpis {
                grid-template-columns: 1fr;
            }

            .cc-payment-actions-bar .btn-flat {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="cc-payment-page">
        <div class="cc-payment-actions-bar">
            <a class="btn-flat" href="{{ route('cuentas-corrientes.index') }}">
                <i class="material-icons left">arrow_back</i>Volver al listado
            </a>
            <a class="btn-flat" href="{{ route('cuentas-corrientes.show', $cuenta) }}">
                <i class="material-icons left">visibility</i>Ver cuenta
            </a>
        </div>

        <form id="cc_payment_form" method="POST" action="{{ route('cuentas-corrientes.payments.store', $cuenta) }}">
            @csrf

            <div class="cc-payment-layout">
                <div class="card cc-account-card">
                    <div class="card-content">
                        <span class="card-title">Cuenta</span>

                        <div class="cc-account-grid">
                            <div class="cc-account-item">
                                <span>DNI</span>
                                <strong>{{ $cliente->dni }}</strong>
                            </div>
                            <div class="cc-account-item">
                                <span>Cliente</span>
                                <strong>{{ $cliente->apellido }}, {{ $cliente->nombre }}</strong>
                            </div>
                            <div class="cc-account-item">
                                <span>Telefono</span>
                                <strong>{{ $cliente->telefono ?: '-' }}</strong>
                            </div>
                            <div class="cc-account-item">
                                <span>Cuenta activa</span>
                                <strong>{{ $cuenta->activa ? 'Si' : 'No' }}</strong>
                            </div>
                        </div>

                        <div class="cc-account-kpis">
                            <div class="cc-account-kpi">
                                <span>Saldo actual</span>
                                <strong>{{ $money($saldo) }}</strong>
                            </div>
                        </div>

                        @if (($alertaVencidas['count'] ?? 0) > 0)
                            <div class="card-panel cc-overdue-panel">
                                <strong>Alerta de mora +30 dias</strong>
                                {{ $alertaVencidas['count'] }} venta(s) siguen pendientes por {{ $money($alertaVencidas['total']) }}.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card cc-pending-card">
                    <div class="card-content">
                        <div class="cc-payment-toolbar">
                            <div id="cc_selection_summary" class="cc-selection-summary is-info">
                                <strong>Monto pendiente seleccionado</strong>
                                Selecciona una o mas ventas para habilitar el registro del pago.
                            </div>

                            <button
                                id="cc_open_payment_modal"
                                class="btn"
                                type="button"
                                @disabled($ventasPendientes->isEmpty())
                            >
                                <i class="material-icons left">payments</i>Registrar pago
                            </button>
                        </div>

                        <span class="card-title">Ventas pendientes para aplicar</span>
                        @error('ventas')<div class="red-text text-darken-2" style="font-size:12px; margin-bottom:8px;">{{ $message }}</div>@enderror

                        @if ($ventasPendientes->isNotEmpty())
                            <div class="responsive-table cc-payment-table">
                                <table class="striped responsive-stack-table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Comprobante</th>
                                            <th>Fecha</th>
                                            <th>Antig.</th>
                                            <th>Estado</th>
                                            <th class="right-align">Monto original</th>
                                            <th class="right-align">Debe</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ventasPendientes as $ventaPendiente)
                                            <tr @class(['is-overdue' => $ventaPendiente->vencida_30_calc])>
                                                <td data-label="Selección">
                                                    <label>
                                                        <input
                                                            type="checkbox"
                                                            name="ventas[]"
                                                            value="{{ $ventaPendiente->venta_id }}"
                                                            data-pendiente="{{ $ventaPendiente->monto_pendiente_calc }}"
                                                            @checked(in_array((int) $ventaPendiente->venta_id, $selectedVentas, true))
                                                        >
                                                        <span></span>
                                                    </label>
                                                </td>
                                                <td data-label="Comprobante">
                                                    <strong>{{ $ventaPendiente->venta?->codigo_sucursal ?? ('#' . $ventaPendiente->venta_id) }}</strong>
                                                </td>
                                                <td data-label="Fecha">{{ $ventaPendiente->fecha?->format('d/m/Y') ?? '-' }}</td>
                                                <td data-label="Antig.">{{ $ventaPendiente->antiguedad_dias_calc }} dias</td>
                                                <td data-label="Estado">
                                                    <div class="cc-sale-status">
                                                        @if ((float) $ventaPendiente->monto_aplicado_calc > 0)
                                                            <span class="cc-sale-chip is-info">Pago parcial</span>
                                                        @else
                                                            <span class="cc-sale-chip is-neutral">Pendiente</span>
                                                        @endif

                                                        @if ($ventaPendiente->vencida_30_calc)
                                                            <span class="cc-sale-chip">Vencida +30d</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td data-label="Monto original" class="right-align">
                                                    <strong>{{ $money($ventaPendiente->monto) }}</strong>
                                                    @if ((float) $ventaPendiente->monto_aplicado_calc > 0)
                                                        <div class="grey-text" style="font-size:12px;">
                                                            Aplicado: {{ $money($ventaPendiente->monto_aplicado_calc) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td data-label="Debe" class="right-align">
                                                    <strong>{{ $money($ventaPendiente->monto_pendiente_calc) }}</strong>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="cc-payment-empty">
                                No hay ventas pendientes para esta cuenta corriente.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div id="cc_payment_modal" class="modal modal-fixed-footer" @if ($openPaymentModal) data-auto-open="true" @endif>
                <div class="modal-content">
                    <div class="admin-modal-head">
                        <h5 class="admin-modal-title">Registrar pago</h5>
                        <p class="admin-modal-subtitle">
                            {{ $cliente->apellido }}, {{ $cliente->nombre }} · DNI {{ $cliente->dni }}
                        </p>
                    </div>

                    <div class="admin-modal-body">
                        <div id="cc_modal_selection_info" class="cc-selection-summary is-info" style="margin-bottom:14px;">
                            <strong>Ventas seleccionadas</strong>
                            Selecciona una o mas ventas para asignar el pago.
                        </div>

                        <div class="row" style="margin-bottom:0;">
                            <div class="input-field col s12 m4">
                                <input id="cc_pago_monto" type="number" name="monto" step="0.01" min="0.01" value="{{ old('monto') }}" required>
                                <label for="cc_pago_monto" class="active">Monto</label>
                                @error('monto')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                            </div>

                            <div class="input-field col s12 m8">
                                <input id="cc_pago_referencia" type="text" name="referencia" value="{{ old('referencia') }}">
                                <label for="cc_pago_referencia" class="active">Referencia</label>
                                @error('referencia')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="input-field">
                            <textarea id="cc_pago_observacion" name="observacion" class="materialize-textarea">{{ old('observacion') }}</textarea>
                            <label for="cc_pago_observacion" class="active">Observacion</label>
                            @error('observacion')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                        </div>

                        <div id="cc_modal_summary" class="cc-modal-summary is-info">
                            <strong>Confirmacion del pago</strong>
                            Ingresa el monto para registrar el pago sobre las ventas seleccionadas.
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <a class="btn-flat modal-close">Cancelar</a>
                    <button id="cc_payment_submit" class="btn" type="submit">
                        <i class="material-icons left">save</i>Confirmar pago
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('cc_payment_modal');
            const openButton = document.getElementById('cc_open_payment_modal');
            const amountInput = document.getElementById('cc_pago_monto');
            const submitButton = document.getElementById('cc_payment_submit');
            const pageSummaryEl = document.getElementById('cc_selection_summary');
            const modalSelectionInfoEl = document.getElementById('cc_modal_selection_info');
            const modalSummaryEl = document.getElementById('cc_modal_summary');
            const checkboxes = Array.from(document.querySelectorAll('input[name="ventas[]"]'));

            if (!modalEl || !openButton || !amountInput || !submitButton || !pageSummaryEl || !modalSelectionInfoEl || !modalSummaryEl) {
                return;
            }

            function parseMoney(value) {
                const parsed = Number.parseFloat(value);

                return Number.isFinite(parsed) ? parsed : 0;
            }

            function formatMoney(value) {
                return '$' + value.toLocaleString('es-AR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function setBoxState(element, type, title, message) {
                element.className = element.className
                    .replace(/\bis-danger\b|\bis-info\b|\bis-success\b/g, '')
                    .trim();
                element.className += ' ' + type;
                element.innerHTML = '<strong>' + title + '</strong>' + message;
            }

            function selectedContext() {
                const selected = checkboxes.filter(function (checkbox) {
                    return checkbox.checked;
                });
                const total = selected.reduce(function (carry, checkbox) {
                    return carry + parseMoney(checkbox.dataset.pendiente || '0');
                }, 0);

                return {
                    count: selected.length,
                    total: total,
                };
            }

            function refreshSelectionSummary() {
                const context = selectedContext();

                if (context.count === 0) {
                    setBoxState(
                        pageSummaryEl,
                        'is-info',
                        'Monto pendiente seleccionado',
                        'Selecciona una o mas ventas para habilitar el registro del pago.',
                    );
                    setBoxState(
                        modalSelectionInfoEl,
                        'is-info',
                        'Ventas seleccionadas',
                        'Selecciona una o mas ventas para asignar el pago.',
                    );
                    openButton.disabled = true;
                    return context;
                }

                const label = context.count === 1 ? 'venta seleccionada' : 'ventas seleccionadas';
                const message = context.count + ' ' + label + ' por ' + formatMoney(context.total) + '.';

                setBoxState(pageSummaryEl, 'is-success', 'Monto pendiente seleccionado', message);
                setBoxState(modalSelectionInfoEl, 'is-success', 'Ventas seleccionadas', message);
                openButton.disabled = false;

                return context;
            }

            function refreshModalSummary() {
                const context = refreshSelectionSummary();
                const paymentAmount = parseMoney(amountInput.value || '0');

                if (context.count === 0) {
                    setBoxState(
                        modalSummaryEl,
                        'is-info',
                        'Confirmacion del pago',
                        'Selecciona ventas antes de registrar el pago.',
                    );
                    submitButton.disabled = true;
                    return;
                }

                if (paymentAmount <= 0) {
                    setBoxState(
                        modalSummaryEl,
                        'is-info',
                        'Monto pendiente',
                        'Ingresa un monto para aplicar sobre ' + formatMoney(context.total) + ' seleccionado.',
                    );
                    submitButton.disabled = true;
                    return;
                }

                if (paymentAmount > (context.total + 0.00001)) {
                    setBoxState(
                        modalSummaryEl,
                        'is-danger',
                        'Monto excedido',
                        'El monto supera el saldo pendiente de las ventas seleccionadas (' + formatMoney(context.total) + ').',
                    );
                    submitButton.disabled = true;
                    return;
                }

                if (Math.abs(paymentAmount - context.total) < 0.00001) {
                    setBoxState(
                        modalSummaryEl,
                        'is-success',
                        'Ventas cubiertas',
                        'El pago cancelara por completo las ventas seleccionadas por ' + formatMoney(context.total) + '.',
                    );
                    submitButton.disabled = false;
                    return;
                }

                setBoxState(
                    modalSummaryEl,
                    'is-info',
                    'Pago parcial asignado',
                    'Se aplicaran ' + formatMoney(paymentAmount) + ' sobre ' + formatMoney(context.total) + ' seleccionado. Quedaran ' + formatMoney(context.total - paymentAmount) + ' pendientes.',
                );
                submitButton.disabled = false;
            }

            openButton.addEventListener('click', function () {
                refreshModalSummary();

                if (openButton.disabled) {
                    return;
                }

                if (!window.M || !M.Modal) {
                    return;
                }

                const instance = M.Modal.getInstance(modalEl) || M.Modal.init(modalEl, {});
                instance.open();
            });

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', refreshModalSummary);
            });

            amountInput.addEventListener('input', refreshModalSummary);
            refreshModalSummary();
        });
    </script>
@endpush
