@extends('layouts.admin-panel')

@section('title', 'Gestión de Usuarios')
@section('header_title', 'Usuarios')

@php
    $selectedRoleIds = collect(old('role_ids', $editUser?->roles?->pluck('id')->all() ?? []))
        ->map(fn ($value) => (string) $value)
        ->all();
    $selectedPermissionIds = collect(old('permission_ids', $editRole?->permissions?->pluck('id')->all() ?? []))
        ->map(fn ($value) => (string) $value)
        ->all();
    $allPermissions = collect($permissionOptions)->flatten(1);
    $formContext = old('form_context');
    $openUserModal = $tab === 'usuarios' && (request()->query('new_user') === '1' || $editUser || $formContext === 'user');
    $openPasswordModal = $tab === 'usuarios' && ($passwordUser || $formContext === 'password');
    $openRoleModal = $tab === 'roles' && (request()->query('new_role') === '1' || $editRole || $formContext === 'role');
@endphp

@push('styles')
    <style>
        .users-admin-card { border-radius: var(--ui-radius-lg); }
        .users-admin-card .card-content { padding-top: 10px; }
        .soft-panel {
            border: 1px solid var(--ui-border);
            border-radius: var(--ui-radius-md);
            padding: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92) 0%, rgba(243, 248, 252, 0.94) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 10px 24px rgba(15, 23, 42, 0.06);
        }
        .soft-panel h6 { margin: 0 0 14px; font-weight: 900; color: var(--ui-text); }
        .table-wrap { overflow: auto; }
        .compact-table td, .compact-table th {
            padding: 10px 8px;
            line-height: 1.35;
            vertical-align: top;
        }
        .compact-table td {
            white-space: normal;
            word-break: break-word;
        }
        .role-chip {
            display: inline-block;
            margin: 2px 6px 2px 0;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eef4f8;
            color: #344054;
            border: 1px solid var(--ui-border);
            font-size: 12px;
            font-weight: 800;
        }
        .perm-list {
            margin: 0;
            padding-left: 18px;
            color: var(--ui-text-soft);
            font-size: 12px;
            line-height: 1.45;
        }
        .perm-list li { margin-bottom: 2px; }
        .inline-form { display: inline; }
        .field-errors { color: #c62828; font-size: 12px; margin-top: 4px; }
        .form-help { color: #6b7280; font-size: 12px; margin-top: 4px; }
        .subtle { color: #6b7280; font-size: 13px; }
        .multi-tools {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 6px 0 8px;
        }
        .multi-tools .btn-flat {
            height: 32px;
            line-height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            background: #f8fafc;
            color: #475467;
            text-transform: none;
            letter-spacing: 0;
            font-weight: 800;
        }
        .multi-tools .btn-flat:hover { background: #f2f5f9; }
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }
        .multi-select-shell {
            border: 1px solid var(--ui-border);
            border-radius: 18px;
            background: rgba(255,255,255,.72);
            padding: 12px;
            margin-top: 4px;
        }
        .multi-select-shell .multi-tools { margin-top: 0; }
        .checklist-box {
            border: 1px solid var(--ui-border);
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
            padding: 10px;
            overflow: auto;
            min-height: 180px;
            max-height: 240px;
        }
        .checklist-box.permissions-box {
            min-height: 320px;
            height: calc(100vh - 360px);
            max-height: 500px;
        }
        .checklist-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 12px;
            color: var(--ui-text);
            cursor: pointer;
            line-height: 1.3;
        }
        .checklist-item:hover { background: rgba(226, 232, 240, 0.62); }
        .checklist-item input[type="checkbox"] {
            margin: 2px 0 0;
            position: static;
            opacity: 1;
            pointer-events: auto;
            width: 16px;
            height: 16px;
        }
        .checklist-item input[type="checkbox"] + span {
            display: block !important;
            height: auto !important;
            min-height: 0 !important;
            white-space: normal !important;
            word-break: break-word;
            overflow: visible !important;
            font-size: 13px !important;
            line-height: 1.35 !important;
            flex: 1 1 auto;
            min-width: 0;
            padding-left: 0 !important;
            color: var(--ui-text) !important;
        }
        .checklist-item input[type="checkbox"] + span::before,
        .checklist-item input[type="checkbox"] + span::after {
            display: none !important;
        }
        .checklist-empty {
            padding: 8px;
            color: #6b7280;
            font-size: 13px;
        }
        .modal .modal-content .input-field { margin-bottom: 14px; }
        .toolbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .modal.modal-fixed-footer {
            width: min(820px, 94vw) !important;
            left: 50% !important;
            right: auto !important;
            margin: 0 !important;
            transform: translateX(-50%) !important;
            max-height: 90vh;
            border-radius: 14px;
        }
        .modal.modal-fixed-footer .modal-content { width: 100%; }
        @media (max-width: 992px) {
            .soft-panel { margin-bottom: 14px; }
        }
    </style>
@endpush

@section('content')
    <div class="card users-admin-card">
        <div class="card-content">
            <div class="toolbar-row">
                <div>
                    <span class="card-title" style="margin-bottom:4px;">Gestión de Usuarios y Roles</span>
                    <div class="subtle">
                        Asigná sucursal y roles al usuario desde la misma pestaña. Los roles se definen en la pestaña "Roles".
                    </div>
                </div>

                @if ($tab === 'roles')
                    <a href="{{ route('admin-panel.users.index', ['tab' => 'roles', 'new_role' => 1]) }}" class="btn brown darken-1 waves-effect waves-light">
                        <i class="material-icons left">security</i>Crear rol
                    </a>
                @else
                    <a href="{{ route('admin-panel.users.index', ['tab' => 'usuarios', 'new_user' => 1]) }}" class="btn brown darken-1 waves-effect waves-light">
                        <i class="material-icons left">person_add</i>Crear usuario
                    </a>
                @endif
            </div>

            <div class="card" style="margin:0 0 14px 0;">
                <div class="card-content" style="padding:10px 16px;">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a class="btn {{ $tab === 'usuarios' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect" href="{{ route('admin-panel.users.index', ['tab' => 'usuarios']) }}">
                            <i class="material-icons left">people</i>Usuarios
                        </a>
                        <a class="btn {{ $tab === 'roles' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect" href="{{ route('admin-panel.users.index', ['tab' => 'roles']) }}">
                            <i class="material-icons left">security</i>Roles
                        </a>
                    </div>
                </div>
            </div>

            <div id="tab-usuarios" @if ($tab !== 'usuarios') style="display:none;" @endif>
                <div class="row">
                    <div class="col s12 l10 offset-l1">
                        <div class="soft-panel">
                            <h6>Usuarios cargados</h6>
                            <div class="table-wrap">
                                <table class="striped compact-table responsive-stack-table">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Sucursal</th>
                                            <th>Roles</th>
                                            <th>Estado</th>
                                            <th style="width:220px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($users as $user)
                                            <tr>
                                                <td data-label="Usuario">
                                                    <div style="font-weight:600;">{{ $user->username }}</div>
                                                    <div class="subtle">
                                                        @if ($user->first_name || $user->last_name)
                                                            {{ trim($user->first_name . ' ' . $user->last_name) }}
                                                        @else
                                                            Sin nombre cargado
                                                        @endif
                                                        @if ($user->email)
                                                            <br>{{ $user->email }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td data-label="Sucursal">
                                                    @if ($user->panelProfile?->sucursal)
                                                        {{ $user->panelProfile->sucursal->nombre }}
                                                    @else
                                                        <span class="subtle">Sin asignar</span>
                                                    @endif
                                                </td>
                                                <td data-label="Roles">
                                                    @forelse ($user->roles as $role)
                                                        <span class="role-chip">{{ $role->name }}</span>
                                                    @empty
                                                        <span class="subtle">Sin roles</span>
                                                    @endforelse
                                                </td>
                                                <td data-label="Estado">
                                                    @if ($user->is_active)
                                                        <span class="new badge green" data-badge-caption="Activo"></span>
                                                    @else
                                                        <span class="new badge red" data-badge-caption="Inactivo"></span>
                                                    @endif
                                                </td>
                                                <td data-label="Acciones">
                                                    <div class="actions-cell">
                                                        <a href="{{ route('admin-panel.users.index', ['tab' => 'usuarios', 'edit_user' => $user->id]) }}" class="btn-flat waves-effect" title="Editar usuario">
                                                            <i class="material-icons">edit</i>
                                                        </a>
                                                        <a href="{{ route('admin-panel.users.index', ['tab' => 'usuarios', 'change_password_user' => $user->id]) }}" class="btn-flat waves-effect" title="Cambiar contraseña">
                                                            <i class="material-icons">vpn_key</i>
                                                        </a>
                                                        <form method="POST" action="{{ route('admin-panel.users.toggle', $user) }}" class="inline-form">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn-flat waves-effect" title="{{ $user->is_active ? 'Desactivar' : 'Activar' }}">
                                                                <i class="material-icons">{{ $user->is_active ? 'person_off' : 'check_circle' }}</i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="subtle">No hay usuarios cargados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-roles" @if ($tab !== 'roles') style="display:none;" @endif>
                <div class="row">
                    <div class="col s12 l10 offset-l1">
                        <div class="soft-panel">
                            <h6>Roles existentes</h6>
                            <div class="table-wrap">
                                <table class="striped compact-table responsive-stack-table">
                                    <thead>
                                        <tr>
                                            <th>Rol</th>
                                            <th>Usuarios</th>
                                            <th>Permisos</th>
                                            <th style="width:180px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($roles as $role)
                                            <tr>
                                                <td data-label="Rol" style="font-weight:600;">{{ $role->name }}</td>
                                                <td data-label="Usuarios">{{ $role->users_count }}</td>
                                                <td data-label="Permisos">
                                                    <div style="font-weight:600; margin-bottom:4px;">{{ $role->permissions->count() }} permisos</div>
                                                    <ul class="perm-list">
                                                        @forelse ($role->permissions->take(4) as $permission)
                                                            <li>{{ $permission->name }}</li>
                                                        @empty
                                                            <li>Sin permisos asignados</li>
                                                        @endforelse
                                                        @if ($role->permissions->count() > 4)
                                                            <li>+{{ $role->permissions->count() - 4 }} más</li>
                                                        @endif
                                                    </ul>
                                                </td>
                                                <td data-label="Acciones">
                                                    <div class="actions-cell">
                                                        <a href="{{ route('admin-panel.users.index', ['tab' => 'roles', 'edit_role' => $role->id]) }}" class="btn-flat waves-effect" title="Editar rol">
                                                            <i class="material-icons">edit</i>
                                                        </a>
                                                        <form method="POST" action="{{ route('admin-panel.roles.destroy', $role) }}" class="inline-form" onsubmit="return confirm('¿Eliminar el rol {{ addslashes($role->name) }}?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn-flat red-text waves-effect" title="Eliminar rol">
                                                                <i class="material-icons">delete</i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="subtle">No hay roles creados.</td>
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

    <div id="modal-crear-usuario" class="modal modal-fixed-footer" @if ($openUserModal) data-auto-open="true" @endif>
        <div class="modal-content">
            <div class="admin-modal-head">
                <h5 class="admin-modal-title">{{ $editUser ? 'Editar usuario' : 'Crear usuario' }}</h5>
                <p class="admin-modal-subtitle">
                    @if ($editUser)
                        Actualizá datos, sucursal y roles de <strong>{{ $editUser->username }}</strong>.
                    @else
                        Asigná sucursal y roles desde el alta.
                    @endif
                </p>
            </div>

            <div class="admin-modal-body">
                <form id="form-crear-usuario" method="POST" action="{{ $editUser ? route('admin-panel.users.update', $editUser) : route('admin-panel.users.store') }}">
                    @csrf
                    @if ($editUser)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="form_context" value="user">
                    <input type="hidden" name="is_active" value="0">

                    <div class="input-field">
                        <input id="user_username" type="text" name="username" value="{{ old('username', $editUser?->username) }}" required>
                        <label for="user_username" class="active">Usuario</label>
                        @error('username')<div class="field-errors">{{ $message }}</div>@enderror
                    </div>

                    <div class="row" style="margin-bottom:0;">
                        <div class="col s12 m6">
                            <div class="input-field">
                                <input id="user_first_name" type="text" name="first_name" value="{{ old('first_name', $editUser?->first_name) }}">
                                <label for="user_first_name" class="active">Nombre</label>
                                @error('first_name')<div class="field-errors">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col s12 m6">
                            <div class="input-field">
                                <input id="user_last_name" type="text" name="last_name" value="{{ old('last_name', $editUser?->last_name) }}">
                                <label for="user_last_name" class="active">Apellido</label>
                                @error('last_name')<div class="field-errors">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="input-field">
                        <input id="user_email" type="email" name="email" value="{{ old('email', $editUser?->email) }}" required>
                        <label for="user_email" class="active">Email</label>
                        @error('email')<div class="field-errors">{{ $message }}</div>@enderror
                    </div>

                    @if (! $editUser)
                        <div class="row" style="margin-bottom:0;">
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <input id="user_password" type="password" name="password" autocomplete="new-password">
                                    <label for="user_password" class="active">Contraseña</label>
                                    @error('password')<div class="field-errors">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col s12 m6">
                                <div class="input-field">
                                    <input id="user_password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                                    <label for="user_password_confirmation" class="active">Repetir contraseña</label>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div style="margin: 6px 0 12px;">
                        <label style="font-weight:800; color:var(--ui-text);">Sucursal</label>
                        <select id="user_sucursal_id" name="sucursal_id">
                            <option value="">Sin sucursal fija</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('sucursal_id', $editUser?->panelProfile?->sucursal_id) === (string) $branch->id)>{{ $branch->nombre }}</option>
                            @endforeach
                        </select>
                        @error('sucursal_id')<div class="field-errors">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin: 8px 0 12px;">
                        <label style="font-weight:800; color:var(--ui-text);">Roles</label>
                        <div class="multi-select-shell">
                            <div class="multi-tools">
                                <button type="button" class="btn-flat waves-effect js-multi-toggle" data-target="user-roles-checklist" data-mode="all">
                                    Seleccionar todos
                                </button>
                                <button type="button" class="btn-flat waves-effect js-multi-toggle" data-target="user-roles-checklist" data-mode="none">
                                    Quitar todos
                                </button>
                            </div>
                            <div id="user-roles-checklist" class="checklist-box">
                                @forelse ($roleOptions as $roleOption)
                                    <label class="checklist-item">
                                        <input type="checkbox" name="role_ids[]" value="{{ $roleOption->id }}" @checked(in_array((string) $roleOption->id, $selectedRoleIds, true))>
                                        <span>{{ $roleOption->name }}</span>
                                    </label>
                                @empty
                                    <div class="checklist-empty">No hay roles creados.</div>
                                @endforelse
                            </div>
                        </div>
                        @error('role_ids')<div class="field-errors">{{ $message }}</div>@enderror
                        @error('role_ids.*')<div class="field-errors">{{ $message }}</div>@enderror
                        <div class="form-help">Marcá roles con checkboxes. Podés seleccionar o quitar todos con un clic.</div>
                    </div>

                    <p style="margin: 10px 0 0;">
                        <label>
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editUser?->is_active ?? true))>
                            <span>Usuario activo</span>
                        </label>
                    </p>
                </form>
            </div>
        </div>
        <div class="modal-footer">
            <a href="{{ route('admin-panel.users.index', ['tab' => 'usuarios']) }}" class="btn-flat waves-effect">
                {{ $editUser ? 'Cancelar edición' : 'Cancelar' }}
            </a>
            <button type="submit" form="form-crear-usuario" class="btn brown darken-1 waves-effect waves-light">
                <i class="material-icons left">{{ $editUser ? 'save' : 'person_add' }}</i>
                {{ $editUser ? 'Guardar cambios' : 'Crear usuario' }}
            </button>
        </div>
    </div>

    @if ($passwordUser)
        <div id="modal-cambiar-password" class="modal modal-fixed-footer" @if ($openPasswordModal) data-auto-open="true" @endif>
            <div class="modal-content">
                <div class="admin-modal-head">
                    <h5 class="admin-modal-title">Cambiar contraseña</h5>
                    <p class="admin-modal-subtitle">Definí una nueva contraseña para <strong>{{ $passwordUser->username }}</strong>.</p>
                </div>

                <div class="admin-modal-body">
                    <form id="form-cambiar-password" method="POST" action="{{ route('admin-panel.users.password', $passwordUser) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="form_context" value="password">

                        <div class="input-field">
                            <input id="password_new" type="password" name="password" autocomplete="new-password">
                            <label for="password_new" class="active">Nueva contraseña</label>
                            @error('password')<div class="field-errors">{{ $message }}</div>@enderror
                        </div>

                        <div class="input-field">
                            <input id="password_new_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                            <label for="password_new_confirmation" class="active">Repetir nueva contraseña</label>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('admin-panel.users.index', ['tab' => 'usuarios']) }}" class="btn-flat waves-effect">Cancelar</a>
                <button type="submit" form="form-cambiar-password" class="btn brown darken-1 waves-effect waves-light">
                    <i class="material-icons left">vpn_key</i>Actualizar contraseña
                </button>
            </div>
        </div>
    @endif

    <div id="modal-crear-rol" class="modal modal-fixed-footer" @if ($openRoleModal) data-auto-open="true" @endif>
        <div class="modal-content">
            <div class="admin-modal-head">
                <h5 class="admin-modal-title">{{ $editRole ? 'Editar rol' : 'Crear rol' }}</h5>
                <p class="admin-modal-subtitle">
                    @if ($editRole)
                        Actualizá permisos del rol <strong>{{ $editRole->name }}</strong>.
                    @else
                        Definí un rol y los permisos que va a otorgar.
                    @endif
                </p>
            </div>

            <div class="admin-modal-body">
                <form id="form-crear-rol" method="POST" action="{{ $editRole ? route('admin-panel.roles.update', $editRole) : route('admin-panel.roles.store') }}">
                    @csrf
                    @if ($editRole)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="form_context" value="role">

                    <div class="input-field">
                        <input id="role_name" type="text" name="name" value="{{ old('name', $editRole?->name) }}" required>
                        <label for="role_name" class="active">Nombre del rol</label>
                        @error('name')<div class="field-errors">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin: 8px 0 12px;">
                        <label style="font-weight:800; color:var(--ui-text);">Permisos del rol</label>
                        <div class="multi-select-shell">
                            <div class="multi-tools">
                                <button type="button" class="btn-flat waves-effect js-multi-toggle" data-target="role-permissions-checklist" data-mode="all">
                                    Seleccionar todos
                                </button>
                                <button type="button" class="btn-flat waves-effect js-multi-toggle" data-target="role-permissions-checklist" data-mode="none">
                                    Quitar todos
                                </button>
                            </div>
                            <div id="role-permissions-checklist" class="checklist-box permissions-box">
                                @forelse ($allPermissions as $permission)
                                    <label class="checklist-item">
                                        <input type="checkbox" name="permission_ids[]" value="{{ $permission['id'] }}" @checked(in_array((string) $permission['id'], $selectedPermissionIds, true))>
                                        <span>{{ $permission['label'] }}</span>
                                    </label>
                                @empty
                                    <div class="checklist-empty">No hay permisos disponibles.</div>
                                @endforelse
                            </div>
                        </div>
                        @error('permission_ids')<div class="field-errors">{{ $message }}</div>@enderror
                        @error('permission_ids.*')<div class="field-errors">{{ $message }}</div>@enderror
                        <div class="form-help">La lista ocupa más alto del modal para facilitar selección masiva y revisión.</div>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-footer">
            <a href="{{ route('admin-panel.users.index', ['tab' => 'roles']) }}" class="btn-flat waves-effect">
                {{ $editRole ? 'Cancelar edición' : 'Cancelar' }}
            </a>
            <button type="submit" form="form-crear-rol" class="btn brown darken-1 waves-effect waves-light">
                <i class="material-icons left">{{ $editRole ? 'save' : 'security' }}</i>
                {{ $editRole ? 'Guardar rol' : 'Crear rol' }}
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-multi-toggle').forEach(function (btn) {
                if (btn.dataset.bound === '1') {
                    return;
                }

                btn.dataset.bound = '1';
                btn.addEventListener('click', function () {
                    const targetId = btn.getAttribute('data-target');
                    const mode = btn.getAttribute('data-mode');
                    const scope = targetId ? document.getElementById(targetId) : null;

                    if (!scope) {
                        return;
                    }

                    const checks = scope.querySelectorAll('input[type="checkbox"]');
                    checks.forEach(function (checkbox) {
                        checkbox.checked = mode === 'all';
                    });
                });
            });
        });
    </script>
@endpush
