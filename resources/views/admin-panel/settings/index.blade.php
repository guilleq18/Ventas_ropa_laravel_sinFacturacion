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
        .cfg-hero-badges, .cfg-tab-list, .cfg-fiscal-kpis, .cfg-option-meta, .cfg-credential-actions, .cfg-copy-row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .cfg-chip { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 6px 10px; font-size: 12px; line-height: 1; border: 1px solid var(--ui-border); background: rgba(255,255,255,.82); color: #475467; font-weight: 800; }
        .cfg-chip i.material-icons { font-size: 16px; }
        .cfg-tabs .card-content { padding: 10px 16px; }
        .cfg-tab-btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; padding: 0 16px; height: 40px; font-size: 13px; font-weight: 900; letter-spacing: .02em; }
        .cfg-toolbar .card-content, .cfg-actions .card-content { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; }
        .cfg-toolbar form { margin: 0; flex: 1 1 420px; }
        .cfg-toolbar .row { margin-bottom: 0; }
        .cfg-toolbar-side { flex: 1 1 260px; display: grid; gap: 10px; }
        .cfg-toolbar-note, .cfg-note, .cfg-box { padding: 12px 14px; border-radius: 16px; border: 1px solid var(--ui-border); background: rgba(255,255,255,.72); color: var(--ui-text-soft); font-size: 13px; line-height: 1.45; }
        .cfg-section-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 10px; }
        .cfg-section-title { margin: 0; font-size: 1.05rem; font-weight: 900; color: var(--ui-text); }
        .cfg-section-subtitle { margin: 4px 0 0; color: var(--ui-text-soft); font-size: .9rem; line-height: 1.4; }
        .cfg-options { border: 1px solid var(--ui-border); border-radius: 18px; overflow: hidden; background: rgba(255,255,255,.72); }
        .cfg-option-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 16px; border-bottom: 1px solid var(--ui-divider); }
        .cfg-option-row:last-child { border-bottom: none; }
        .cfg-option-copy { flex: 1 1 auto; min-width: 0; }
        .cfg-option-label { margin: 0; font-size: .98rem; font-weight: 900; color: var(--ui-text); line-height: 1.25; }
        .cfg-option-help { margin-top: 4px; color: var(--ui-text-soft); font-size: .87rem; line-height: 1.45; }
        .cfg-option-control { flex: 0 0 auto; min-width: 130px; display: flex; justify-content: flex-end; align-items: center; }
        .cfg-option-control .switch label { color: var(--ui-text-soft); font-weight: 800; font-size: 13px; }
        .cfg-option-control .switch label input[type=checkbox]:checked + .lever { background-color: rgba(0, 150, 136, 0.35); }
        .cfg-option-control .switch label input[type=checkbox]:checked + .lever:after { background-color: #009688; }
        .cfg-empty { margin: 0; border-radius: 12px; }
        .cfg-grid-2, .cfg-grid-3, .cfg-result-grid { display: grid; gap: 14px; }
        .cfg-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .cfg-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .cfg-field { display: grid; gap: 6px; }
        .cfg-field label { color: var(--ui-text-soft); font-size: 12px; line-height: 1.2; font-weight: 900; letter-spacing: .03em; }
        .cfg-field .browser-default, .cfg-field input[type="number"], .cfg-field input[type="text"], .cfg-field input[type="file"], .cfg-field textarea { width: 100%; margin: 0; border-radius: 18px; border: 1px solid var(--ui-border); background: #fff; color: var(--ui-text); box-shadow: none; box-sizing: border-box; font: inherit; }
        .cfg-field .browser-default, .cfg-field input[type="number"], .cfg-field input[type="text"], .cfg-field input[type="file"] { height: 50px; padding: 0 16px; }
        .cfg-field input[type="file"] { padding-top: 12px; }
        .cfg-field textarea { min-height: 150px; padding: 14px 16px; resize: vertical; }
        .cfg-field .browser-default:focus, .cfg-field input[type="number"]:focus, .cfg-field input[type="text"]:focus, .cfg-field input[type="file"]:focus, .cfg-field textarea:focus { border-color: #8ea4bd; outline: none; box-shadow: 0 0 0 3px rgba(41, 98, 255, .08); }
        .cfg-list { margin: 0; padding-left: 18px; color: var(--ui-text-soft); }
        .cfg-list li { margin-bottom: 6px; }
        .cfg-issues { margin: 0; padding-left: 18px; color: #b42318; }
        .cfg-code { width: 100%; min-height: 220px; border-radius: 18px; border: 1px solid var(--ui-border); background: #182032; color: #eef6ff; padding: 16px; box-sizing: border-box; font: 12px/1.55 Consolas, Monaco, monospace; white-space: pre-wrap; word-break: break-word; }
        .cfg-mono { font-family: Consolas, Monaco, monospace; word-break: break-word; }
        .cfg-error { color: #b42318; font-size: 12px; margin-top: 2px; }
        .cfg-result-card { border: 1px solid var(--ui-border); border-radius: 16px; padding: 14px 16px; background: rgba(255,255,255,.74); }
        .cfg-result-card h5 { margin: 0 0 8px; font-size: .98rem; font-weight: 900; color: var(--ui-text); }
        .cfg-result-card p, .cfg-actions-note { margin: 0; color: var(--ui-text-soft); font-size: 13px; line-height: 1.45; }
        @media (max-width: 1040px) { .cfg-grid-3, .cfg-result-grid { grid-template-columns: 1fr; } }
        @media (max-width: 800px) {
            .cfg-option-row { flex-direction: column; align-items: stretch; }
            .cfg-option-control { width: 100%; min-width: 0; justify-content: flex-start; }
            .cfg-grid-2 { grid-template-columns: 1fr; }
            .cfg-tab-btn, .cfg-actions .btn, .cfg-credential-actions .btn { width: 100%; justify-content: center; }
            .cfg-actions .card-content { align-items: stretch; }
        }
    </style>
@endpush

@section('content')
    @php
        $tab = old('tab', $tab ?? 'ventas');
        $facturacionTab = old('facturacion_tab', $facturacionTab ?? 'configuracion');
        $money = fn ($value) => '$' . number_format((float) $value, 2, ',', '.');
        $hasBranches = $branches->isNotEmpty();
        $fiscalFacturacionHabilitada = old('fiscal_facturacion_habilitada', $fiscalUi['facturacion_habilitada'] ?? false);
        $fiscalRequiereReceptor = old('fiscal_requiere_receptor_en_todas', $fiscalUi['requiere_receptor_en_todas'] ?? false);
        $arcaCredentials = $arcaCredentials ?? [];
        $arcaValidation = session('arca_validation');
        $arcaProbe = session('arca_probe');
        $companyCuitDigits = preg_replace('/\D+/', '', (string) data_get($companyData, 'cuit', '')) ?: '';
        $defaultRepresentedCuit = old('arca_represented_cuit', data_get($arcaCredentials, 'represented_cuit.value') ?: $companyCuitDigits);
        $rawDefaultAlias = old('arca_alias', data_get($arcaCredentials, 'alias.value') ?: 'tiendaropahomo');
        $defaultAlias = preg_replace('/[^A-Za-z0-9]+/', '', (string) $rawDefaultAlias) ?: 'tiendaropahomo';
        $defaultOrganization = old('arca_organization', data_get($arcaCredentials, 'organization.value') ?: (data_get($companyData, 'razon_social') ?: data_get($companyData, 'nombre') ?: config('app.name', 'Laravel')));
        $defaultCommonName = old('arca_common_name', data_get($arcaCredentials, 'common_name.value') ?: \Illuminate\Support\Str::slug(config('app.name', 'Laravel'), '_'));
        $sourceLabel = fn (?string $source) => match ($source) { 'panel' => 'panel', 'env' => 'env', default => 'sin definir' };
        $tabRouteParams = fn (string $targetTab) => array_filter([
            'tab' => $targetTab,
            'sucursal' => $selectedBranch?->id,
        ], fn ($value) => $value !== null && $value !== '');
        $facturacionTabRouteParams = fn (string $targetSubtab) => array_filter([
            'tab' => 'facturacion',
            'facturacion_tab' => $targetSubtab,
            'sucursal' => $selectedBranch?->id,
            'documentos_sucursal' => $targetSubtab === 'comprobantes' ? ($authorizedDocumentsFilters['sucursal'] ?? null) : null,
            'documentos_from' => $targetSubtab === 'comprobantes' ? ($authorizedDocumentsFilters['from'] ?? null) : null,
            'documentos_to' => $targetSubtab === 'comprobantes' ? ($authorizedDocumentsFilters['to'] ?? null) : null,
            'documentos_q' => $targetSubtab === 'comprobantes' ? ($authorizedDocumentsFilters['q'] ?? null) : null,
            'caea_entorno' => $targetSubtab === 'caea' ? ($caeaPeriodsFilters['entorno'] ?? null) : null,
            'caea_estado_solicitud' => $targetSubtab === 'caea' ? ($caeaPeriodsFilters['estado_solicitud'] ?? null) : null,
            'caea_estado_informacion' => $targetSubtab === 'caea' ? ($caeaPeriodsFilters['estado_informacion'] ?? null) : null,
            'caea_cuit' => $targetSubtab === 'caea' ? ($caeaPeriodsFilters['cuit'] ?? null) : null,
            'caea_q' => $targetSubtab === 'caea' ? ($caeaPeriodsFilters['q'] ?? null) : null,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp
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

        @if ($hasBranches)
            <div class="card cfg-toolbar">
                <div class="card-content">
                    <form method="GET" action="{{ route('admin-panel.settings.index') }}">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        @if ($tab === 'facturacion')
                            <input type="hidden" name="facturacion_tab" value="{{ $facturacionTab }}">
                            @if ($facturacionTab === 'comprobantes')
                                <input type="hidden" name="documentos_sucursal" value="{{ $authorizedDocumentsFilters['sucursal'] ?? '' }}">
                                <input type="hidden" name="documentos_from" value="{{ $authorizedDocumentsFilters['from'] ?? '' }}">
                                <input type="hidden" name="documentos_to" value="{{ $authorizedDocumentsFilters['to'] ?? '' }}">
                                <input type="hidden" name="documentos_q" value="{{ $authorizedDocumentsFilters['q'] ?? '' }}">
                            @endif
                            @if ($facturacionTab === 'caea')
                                <input type="hidden" name="caea_entorno" value="{{ $caeaPeriodsFilters['entorno'] ?? '' }}">
                                <input type="hidden" name="caea_estado_solicitud" value="{{ $caeaPeriodsFilters['estado_solicitud'] ?? '' }}">
                                <input type="hidden" name="caea_estado_informacion" value="{{ $caeaPeriodsFilters['estado_informacion'] ?? '' }}">
                                <input type="hidden" name="caea_cuit" value="{{ $caeaPeriodsFilters['cuit'] ?? '' }}">
                                <input type="hidden" name="caea_q" value="{{ $caeaPeriodsFilters['q'] ?? '' }}">
                            @endif
                        @endif
                        <div class="row">
                            <div class="input-field col s12 m8">
                                <select name="sucursal">
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected($selectedBranch?->id === $branch->id)>{{ $branch->nombre }}</option>
                                    @endforeach
                                </select>
                                <label>{{ $tab === 'credenciales' ? 'Sucursal para pruebas ARCA' : 'Sucursal a configurar' }}</label>
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
                        <div class="cfg-toolbar-note">
                            {{ $tab === 'credenciales'
                                ? 'Las credenciales son globales. La sucursal seleccionada solo se usa para ejecutar la prueba de homologación desde el panel.'
                                : ($tab === 'facturacion' && $facturacionTab === 'caea'
                                    ? 'Los períodos CAEA se administran por CUIT representada y quincena. La sucursal seleccionada se conserva solo como contexto de navegación.'
                                    : 'Las opciones mostradas impactan en el POS de esa sucursal. Si una opción no fue configurada, se usa el valor global como fallback.') }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card cfg-toolbar">
                <div class="card-content">
                    <div class="card-panel amber lighten-4 amber-text text-darken-4 cfg-empty">
                        No hay sucursales activas para configurar.
                        @if ($tab === 'credenciales')
                            Igual podés generar y cargar las credenciales ARCA desde esta pantalla; solo la prueba de homologación requiere una sucursal.
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="card cfg-tabs" style="margin:0;">
            <div class="card-content">
                <div class="cfg-tab-list">
                    <a class="btn {{ $tab === 'ventas' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect cfg-tab-btn" href="{{ route('admin-panel.settings.index', $tabRouteParams('ventas')) }}">
                        <i class="material-icons left">point_of_sale</i>Ventas
                    </a>
                    <a class="btn {{ $tab === 'facturacion' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect cfg-tab-btn" href="{{ route('admin-panel.settings.index', $tabRouteParams('facturacion')) }}">
                        <i class="material-icons left">receipt_long</i>Facturación electrónica
                    </a>
                    <a class="btn {{ $tab === 'credenciales' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect cfg-tab-btn" href="{{ route('admin-panel.settings.index', $tabRouteParams('credenciales')) }}">
                        <i class="material-icons left">shield</i>Credenciales ARCA
                    </a>
                </div>
            </div>
        </div>

        @if ($tab === 'ventas')
            @if (! $selectedBranch)
                <div class="card cfg-section">
                    <div class="card-content">
                        <div class="cfg-note">Necesitás al menos una sucursal activa para configurar ventas por sucursal.</div>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('admin-panel.settings.update') }}" style="margin:0;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="sucursal_id" value="{{ $selectedBranch?->id }}">
                    <input type="hidden" name="tab" value="ventas">

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
                                                @error($option['name'])<div class="cfg-error">{{ $message }}</div>@enderror
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
                            <div class="cfg-actions-note">Guarda los cambios de ventas para la sucursal <b>{{ $selectedBranch?->nombre }}</b>.</div>
                            <button class="btn waves-effect waves-light" type="submit">
                                <i class="material-icons left">save</i>Guardar configuración
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        @endif

        @if ($tab === 'credenciales')
                <div class="card cfg-section">
                    <div class="card-content">
                        <div class="cfg-section-head">
                            <div>
                                <h4 class="cfg-section-title">Credenciales ARCA</h4>
                                <p class="cfg-section-subtitle">Asistente integrado para generar la clave privada, copiar la CSR en WSASS, cargar el certificado y validar el par resultante.</p>
                            </div>
                            <span class="cfg-chip"><i class="material-icons">shield</i>Global para ARCA</span>
                        </div>

                        <div class="cfg-note">
                            <strong>Estado actual:</strong>
                            <ul class="cfg-list">
                                <li>CUIT representado: {{ data_get($arcaCredentials, 'represented_cuit.configured') ? (data_get($arcaCredentials, 'represented_cuit.masked') ?: 'configurado') : 'faltante' }} ({{ $sourceLabel(data_get($arcaCredentials, 'represented_cuit.source')) }})</li>
                                <li>Alias DN: {{ data_get($arcaCredentials, 'alias.value') ?: 'sin definir' }} ({{ $sourceLabel(data_get($arcaCredentials, 'alias.source')) }})</li>
                                <li>CSR: {{ data_get($arcaCredentials, 'csr.exists') ? 'lista para copiar' : (data_get($arcaCredentials, 'csr.configured') ? 'ruta configurada pero archivo no encontrado' : 'sin generar') }}</li>
                                <li>Certificado: {{ data_get($arcaCredentials, 'certificate.exists') ? 'ok' : (data_get($arcaCredentials, 'certificate.configured') ? 'ruta configurada pero archivo no encontrado' : 'faltante') }} ({{ $sourceLabel(data_get($arcaCredentials, 'certificate.source')) }})</li>
                                <li>Clave privada: {{ data_get($arcaCredentials, 'private_key.exists') ? 'ok' : (data_get($arcaCredentials, 'private_key.configured') ? 'ruta configurada pero archivo no encontrado' : 'faltante') }} ({{ $sourceLabel(data_get($arcaCredentials, 'private_key.source')) }})</li>
                                <li>SSL ARCA: {{ config('fiscal.arca.verify_ssl', true) ? 'verificación activa' : 'verificación desactivada por entorno' }}</li>
                            </ul>
                        </div>

                        <div class="cfg-grid-3" style="margin-top:14px;">
                            <div class="cfg-box">
                                <h5 style="margin:0 0 8px; font-size:.98rem; font-weight:900; color:var(--ui-text);">1. Generar key + CSR</h5>
                                <form method="POST" action="{{ route('admin-panel.settings.arca.generate-csr') }}">
                                    @csrf
                                    <input type="hidden" name="sucursal_id" value="{{ $selectedBranch?->id }}">

                                    <div class="cfg-grid-2">
                                        <div class="cfg-field">
                                            <label for="arca_represented_cuit">CUIT representado</label>
                                            <input id="arca_represented_cuit" type="text" name="arca_represented_cuit" value="{{ $defaultRepresentedCuit }}">
                                            @error('arca_represented_cuit')<div class="cfg-error">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="cfg-field">
                                            <label for="arca_alias">Alias simbólico DN</label>
                                            <input id="arca_alias" type="text" name="arca_alias" value="{{ $defaultAlias }}" pattern="[A-Za-z0-9]+" inputmode="latin-prose">
                                            @error('arca_alias')<div class="cfg-error">{{ $message }}</div>@enderror
                                            <div>Solo letras y números, sin espacios, guiones ni guion bajo.</div>
                                        </div>
                                        <div class="cfg-field">
                                            <label for="arca_organization">Organization</label>
                                            <input id="arca_organization" type="text" name="arca_organization" value="{{ $defaultOrganization }}">
                                            @error('arca_organization')<div class="cfg-error">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="cfg-field">
                                            <label for="arca_common_name">Common Name</label>
                                            <input id="arca_common_name" type="text" name="arca_common_name" value="{{ $defaultCommonName }}">
                                            @error('arca_common_name')<div class="cfg-error">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="cfg-credential-actions" style="margin-top:10px;">
                                        <button class="btn waves-effect waves-light" type="submit">
                                            <i class="material-icons left">vpn_key</i>Generar key + CSR
                                        </button>
                                    </div>
                                </form>
                                <div style="margin-top:10px;">Si regenerás la key, el sistema te pedirá volver a cargar el certificado para evitar combinaciones inconsistentes.</div>
                            </div>

                            <div class="cfg-box">
                                <h5 style="margin:0 0 8px; font-size:.98rem; font-weight:900; color:var(--ui-text);">2. Cargar certificado</h5>
                                <form method="POST" action="{{ route('admin-panel.settings.arca.upload-certificate') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="sucursal_id" value="{{ $selectedBranch?->id }}">

                                    <div class="cfg-field">
                                        <label for="arca_certificate_file">Archivo .crt/.pem</label>
                                        <input id="arca_certificate_file" type="file" name="arca_certificate_file" accept=".crt,.pem,.cer,.txt">
                                        @error('arca_certificate_file')<div class="cfg-error">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="cfg-field" style="margin-top:10px;">
                                        <label for="arca_certificate_pem">O pegá el PEM completo</label>
                                        <textarea id="arca_certificate_pem" name="arca_certificate_pem" placeholder="-----BEGIN CERTIFICATE-----">{{ old('arca_certificate_pem') }}</textarea>
                                        @error('arca_certificate_pem')<div class="cfg-error">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="cfg-credential-actions" style="margin-top:10px;">
                                        <button class="btn waves-effect waves-light" type="submit">
                                            <i class="material-icons left">upload_file</i>Guardar certificado
                                        </button>
                                    </div>
                                </form>
                                <div style="margin-top:10px;">El sistema valida en el momento que el certificado cargado corresponda con la clave privada actual.</div>
                            </div>

                            <div class="cfg-box">
                                <h5 style="margin:0 0 8px; font-size:.98rem; font-weight:900; color:var(--ui-text);">3. Validar y probar</h5>
                                <form method="POST" action="{{ route('admin-panel.settings.arca.validate-credentials') }}">
                                    @csrf
                                    <input type="hidden" name="sucursal_id" value="{{ $selectedBranch?->id }}">
                                    <div class="cfg-credential-actions">
                                        <button class="btn grey lighten-3 black-text waves-effect" type="submit">
                                            <i class="material-icons left">fact_check</i>Validar credenciales
                                        </button>
                                    </div>
                                </form>

                                @if ($selectedBranch)
                                    <form method="POST" action="{{ route('admin-panel.settings.arca.probe') }}" style="margin-top:10px;">
                                        @csrf
                                        <input type="hidden" name="sucursal_id" value="{{ $selectedBranch->id }}">
                                        <div class="cfg-credential-actions">
                                            <button class="btn green darken-1 waves-effect" type="submit">
                                                <i class="material-icons left">cloud_done</i>Probar conexión ARCA
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <div class="cfg-note" style="margin-top:10px;">Creá o activá una sucursal para poder ejecutar la prueba de homologación desde el panel.</div>
                                @endif

                                <div style="margin-top:10px;">La prueba usa la sucursal seleccionada y ejecuta el mismo chequeo que antes se hacía solo por consola.</div>
                            </div>
                        </div>

                        @if (data_get($arcaCredentials, 'csr.exists'))
                            <div class="cfg-note" style="margin-top:14px;">
                                <strong>CSR lista para copiar en WSASS:</strong>
                                <div style="margin:8px 0 10px;">Usá el alias <span class="cfg-mono">{{ data_get($arcaCredentials, 'alias.value') ?: '-' }}</span> en “Nombre simbólico del DN” y pegá el contenido completo de abajo.</div>
                                <div class="cfg-copy-row" style="margin-bottom:10px;">
                                    <button class="btn grey lighten-3 black-text waves-effect js-copy-csr" type="button" data-copy-target="arca_csr_pem">
                                        <i class="material-icons left">content_copy</i>Copiar CSR
                                    </button>
                                    <span class="cfg-mono" id="arca_csr_copy_status" aria-live="polite"></span>
                                </div>
                                <textarea id="arca_csr_pem" class="cfg-code" readonly>{{ data_get($arcaCredentials, 'csr.pem') }}</textarea>
                                <div style="margin-top:8px;">Archivo: <span class="cfg-mono">{{ data_get($arcaCredentials, 'csr.path') }}</span></div>
                            </div>
                        @endif

                        @if ($arcaValidation || $arcaProbe)
                            <div class="cfg-result-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top:14px;">
                                @if ($arcaValidation)
                                    <div class="cfg-result-card">
                                        <h5>Validación local</h5>
                                        <p>Resultado: <strong>{{ data_get($arcaValidation, 'ok') ? 'OK' : 'Con observaciones' }}</strong></p>
                                        <p>Key/certificado: {{ data_get($arcaValidation, 'key_matches_certificate') ? 'coinciden' : 'no coinciden' }}</p>
                                        <p>CUIT representado: {{ data_get($arcaValidation, 'represented_cuit') ?: 'sin definir' }}</p>
                                        <p>CUIT del subject: {{ data_get($arcaValidation, 'subject_cuit') ?: 'no informado' }}</p>
                                        <p>Válido desde: {{ data_get($arcaValidation, 'valid_from') ?: '-' }}</p>
                                        <p>Válido hasta: {{ data_get($arcaValidation, 'valid_to') ?: '-' }}</p>
                                        <p>Certificado: <span class="cfg-mono">{{ data_get($arcaValidation, 'certificate_path') ?: '-' }}</span></p>
                                        <p>Clave privada: <span class="cfg-mono">{{ data_get($arcaValidation, 'private_key_path') ?: '-' }}</span></p>
                                        @if (data_get($arcaValidation, 'warnings'))
                                            <ul class="cfg-issues" style="margin-top:8px;">
                                                @foreach (data_get($arcaValidation, 'warnings', []) as $warning)
                                                    <li>{{ $warning }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif

                                @if ($arcaProbe)
                                    <div class="cfg-result-card">
                                        <h5>Resultado ARCA</h5>
                                        <p>Sucursal: {{ data_get($arcaProbe, 'branch.nombre') ?: ($selectedBranch?->nombre ?? '-') }}</p>
                                        <p>Entorno: {{ data_get($arcaProbe, 'environment') ?: '-' }}</p>
                                        <p>Punto de venta: {{ data_get($arcaProbe, 'point_of_sale') ?: '-' }}</p>
                                        <p>Clase sugerida: {{ data_get($arcaProbe, 'receipt_class') ?: '-' }}</p>
                                        <p>Código comprobante: {{ data_get($arcaProbe, 'receipt_code') ?: '-' }}</p>
                                        <p>Preparación fiscal: {{ data_get($arcaProbe, 'readiness.ready') ? 'OK' : 'Pendiente' }}</p>
                                        <p>WSFE FEDummy: App {{ data_get($arcaProbe, 'wsfe_dummy.app_server') ?: '-' }}, DB {{ data_get($arcaProbe, 'wsfe_dummy.db_server') ?: '-' }}, Auth {{ data_get($arcaProbe, 'wsfe_dummy.auth_server') ?: '-' }}</p>
                                        <p>WSAA expira: {{ data_get($arcaProbe, 'wsaa.expiration_time') ?: 'sin ticket' }}</p>
                                        <p>Último comprobante autorizado: {{ data_get($arcaProbe, 'last_authorized.numero') ?: 'n/d' }}</p>
                                        @if (data_get($arcaProbe, 'readiness.issues'))
                                            <ul class="cfg-issues" style="margin-top:8px;">
                                                @foreach (data_get($arcaProbe, 'readiness.issues', []) as $issue)
                                                    <li>{{ $issue }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
        @endif

        @if ($tab === 'facturacion')
            <div class="card cfg-tabs" style="margin:0;">
                <div class="card-content">
                    <div class="cfg-tab-list">
                        <a class="btn {{ $facturacionTab === 'configuracion' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect cfg-tab-btn" href="{{ route('admin-panel.settings.index', $facturacionTabRouteParams('configuracion')) }}">
                            <i class="material-icons left">settings</i>Configuración fiscal
                        </a>
                        <a class="btn {{ $facturacionTab === 'comprobantes' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect cfg-tab-btn" href="{{ route('admin-panel.settings.index', $facturacionTabRouteParams('comprobantes')) }}">
                            <i class="material-icons left">receipt_long</i>CAE emitidos
                        </a>
                        <a class="btn {{ $facturacionTab === 'caea' ? 'blue' : 'grey lighten-3 black-text' }} waves-effect cfg-tab-btn" href="{{ route('admin-panel.settings.index', $facturacionTabRouteParams('caea')) }}">
                            <i class="material-icons left">fact_check</i>Períodos CAEA
                        </a>
                    </div>
                </div>
            </div>

            @if ($facturacionTab === 'configuracion')
                @if (! $selectedBranch || ! $fiscalUi)
                    <div class="card cfg-section">
                        <div class="card-content">
                            <div class="cfg-note">Necesitás al menos una sucursal activa para configurar facturación por sucursal.</div>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin-panel.settings.update') }}" style="margin:0;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="sucursal_id" value="{{ $selectedBranch?->id }}">
                        <input type="hidden" name="tab" value="facturacion">
                        <input type="hidden" name="facturacion_tab" value="configuracion">

                        <div class="card cfg-section">
                            <div class="card-content">
                                <div class="cfg-section-head">
                                    <div>
                                        <h4 class="cfg-section-title">Facturación electrónica</h4>
                                        <p class="cfg-section-subtitle">Configuración mínima por sucursal para el circuito fiscal del POS.</p>
                                    </div>
                                    <span class="cfg-chip"><i class="material-icons">receipt_long</i>{{ $fiscalUi['mode_label'] }}</span>
                                </div>

                                <div class="cfg-grid-2">
                                    <div class="cfg-field">
                                        <label for="fiscal_modo_operacion">Modo fiscal</label>
                                        <select id="fiscal_modo_operacion" name="fiscal_modo_operacion" class="browser-default">
                                            @foreach ($fiscalUi['modes'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('fiscal_modo_operacion', $fiscalUi['modo_operacion']) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('fiscal_modo_operacion')<div class="cfg-error">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="cfg-field">
                                        <label for="fiscal_entorno">Entorno fiscal</label>
                                        <select id="fiscal_entorno" name="fiscal_entorno" class="browser-default">
                                            @foreach ($fiscalUi['environments'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('fiscal_entorno', $fiscalUi['entorno']) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('fiscal_entorno')<div class="cfg-error">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="cfg-field">
                                        <label for="fiscal_punto_venta">Punto de venta fiscal</label>
                                        <input id="fiscal_punto_venta" type="number" min="1" name="fiscal_punto_venta" value="{{ old('fiscal_punto_venta', $fiscalUi['punto_venta']) }}">
                                        @error('fiscal_punto_venta')<div class="cfg-error">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="cfg-field">
                                        <label for="fiscal_domicilio_fiscal_emision">Domicilio fiscal de emision</label>
                                        <input id="fiscal_domicilio_fiscal_emision" type="text" name="fiscal_domicilio_fiscal_emision" value="{{ old('fiscal_domicilio_fiscal_emision', $fiscalUi['domicilio_fiscal_emision']) }}">
                                        @error('fiscal_domicilio_fiscal_emision')<div class="cfg-error">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="cfg-box" style="margin-top:14px;">
                                    <div class="switch">
                                        <label>
                                            No
                                            <input type="hidden" name="fiscal_facturacion_habilitada" value="0">
                                            <input type="checkbox" name="fiscal_facturacion_habilitada" value="1" @checked($fiscalFacturacionHabilitada)>
                                            <span class="lever"></span>
                                            Sí
                                        </label>
                                    </div>
                                    <div style="margin-top:8px;">Habilita el circuito de facturación electrónica para esta sucursal. Si el gateway está en <b>ARCA</b> y las credenciales existen, el sistema ya puede pedir CAE real en homologación.</div>
                                    @error('fiscal_facturacion_habilitada')<div class="cfg-error" style="margin-top:8px;">{{ $message }}</div>@enderror
                                </div>

                                <div class="cfg-box" style="margin-top:14px;">
                                    <div class="switch">
                                        <label>
                                            No
                                            <input type="hidden" name="fiscal_requiere_receptor_en_todas" value="0">
                                            <input type="checkbox" name="fiscal_requiere_receptor_en_todas" value="1" @checked($fiscalRequiereReceptor)>
                                            <span class="lever"></span>
                                            Sí
                                        </label>
                                    </div>
                                    <div style="margin-top:8px;">Reserva la política para exigir datos del receptor en todas las ventas fiscales. Queda persistida ahora para las siguientes fases.</div>
                                </div>

                                <div class="cfg-note" style="margin-top:14px;">
                                    <strong>Estado de preparación:</strong>
                                    @if ($fiscalUi['electronic_issues'] === [])
                                        La sucursal tiene los datos mínimos para avanzar con el circuito electrónico.
                                    @else
                                        <ul class="cfg-issues" style="margin-top:8px;">
                                            @foreach ($fiscalUi['electronic_issues'] as $issue)
                                                <li>{{ $issue }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <div class="cfg-note" style="margin-top:14px;">
                                    <strong>Credenciales ARCA detectadas:</strong>
                                    <ul class="cfg-list" style="margin-top:8px;">
                                        <li>CUIT representado: {{ data_get($arcaCredentials, 'represented_cuit.configured') ? (data_get($arcaCredentials, 'represented_cuit.masked') ?: 'configurado') : 'faltante' }}</li>
                                        <li>Certificado: {{ data_get($arcaCredentials, 'certificate.exists') ? 'ok' : (data_get($arcaCredentials, 'certificate.configured') ? 'ruta configurada pero archivo no encontrado' : 'faltante') }}</li>
                                        <li>Clave privada: {{ data_get($arcaCredentials, 'private_key.exists') ? 'ok' : (data_get($arcaCredentials, 'private_key.configured') ? 'ruta configurada pero archivo no encontrado' : 'faltante') }}</li>
                                        <li>CSR disponible: {{ data_get($arcaCredentials, 'csr.exists') ? 'sí' : 'no' }}</li>
                                    </ul>
                                    <div style="margin-top:8px;">La generación y carga de certificados ahora se administra desde la pestaña <b>Credenciales ARCA</b>.</div>
                                    <div style="margin-top:8px;">Alternativa por consola: <code>.\scripts\artisan-local.ps1 fiscal:homologacion-probar {{ $selectedBranch?->id }}</code></div>
                                </div>
                            </div>
                        </div>

                        <div class="card cfg-actions">
                            <div class="card-content">
                                <div class="cfg-actions-note">Guarda los cambios fiscales para la sucursal <b>{{ $selectedBranch?->nombre }}</b>.</div>
                                <button class="btn waves-effect waves-light" type="submit">
                                    <i class="material-icons left">save</i>Guardar configuración
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            @elseif ($facturacionTab === 'comprobantes')
                @include('admin-panel.settings.partials.facturacion-comprobantes')
            @else
                @include('admin-panel.settings.partials.facturacion-caea')
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-copy-csr').forEach(function (button) {
                button.addEventListener('click', async function () {
                    const targetId = button.getAttribute('data-copy-target');
                    const target = targetId ? document.getElementById(targetId) : null;
                    const status = document.getElementById('arca_csr_copy_status');

                    if (!target) {
                        return;
                    }

                    const text = target.value || target.textContent || '';

                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(text);
                        } else {
                            target.focus();
                            target.select();
                            document.execCommand('copy');
                        }

                        if (status) {
                            status.textContent = 'CSR copiada';
                        }
                    } catch (error) {
                        if (status) {
                            status.textContent = 'No se pudo copiar';
                        }
                    }
                });
            });
        });
    </script>
@endpush
