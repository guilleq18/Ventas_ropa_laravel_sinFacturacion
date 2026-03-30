@extends('layouts.admin-panel')

@section('title', 'Datos de empresa')
@section('header_title', 'Datos de empresa')

@php
    $openBranchModal = $tab === 'sucursales' && (request()->filled('new_sucursal') || $editingBranch);
@endphp

@push('styles')
    <style>
        .empresa-admin-card { border-radius: var(--ui-radius-lg); }
        .empresa-admin-card .card-content { padding-top: 10px; }
        .empresa-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
        .empresa-subtle { color: #6b7280; font-size: 13px; }
        .soft-panel { border: 1px solid var(--ui-border); border-radius: var(--ui-radius-md); padding: 18px; background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(243, 248, 252, 0.94) 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,.88), 0 10px 24px rgba(15, 23, 42, 0.06); }
        .soft-panel h6 { margin: 0 0 14px; font-weight: 900; color: var(--ui-text); }
        .table-wrap { overflow: auto; }
        .compact-table td, .compact-table th { padding: 10px 8px; line-height: 1.35; vertical-align: top; }
        .compact-table td { white-space: normal; word-break: break-word; }
        .field-errors { color: #c62828; font-size: 12px; margin-top: 4px; }
        .status-chip { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; border: 1px solid transparent; }
        .status-chip.active { background: var(--ui-success-bg); border-color: var(--ui-success-border); color: var(--ui-success-text); }
        .status-chip.inactive { background: var(--ui-danger-bg); border-color: var(--ui-danger-border); color: var(--ui-danger-text); }
        .inline-form { display: inline; }
        .actions-cell { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
        .icon-btn { width: 36px; height: 36px; padding: 0; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; color: #475467; background: #f8fafc; border: 1px solid #dde4ee; cursor: pointer; appearance: none; -webkit-appearance: none; }
        .icon-btn:hover { background: #f2f5f9; color: #344054; }
        @media (max-width: 992px) { .soft-panel { margin-bottom: 14px; } }
    </style>
@endpush

@section('content')
    <div class="card empresa-admin-card">
        <div class="card-content">
            <div class="empresa-toolbar">
                <div>
                    <span class="card-title" style="margin-bottom:4px;">Datos de empresa y sucursales</span>
                    <div class="empresa-subtle">Configurá datos fiscales y administrá las sucursales desde una sola pantalla.</div>
                </div>
                @if ($tab === 'sucursales')
                    <a href="{{ route('admin-panel.empresa.index', ['tab' => 'sucursales', 'new_sucursal' => 1]) }}" class="btn brown darken-1 waves-effect waves-light">
                        <i class="material-icons left">add_business</i>Nueva sucursal
                    </a>
                @endif
            </div>

            <div class="card" style="margin:0 0 14px 0;">
                <div class="card-content" style="padding:10px 16px;">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a class="btn {{ $tab === 'empresa' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect" href="{{ route('admin-panel.empresa.index', ['tab' => 'empresa']) }}">
                            <i class="material-icons left">storefront</i>Datos de empresa
                        </a>
                        <a class="btn {{ $tab === 'sucursales' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect" href="{{ route('admin-panel.empresa.index', ['tab' => 'sucursales']) }}">
                            <i class="material-icons left">store</i>Sucursales
                        </a>
                    </div>
                </div>
            </div>

            <div id="tab-empresa" @if ($tab !== 'empresa') style="display:none;" @endif>
                <div class="row" style="margin-bottom:0;">
                    <div class="col s12 l8">
                        <div class="soft-panel">
                            <h6>Datos de empresa</h6>
                            <p class="empresa-subtle" style="margin-top:-6px; margin-bottom:10px;">Estos datos se usan en tickets y configuraciones comerciales del sistema.</p>

                            <form method="POST" action="{{ route('admin-panel.empresa.update') }}" novalidate>
                                @csrf
                                @method('PUT')

                                <div class="row" style="margin-bottom:0;">
                                    <div class="input-field col s12 m6">
                                        <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $company['nombre']) }}">
                                        <label for="nombre" class="active">Nombre</label>
                                        @error('nombre')<div class="field-errors">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="input-field col s12 m6">
                                        <input id="cuit" type="text" name="cuit" value="{{ old('cuit', $company['cuit']) }}">
                                        <label for="cuit" class="active">CUIT</label>
                                        @error('cuit')<div class="field-errors">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="row" style="margin-bottom:0;">
                                    <div class="input-field col s12 m6">
                                        <input id="razon_social" type="text" name="razon_social" value="{{ old('razon_social', $company['razon_social']) }}">
                                        <label for="razon_social" class="active">Razón social</label>
                                        @error('razon_social')<div class="field-errors">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col s12 m6" style="margin-top:10px;">
                                        <div class="grey-text text-darken-2" style="font-size:12px; margin-bottom:6px; font-weight:800;">Condición fiscal</div>
                                        <select name="condicion_fiscal">
                                            @foreach ($fiscalChoices as $value => $label)
                                                <option value="{{ $value }}" @selected(old('condicion_fiscal', $company['condicion_fiscal']) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('condicion_fiscal')<div class="field-errors">{{ $message }}</div>@enderror
                                        <div class="empresa-subtle" style="margin-top:6px;">Define cómo se mostrará el detalle fiscal en POS y ticket.</div>
                                    </div>
                                </div>

                                <div class="row" style="margin-bottom:0;">
                                    <div class="input-field col s12">
                                        <input id="direccion" type="text" name="direccion" value="{{ old('direccion', $company['direccion']) }}">
                                        <label for="direccion" class="active">Dirección</label>
                                        @error('direccion')<div class="field-errors">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:4px;">
                                    <button type="submit" class="btn waves-effect waves-light"><i class="material-icons left">save</i>Guardar</button>
                                    <a href="{{ route('admin-panel.empresa.index', ['tab' => 'empresa']) }}" class="btn-flat waves-effect">Limpiar</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col s12 l4">
                        <div class="card-panel">
                            <div style="font-weight:700; margin-bottom:6px;">Uso actual</div>
                            <div class="empresa-subtle">
                                El campo <strong>Nombre</strong> se usa en el encabezado del ticket de venta.
                                También podés completar razón social, CUIT, condición fiscal y dirección para futuras impresiones/reportes.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-sucursales" @if ($tab !== 'sucursales') style="display:none;" @endif>
                <div class="row" style="margin-bottom:0;">
                    <div class="col s12">
                        <div class="soft-panel">
                            <h6>Sucursales creadas</h6>
                            <div class="table-wrap">
                                <table class="striped compact-table responsive-stack-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Dirección</th>
                                            <th>Teléfono</th>
                                            <th style="width:120px;">Estado</th>
                                            <th style="width:170px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($branches as $branch)
                                            <tr>
                                                <td data-label="Nombre" style="font-weight:600;">{{ $branch->nombre }}</td>
                                                <td data-label="Dirección">{{ $branch->direccion ?: '-' }}</td>
                                                <td data-label="Teléfono">{{ $branch->telefono ?: '-' }}</td>
                                                <td data-label="Estado">
                                                    @if ($branch->activa)
                                                        <span class="status-chip active">Activa</span>
                                                    @else
                                                        <span class="status-chip inactive">Inactiva</span>
                                                    @endif
                                                </td>
                                                <td data-label="Acciones">
                                                    <div class="actions-cell">
                                                        <a href="{{ route('admin-panel.empresa.index', ['tab' => 'sucursales', 'edit_sucursal' => $branch->id]) }}" class="icon-btn waves-effect" title="Editar sucursal">
                                                            <i class="material-icons">edit</i>
                                                        </a>
                                                        <form method="POST" action="{{ route('admin-panel.sucursales.toggle', $branch) }}" class="inline-form">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="icon-btn waves-effect" title="{{ $branch->activa ? 'Desactivar' : 'Activar' }}">
                                                                <i class="material-icons">{{ $branch->activa ? 'toggle_off' : 'toggle_on' }}</i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="empresa-subtle">No hay sucursales creadas.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-sucursal" class="modal modal-fixed-footer" @if ($openBranchModal) data-auto-open="true" @endif>
        <div class="modal-content">
            <div class="admin-modal-head">
                <h5 class="admin-modal-title">{{ $editingBranch ? 'Editar sucursal' : 'Crear sucursal' }}</h5>
                <p class="admin-modal-subtitle">
                    @if ($editingBranch)
                        Actualizá los datos de <strong>{{ $editingBranch->nombre }}</strong>.
                    @else
                        Cargá los datos de la nueva sucursal para que quede disponible en todo el sistema.
                    @endif
                </p>
            </div>

            <div class="admin-modal-body">
                <form id="form-sucursal" method="POST" action="{{ $editingBranch ? route('admin-panel.sucursales.update', $editingBranch) : route('admin-panel.sucursales.store') }}">
                    @csrf
                    @if ($editingBranch)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="activa" value="0">

                    <div class="input-field">
                        <input id="sucursal_nombre" type="text" name="nombre" value="{{ old('nombre', $editingBranch?->nombre) }}" required>
                        <label for="sucursal_nombre" class="active">Nombre</label>
                        @error('nombre')<div class="field-errors">{{ $message }}</div>@enderror
                    </div>

                    <div class="input-field">
                        <input id="sucursal_direccion" type="text" name="direccion" value="{{ old('direccion', $editingBranch?->direccion) }}">
                        <label for="sucursal_direccion" class="active">Dirección</label>
                        @error('direccion')<div class="field-errors">{{ $message }}</div>@enderror
                    </div>

                    <div class="input-field">
                        <input id="sucursal_telefono" type="text" name="telefono" value="{{ old('telefono', $editingBranch?->telefono) }}">
                        <label for="sucursal_telefono" class="active">Teléfono</label>
                        @error('telefono')<div class="field-errors">{{ $message }}</div>@enderror
                    </div>

                    <p style="margin:10px 0 0;">
                        <label>
                            <input type="checkbox" name="activa" value="1" @checked(old('activa', $editingBranch?->activa ?? true))>
                            <span>Sucursal activa</span>
                        </label>
                    </p>
                </form>
            </div>
        </div>

        <div class="modal-footer">
            <a href="{{ route('admin-panel.empresa.index', ['tab' => 'sucursales']) }}" class="btn-flat waves-effect">{{ $editingBranch ? 'Cancelar edición' : 'Cancelar' }}</a>
            <button type="submit" form="form-sucursal" class="btn brown darken-1 waves-effect waves-light">
                <i class="material-icons left">{{ $editingBranch ? 'save' : 'add_business' }}</i>
                {{ $editingBranch ? 'Guardar cambios' : 'Crear sucursal' }}
            </button>
        </div>
    </div>
@endsection
