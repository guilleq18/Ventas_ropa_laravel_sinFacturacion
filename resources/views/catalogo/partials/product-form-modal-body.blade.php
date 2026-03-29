<div id="catalog_form_modal_body" class="catalog-modal-card">
    <div class="catalog-modal-head">
        <div class="catalog-modal-copy">
            <h3 class="catalog-modal-title">{{ $producto ? 'Editar producto' : 'Nuevo producto' }}</h3>
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
            <h4 class="catalog-panel-title" style="font-size: 1.2rem;">{{ $producto?->nombre ?: 'Base del producto' }}</h4>
            <p class="catalog-note">
                {{ $producto ? 'Actualiza la ficha base del producto sin salir del catalogo.' : 'Crea la base del producto para despues cargar variantes, precios y stock.' }}
            </p>
        </div>

        <form
            method="POST"
            action="{{ $producto ? route('catalogo.productos.update', $producto) : route('catalogo.productos.store') }}"
            @if ($producto)
                hx-put="{{ route('catalogo.productos.update', $producto) }}"
            @else
                hx-post="{{ route('catalogo.productos.store') }}"
            @endif
            hx-target="#catalog_form_modal_body"
            hx-swap="outerHTML"
            class="catalog-modal-form"
        >
            @csrf
            @if ($producto)
                @method('PUT')
            @endif
            <input type="hidden" name="q" value="{{ $searchQuery }}">
            <input type="hidden" name="selected_product_id" value="{{ $selectedProductId }}">

            <div class="catalog-modal-grid">
                <div class="is-span-2">
                    <label>
                        <span class="catalog-modal-label">Nombre</span>
                        <input
                            type="text"
                            name="nombre"
                            value="{{ $values['nombre'] }}"
                            class="catalog-modal-input"
                            required
                            autofocus
                        >
                    </label>
                    @if ($validationErrors?->has('nombre'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('nombre') }}</p>
                    @endif
                </div>

                <div class="is-span-2">
                    <label>
                        <span class="catalog-modal-label">Descripcion</span>
                        <textarea
                            name="descripcion"
                            rows="4"
                            class="catalog-modal-textarea"
                            placeholder="Detalle comercial, materiales o notas internas"
                        >{{ $values['descripcion'] }}</textarea>
                    </label>
                    @if ($validationErrors?->has('descripcion'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('descripcion') }}</p>
                    @endif
                </div>

                <div class="is-span-2">
                    <label class="catalog-modal-field">
                        <span>Categoria</span>
                        <select name="categoria_id" class="catalog-modal-select">
                            <option value="">Sin categoria</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected((string) $values['categoria_id'] === (string) $categoria->id)>
                                    {{ $categoria->nombre }}{{ $categoria->activa ? '' : ' (inactiva)' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @if ($validationErrors?->has('categoria_id'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('categoria_id') }}</p>
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
                Producto activo
            </label>

            <div class="catalog-modal-actions">
                <p class="catalog-modal-actions-copy">
                    {{ $producto ? 'Los cambios se reflejan en la lista y, si esta seleccionado, en el panel de variantes.' : 'Al crear el producto se agrega a la lista y queda listo para cargar variantes.' }}
                </p>

                <div class="catalog-panel-actions" style="margin: 0;">
                    <button type="submit" class="catalog-btn">{{ $producto ? 'Guardar producto' : 'Crear producto' }}</button>
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
