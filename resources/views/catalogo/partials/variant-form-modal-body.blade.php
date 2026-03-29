<div id="catalog_form_modal_body" class="catalog-modal-card">
    <div class="catalog-modal-head">
        <div class="catalog-modal-copy">
            <h3 class="catalog-modal-title">Editar variante</h3>
        </div>

        <button
            type="button"
            class="catalog-modal-close"
            onclick="window.dispatchEvent(new CustomEvent('catalog-form-modal-close'))"
            aria-label="Cerrar modal"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18"/>
                <path d="m6 6 12 12"/>
            </svg>
        </button>
    </div>

    <div class="catalog-modal-body">
        @if ($successMessage)
            <div class="catalog-modal-callout is-success">
                {{ $successMessage }}
            </div>
        @endif

        @if ($validationErrors?->any())
            <div class="catalog-modal-callout is-danger">
                {{ $validationErrors->first() }}
            </div>
        @endif

        <div class="catalog-panel-copy" style="margin-bottom: 14px;">
            <h4 class="catalog-panel-title" style="font-size: 1.2rem;">{{ $producto->nombre }}</h4>
            <p class="catalog-note">Edita SKU, atributos y valores comerciales de la variante seleccionada.</p>
        </div>

        <form
            method="POST"
            action="{{ route('catalogo.variantes.update', $variante) }}"
            hx-put="{{ route('catalogo.variantes.update', $variante) }}"
            hx-target="#catalog_form_modal_body"
            hx-swap="outerHTML"
            class="catalog-modal-form"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="selected_product_id" value="{{ $selectedProductId }}">

            <div class="catalog-modal-grid">
                <div>
                    <label>
                        <span class="catalog-modal-label">SKU</span>
                        <input type="text" name="sku" value="{{ $values['sku'] }}" class="catalog-modal-input" required autofocus>
                    </label>
                    @if ($validationErrors?->has('sku'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('sku') }}</p>
                    @endif
                </div>

                <div>
                    <label>
                        <span class="catalog-modal-label">Codigo de barras</span>
                        <input type="text" name="codigo_barras" value="{{ $values['codigo_barras'] }}" class="catalog-modal-input">
                    </label>
                    @if ($validationErrors?->has('codigo_barras'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('codigo_barras') }}</p>
                    @endif
                </div>

                <div>
                    <label>
                        <span class="catalog-modal-label">Talle</span>
                        <input type="text" name="talle" value="{{ $values['talle'] }}" class="catalog-modal-input" required>
                    </label>
                    @if ($validationErrors?->has('talle'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('talle') }}</p>
                    @endif
                </div>

                <div>
                    <label>
                        <span class="catalog-modal-label">Color</span>
                        <input type="text" name="color" value="{{ $values['color'] }}" class="catalog-modal-input" required>
                    </label>
                    @if ($validationErrors?->has('color'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('color') }}</p>
                    @endif
                </div>

                <div>
                    <label>
                        <span class="catalog-modal-label">Precio final</span>
                        <input type="number" step="0.01" min="0" name="precio" value="{{ $values['precio'] }}" class="catalog-modal-input" required>
                    </label>
                    @if ($validationErrors?->has('precio'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('precio') }}</p>
                    @endif
                </div>

                <div>
                    <label>
                        <span class="catalog-modal-label">Costo</span>
                        <input type="number" step="0.01" min="0" name="costo" value="{{ $values['costo'] }}" class="catalog-modal-input" required>
                    </label>
                    @if ($validationErrors?->has('costo'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('costo') }}</p>
                    @endif
                </div>
            </div>

            <input type="hidden" name="activo" value="0">
            <label class="catalog-modal-check">
                <input
                    type="checkbox"
                    name="activo"
                    value="1"
                    class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500"
                    @checked((bool) $values['activo'])
                >
                Variante activa
            </label>

            <div class="catalog-modal-actions">
                <p class="catalog-modal-actions-copy">La tabla de variantes se refresca al guardar para mostrar los cambios al instante.</p>

                <div class="catalog-panel-actions" style="margin: 0;">
                    <button type="submit" class="catalog-btn">Guardar variante</button>
                    <button
                        type="button"
                        class="catalog-btn catalog-btn-secondary"
                        onclick="window.dispatchEvent(new CustomEvent('catalog-form-modal-close'))"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
