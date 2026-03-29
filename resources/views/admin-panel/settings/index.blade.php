@extends('layouts.admin-panel')

@section('title', 'Configuración')
@section('header_title', 'Configuración')

@push('styles')
    <style>
        .cfg-page { display: grid; gap: 14px; }
        .cfg-hero.card, .cfg-toolbar.card, .cfg-section.card, .cfg-actions.card { border-radius: var(--ui-radius-lg); border: 1px solid var(--ui-border); box-shadow: var(--ui-shadow-card); }
        .cfg-hero .card-content { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
        .cfg-hero-title { margin: 0; font-size: 1.25rem; font-weight: 900; color: var(--ui-text); }
        .cfg-hero-subtitle { margin: 6px 0 0; color: var(--ui-text-soft); font-size: .95rem; line-height: 1.45; max-width: 780px; }
        .cfg-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .cfg-chip { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 6px 10px; font-size: 12px; line-height: 1; border: 1px solid var(--ui-border); background: rgba(255,255,255,.82); color: #475467; font-weight: 800; }
        .cfg-chip i.material-icons { font-size: 16px; }
        .cfg-toolbar .card-content { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; }
        .cfg-toolbar form { margin: 0; flex: 1 1 420px; }
        .cfg-toolbar .row { margin-bottom: 0; }
        .cfg-toolbar .input-field { margin-top: 0; margin-bottom: 0; }
        .cfg-toolbar .btn { margin-top: 6px; }
        .cfg-toolbar-side { flex: 1 1 260px; display: grid; gap: 10px; }
        .cfg-toolbar-note { padding: 12px 14px; border-radius: 16px; border: 1px solid var(--ui-border); background: rgba(255,255,255,.7); color: var(--ui-text); font-size: 13px; line-height: 1.45; }
        .cfg-section-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 10px; }
        .cfg-section-title { margin: 0; font-size: 1.05rem; font-weight: 900; color: var(--ui-text); }
        .cfg-section-subtitle { margin: 4px 0 0; color: var(--ui-text-soft); font-size: .9rem; line-height: 1.4; }
        .cfg-options { border: 1px solid var(--ui-border); border-radius: 18px; overflow: hidden; background: rgba(255,255,255,.72); }
        .cfg-option-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 16px 16px; border-bottom: 1px solid var(--ui-divider); }
        .cfg-option-row:last-child { border-bottom: none; }
        .cfg-option-copy { flex: 1 1 auto; min-width: 0; }
        .cfg-option-label { margin: 0; font-size: .98rem; font-weight: 900; color: var(--ui-text); line-height: 1.25; }
        .cfg-option-help { margin-top: 4px; color: var(--ui-text-soft); font-size: .87rem; line-height: 1.45; }
        .cfg-option-meta { margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; }
        .cfg-option-control { flex: 0 0 auto; min-width: 130px; display: flex; justify-content: flex-end; align-items: center; }
        .cfg-option-control .switch label { color: var(--ui-text-soft); font-weight: 800; font-size: 13px; }
        .cfg-option-control .switch label input[type=checkbox]:checked + .lever { background-color: rgba(0, 150, 136, 0.35); }
        .cfg-option-control .switch label input[type=checkbox]:checked + .lever:after { background-color: #009688; }
        .cfg-actions .card-content { display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap; }
        .cfg-actions-note { color: var(--ui-text-soft); font-size: 13px; line-height: 1.45; }
        .cfg-empty { margin: 0; border-radius: 12px; }
        @media (max-width: 800px) {
            .cfg-option-row { flex-direction: column; align-items: stretch; }
            .cfg-option-control { width: 100%; min-width: 0; justify-content: flex-start; }
            .cfg-actions .card-content { align-items: stretch; }
            .cfg-actions .btn { width: 100%; }
        }
    </style>
@endpush

@section('content')
    <div class="cfg-page">
        <div class="card cfg-hero">
            <div class="card-content">
                <div>
                    <h3 class="cfg-hero-title">Configuración operativa por sucursal</h3>
                    <p class="cfg-hero-subtitle">Definí qué reglas aplica el POS en cada sucursal. La estructura está preparada para sumar más módulos y opciones sin cambiar el layout.</p>
                </div>
                <div class="cfg-hero-badges">
                    <span class="cfg-chip"><i class="material-icons">store</i>Por sucursal</span>
                    <span class="cfg-chip"><i class="material-icons">tune</i>Extensible</span>
                </div>
            </div>
        </div>

        @if ($branches->isEmpty())
            <div class="card cfg-toolbar">
                <div class="card-content">
                    <div class="card-panel amber lighten-4 amber-text text-darken-4 cfg-empty">No hay sucursales activas para configurar.</div>
                </div>
            </div>
        @else
            <div class="card cfg-toolbar">
                <div class="card-content">
                    <form method="GET" action="{{ route('admin-panel.settings.index') }}">
                        <div class="row">
                            <div class="input-field col s12 m8">
                                <select name="sucursal">
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected($selectedBranch?->id === $branch->id)>{{ $branch->nombre }}</option>
                                    @endforeach
                                </select>
                                <label>Sucursal a configurar</label>
                            </div>
                            <div class="col s12 m4" style="display:flex; align-items:flex-end;">
                                <button class="btn waves-effect waves-light" type="submit" style="width:100%;">
                                    <i class="material-icons left">filter_alt</i>Cargar configuración
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="cfg-toolbar-side">
                        <div class="cfg-toolbar-note"><b>Sucursal seleccionada:</b> {{ $selectedBranch?->nombre ?? '-' }}</div>
                        <div class="cfg-toolbar-note">Las opciones mostradas impactan en el POS de esa sucursal. Si una opción no fue configurada, se usa el valor global como fallback.</div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin-panel.settings.update') }}" style="margin:0;">
                @csrf
                @method('PUT')
                <input type="hidden" name="sucursal_id" value="{{ $selectedBranch?->id }}">

                @foreach ($settingsSections as $section)
                    <div class="card cfg-section">
                        <div class="card-content">
                            <div class="cfg-section-head">
                                <div>
                                    <h4 class="cfg-section-title">{{ $section['title'] }}</h4>
                                    <p class="cfg-section-subtitle">{{ $section['subtitle'] }}</p>
                                </div>
                                <span class="cfg-chip"><i class="material-icons">list_alt</i>{{ count($section['options']) }} ajuste{{ count($section['options']) === 1 ? '' : 's' }}</span>
                            </div>

                            <div class="cfg-options">
                                @forelse ($section['options'] as $option)
                                    <div class="cfg-option-row">
                                        <div class="cfg-option-copy">
                                            <p class="cfg-option-label">{{ $option['label'] }}</p>
                                            <div class="cfg-option-help">{{ $option['help_text'] }}</div>
                                            <div class="cfg-option-meta">
                                                @if ($option['source'] === 'sucursal')
                                                    <span class="cfg-chip" style="background:rgba(0,150,136,.08); color:#00695c; border-color:rgba(0,150,136,.18);"><i class="material-icons">check_circle</i>Configurado en sucursal</span>
                                                @else
                                                    <span class="cfg-chip" style="background:rgba(121,85,72,.06); color:#6d4c41;"><i class="material-icons">account_tree</i>Heredado del global</span>
                                                @endif
                                            </div>
                                            @error($option['name'])<div class="red-text text-darken-2" style="font-size:12px; margin-top:6px;">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="cfg-option-control">
                                            <div class="switch">
                                                <label>
                                                    No
                                                    <input type="hidden" name="{{ $option['name'] }}" value="0">
                                                    <input type="checkbox" name="{{ $option['name'] }}" value="1" @checked(old($option['name'], $option['value']))>
                                                    <span class="lever"></span>
                                                    Sí
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="cfg-option-row">
                                        <div class="cfg-option-copy">
                                            <p class="cfg-option-label">Sin opciones cargadas</p>
                                            <div class="cfg-option-help">Esta sección no tiene configuraciones disponibles todavía.</div>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="card cfg-actions">
                    <div class="card-content">
                        <div class="cfg-actions-note">Guarda los cambios para la sucursal <b>{{ $selectedBranch?->nombre }}</b>.</div>
                        <button class="btn waves-effect waves-light" type="submit">
                            <i class="material-icons left">save</i>Guardar configuración
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection
