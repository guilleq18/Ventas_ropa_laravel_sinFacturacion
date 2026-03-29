@extends('layouts.admin-panel')

@section('title', 'Cuentas corrientes')
@section('header_title', 'Cuentas corrientes')

@php
    $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
    $openCreateModal = request()->query('new_cc') === '1' || $errors->any();
@endphp

@push('styles')
    <style>
        .cc-toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }

        .cc-dni-status {
            margin-top: 10px;
            margin-bottom: 0;
            border-radius: 18px;
            box-shadow: none;
        }

        .cc-dni-status.is-loading {
            background: var(--ui-card-soft);
            border-color: var(--ui-border);
            color: var(--ui-text-soft);
        }

        .cc-dni-status.is-info {
            background: var(--ui-info-bg);
            border-color: var(--ui-info-border);
            color: var(--ui-info-text);
        }

        .cc-dni-status.is-danger {
            background: var(--ui-danger-bg);
            border-color: var(--ui-danger-border);
            color: var(--ui-danger-text);
        }

        .cc-dni-status-link {
            display: inline-flex;
            align-items: center;
            margin-top: 8px;
            color: inherit;
            font-weight: 700;
            text-decoration: underline;
        }

        .cc-readonly {
            opacity: .88;
        }

        .cc-alert-overview {
            margin-bottom: 14px;
            background: var(--ui-danger-bg);
            border-color: var(--ui-danger-border);
            color: var(--ui-danger-text);
        }

        .cc-alert-overview strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .cc-overdue-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .02em;
            background: var(--ui-danger-bg);
            border: 1px solid var(--ui-danger-border);
            color: var(--ui-danger-text);
        }

        .cc-overdue-cell {
            white-space: nowrap;
        }

        .cc-table-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .cc-action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid var(--ui-border);
            background: rgba(255, 255, 255, 0.82);
            color: var(--ui-text);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .cc-action-button:hover {
            transform: translateY(-1px);
            border-color: var(--ui-border-strong);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
        }

        .cc-action-button i {
            font-size: 18px;
            line-height: 1;
        }
    </style>
@endpush

@section('content')
    <div class="cc-toolbar">
        <a class="btn modal-trigger" href="#modalNuevaCC">
            <i class="material-icons left">add</i>Nueva cuenta corriente
        </a>
    </div>

    @if (($stats['cuentas_con_alerta'] ?? 0) > 0)
        <div class="card-panel cc-alert-overview">
            <strong>Alerta de mora +30 dias</strong>
            Hay {{ $stats['cuentas_con_alerta'] }} cuenta(s) con ventas vencidas por {{ $money($stats['saldo_vencido_30']) }}.
        </div>
    @endif

    <div id="modalNuevaCC" class="modal" @if ($openCreateModal) data-auto-open="true" @endif>
        <div class="modal-content">
            <div class="admin-modal-head">
                <h5 class="admin-modal-title">Nueva cuenta corriente</h5>
                <p class="admin-modal-subtitle">Si el DNI ya existe sin cuenta, se reutiliza ese cliente.</p>
            </div>

            <div class="admin-modal-body">
                <form
                    id="form-nueva-cc"
                    method="POST"
                    action="{{ route('cuentas-corrientes.store') }}"
                    data-dni-lookup-url="{{ route('cuentas-corrientes.lookup-dni') }}"
                >
                    @csrf

                    <div class="row" style="margin-bottom:0;">
                        <div class="input-field col s12 m4">
                            <input id="cc_dni" type="text" name="dni" value="{{ old('dni') }}" required>
                            <label for="cc_dni" class="active">DNI</label>
                            @error('dni')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                            <div
                                id="cc_dni_status"
                                class="card-panel cc-dni-status"
                                role="status"
                                aria-live="polite"
                                hidden
                            ></div>
                        </div>

                        <div class="input-field col s12 m4">
                            <input id="cc_apellido" type="text" name="apellido" value="{{ old('apellido') }}" required>
                            <label for="cc_apellido" class="active">Apellido</label>
                            @error('apellido')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                        </div>

                        <div class="input-field col s12 m4">
                            <input id="cc_nombre" type="text" name="nombre" value="{{ old('nombre') }}" required>
                            <label for="cc_nombre" class="active">Nombre</label>
                            @error('nombre')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                        </div>

                        <div class="input-field col s12 m6">
                            <input id="cc_telefono" type="text" name="telefono" value="{{ old('telefono') }}">
                            <label for="cc_telefono" class="active">Telefono</label>
                            @error('telefono')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                        </div>

                        <div class="input-field col s12 m6">
                            <input id="cc_fecha_nacimiento" type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}">
                            <label for="cc_fecha_nacimiento" class="active">Fecha de nacimiento</label>
                            @error('fecha_nacimiento')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                        </div>

                        <div class="input-field col s12">
                            <input id="cc_direccion" type="text" name="direccion" value="{{ old('direccion') }}">
                            <label for="cc_direccion" class="active">Direccion</label>
                            @error('direccion')<div class="red-text text-darken-2" style="font-size:12px;">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-footer">
            <a class="btn-flat modal-close">Cancelar</a>
            <button id="cc_submit_button" class="btn" type="submit" form="form-nueva-cc">
                <i class="material-icons left">save</i>Crear
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-content">
            <span class="card-title">Filtros</span>

            <form method="GET" action="{{ route('cuentas-corrientes.index') }}">
                <div class="row" style="margin-bottom:0;">
                    <div class="input-field col s12 m6">
                        <input id="cc_q" type="text" name="q" value="{{ $filters['q'] }}">
                        <label for="cc_q" class="active">Buscar (DNI / Apellido / Nombre)</label>
                    </div>

                    <div class="input-field col s12 m4">
                        <select name="activa">
                            <option value="1" @selected($filters['activa'] === '1')>Activas</option>
                            <option value="0" @selected($filters['activa'] === '0')>Inactivas</option>
                            <option value="" @selected($filters['activa'] === '')>Todas</option>
                        </select>
                        <label>Estado</label>
                    </div>

                    <div class="input-field col s12 m2" style="margin-top:22px;">
                        <button class="btn" type="submit">
                            <i class="material-icons left">search</i>Aplicar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-content">
            <span class="card-title">Listado</span>

            <div class="responsive-table">
                <table class="striped">
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Cliente</th>
                            <th>Telefono</th>
                            <th>Activa</th>
                            <th>Vencido +30d</th>
                            <th class="right-align">Saldo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cuentas as $cuenta)
                            <tr>
                                <td>{{ $cuenta->cliente->dni }}</td>
                                <td>{{ $cuenta->cliente->apellido }}, {{ $cuenta->cliente->nombre }}</td>
                                <td>{{ $cuenta->cliente->telefono ?: '-' }}</td>
                                <td>{{ $cuenta->activa ? 'Si' : 'No' }}</td>
                                <td class="cc-overdue-cell">
                                    @if ($cuenta->has_overdue_30_calc)
                                        <span class="cc-overdue-chip">{{ $money($cuenta->overdue_30_calc) }}</span>
                                    @else
                                        <span class="grey-text">-</span>
                                    @endif
                                </td>
                                <td class="right-align">{{ $money($cuenta->saldo_calc) }}</td>
                                <td class="right-align">
                                    <div class="cc-table-actions">
                                        <a
                                            class="cc-action-button"
                                            href="{{ route('cuentas-corrientes.payments.create', $cuenta) }}"
                                            title="Registrar pago"
                                            aria-label="Registrar pago"
                                        >
                                            <i class="material-icons">payments</i>
                                        </a>
                                        <a
                                            class="cc-action-button"
                                            href="{{ route('cuentas-corrientes.show', $cuenta) }}"
                                            title="Ver cuenta"
                                            aria-label="Ver cuenta"
                                        >
                                            <i class="material-icons">visibility</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="grey-text">Sin resultados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form-nueva-cc');

            if (!form) {
                return;
            }

            const dniInput = document.getElementById('cc_dni');
            const submitButton = document.getElementById('cc_submit_button');
            const statusEl = document.getElementById('cc_dni_status');
            const lookupUrl = form.dataset.dniLookupUrl;
            const managedFields = [
                document.getElementById('cc_apellido'),
                document.getElementById('cc_nombre'),
                document.getElementById('cc_telefono'),
                document.getElementById('cc_fecha_nacimiento'),
                document.getElementById('cc_direccion'),
            ].filter(Boolean);

            if (!dniInput || !submitButton || !statusEl || !lookupUrl) {
                return;
            }

            let debounceTimer = null;
            let activeController = null;
            let lookupSequence = 0;
            let lastCheckedDni = '';
            let lastStatus = 'idle';
            let pendingDni = '';
            let pendingSubmit = false;
            let autoFillSnapshot = new Map();

            function syncMaterializeLabels() {
                if (window.M && typeof window.M.updateTextFields === 'function') {
                    window.M.updateTextFields();
                }
            }

            function normalizeDni(value) {
                return (value || '').trim();
            }

            function setManagedFieldsReadonly(readonly) {
                managedFields.forEach(function (field) {
                    field.readOnly = readonly;
                    field.classList.toggle('cc-readonly', readonly);
                });
            }

            function captureAutoFillSnapshot(cliente) {
                autoFillSnapshot = new Map([
                    ['cc_apellido', cliente.apellido || ''],
                    ['cc_nombre', cliente.nombre || ''],
                    ['cc_telefono', cliente.telefono || ''],
                    ['cc_fecha_nacimiento', cliente.fecha_nacimiento || ''],
                    ['cc_direccion', cliente.direccion || ''],
                ]);
            }

            function applyClientSnapshot(cliente) {
                if (!cliente) {
                    return;
                }

                const nextValues = new Map([
                    ['cc_apellido', cliente.apellido || ''],
                    ['cc_nombre', cliente.nombre || ''],
                    ['cc_telefono', cliente.telefono || ''],
                    ['cc_fecha_nacimiento', cliente.fecha_nacimiento || ''],
                    ['cc_direccion', cliente.direccion || ''],
                ]);

                nextValues.forEach(function (value, fieldId) {
                    const field = document.getElementById(fieldId);

                    if (!field) {
                        return;
                    }

                    const previousValue = autoFillSnapshot.get(fieldId);
                    const currentValue = field.value || '';

                    if (currentValue === '' || currentValue === previousValue) {
                        field.value = value;
                    }
                });

                captureAutoFillSnapshot(cliente);
                syncMaterializeLabels();
            }

            function clearAutoFillSnapshotIfUnchanged() {
                autoFillSnapshot.forEach(function (value, fieldId) {
                    const field = document.getElementById(fieldId);

                    if (!field || field.value !== value) {
                        return;
                    }

                    field.value = '';
                });

                autoFillSnapshot = new Map();
                syncMaterializeLabels();
            }

            function setSubmitBlocked(blocked) {
                submitButton.disabled = blocked;
                dniInput.setCustomValidity(blocked ? 'Este DNI ya tiene una cuenta corriente.' : '');
            }

            function clearStatus() {
                statusEl.hidden = true;
                statusEl.textContent = '';
                statusEl.className = 'card-panel cc-dni-status';
            }

            function showStatus(type, message, options = {}) {
                statusEl.hidden = false;
                statusEl.className = 'card-panel cc-dni-status ' + type;
                statusEl.textContent = message;

                if (options.showUrl) {
                    const link = document.createElement('a');
                    link.href = options.showUrl;
                    link.className = 'cc-dni-status-link';
                    link.textContent = 'Abrir cuenta corriente';
                    statusEl.appendChild(document.createElement('br'));
                    statusEl.appendChild(link);
                }
            }

            async function performLookup(dni, options = {}) {
                const normalizedDni = normalizeDni(dni);

                if (normalizedDni === '') {
                    lastCheckedDni = '';
                    lastStatus = 'idle';
                    pendingDni = '';
                    pendingSubmit = false;
                    setManagedFieldsReadonly(false);
                    setSubmitBlocked(false);
                    clearAutoFillSnapshotIfUnchanged();
                    clearStatus();
                    return true;
                }

                if (activeController) {
                    activeController.abort();
                }

                const sequence = ++lookupSequence;
                activeController = new AbortController();
                pendingDni = normalizedDni;
                showStatus('is-loading', 'Verificando DNI...');
                setSubmitBlocked(true);

                try {
                    const response = await fetch(lookupUrl + '?dni=' + encodeURIComponent(normalizedDni), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: activeController.signal,
                    });

                    const payload = await response.json().catch(function () {
                        return {};
                    });

                    if (sequence !== lookupSequence) {
                        return false;
                    }

                    lastCheckedDni = normalizedDni;
                    pendingDni = '';

                    if (!response.ok) {
                        lastStatus = payload.status || 'error';
                        setManagedFieldsReadonly(false);
                        setSubmitBlocked(false);
                        clearAutoFillSnapshotIfUnchanged();
                        showStatus('is-danger', payload.message || 'No se pudo validar el DNI.');
                        return false;
                    }

                    if (payload.status === 'duplicate_account') {
                        lastStatus = payload.status;
                        setManagedFieldsReadonly(true);
                        setSubmitBlocked(true);
                        applyClientSnapshot(payload.cliente || {});
                        showStatus('is-danger', payload.message || 'El DNI ya tiene cuenta corriente.', {
                            showUrl: payload.cuenta_corriente ? payload.cuenta_corriente.show_url : null,
                        });
                        return false;
                    }

                    if (payload.status === 'existing_client') {
                        lastStatus = payload.status;
                        setManagedFieldsReadonly(true);
                        setSubmitBlocked(false);
                        applyClientSnapshot(payload.cliente || {});
                        showStatus('is-info', payload.message || 'Se reutilizara el cliente existente.');
                        return true;
                    }

                    lastStatus = payload.status || 'available';
                    setManagedFieldsReadonly(false);
                    setSubmitBlocked(false);
                    clearAutoFillSnapshotIfUnchanged();
                    showStatus('is-info', payload.message || 'DNI disponible para crear una cuenta nueva.');
                    return true;
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return false;
                    }

                    pendingDni = '';
                    lastStatus = 'error';
                    setManagedFieldsReadonly(false);
                    setSubmitBlocked(false);
                    clearAutoFillSnapshotIfUnchanged();
                    showStatus('is-danger', 'No se pudo validar el DNI en este momento.');
                    return false;
                } finally {
                    if (sequence === lookupSequence) {
                        activeController = null;
                    }

                    if (pendingSubmit && lastCheckedDni === normalizedDni && ['available', 'existing_client'].includes(lastStatus)) {
                        pendingSubmit = false;
                        form.submit();
                    }
                }
            }

            dniInput.addEventListener('input', function () {
                lastCheckedDni = '';
                lastStatus = 'idle';
                pendingSubmit = false;

                const normalizedDni = normalizeDni(dniInput.value);

                if (normalizedDni === '') {
                    if (activeController) {
                        activeController.abort();
                    }

                    setManagedFieldsReadonly(false);
                    setSubmitBlocked(false);
                    clearAutoFillSnapshotIfUnchanged();
                    clearStatus();
                    return;
                }

                setManagedFieldsReadonly(false);
                setSubmitBlocked(false);

                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }

                debounceTimer = window.setTimeout(function () {
                    performLookup(normalizedDni);
                }, 320);
            });

            dniInput.addEventListener('blur', function () {
                const normalizedDni = normalizeDni(dniInput.value);

                if (normalizedDni !== '') {
                    performLookup(normalizedDni);
                }
            });

            form.addEventListener('submit', function (event) {
                const normalizedDni = normalizeDni(dniInput.value);

                if (normalizedDni === '') {
                    return;
                }

                if (pendingDni === normalizedDni || lastCheckedDni !== normalizedDni) {
                    event.preventDefault();
                    pendingSubmit = true;
                    performLookup(normalizedDni);
                    return;
                }

                if (lastStatus === 'duplicate_account') {
                    event.preventDefault();
                    dniInput.reportValidity();
                    dniInput.focus();
                }
            });
        });
    </script>
@endpush
