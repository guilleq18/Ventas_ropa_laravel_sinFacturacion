<div class="catalog-list">
    @php($currentSelectedProductId = (string) ($selectedProductId ?? request()->query('producto_id')))

    @forelse ($productos as $producto)
        @php($isSelected = $currentSelectedProductId === (string) $producto->getKey())

        <div class="catalog-list-item {{ $isSelected ? 'is-active' : '' }}">
            <div class="catalog-list-main">
                <strong>{{ $producto->nombre }}</strong>

                <div class="catalog-list-meta">
                    <span class="catalog-muted">{{ $producto->categoria?->nombre ?? 'Sin categoria' }}</span>
                </div>

                @if (($producto->categoria && ! $producto->categoria->activa) || ! $producto->activo)
                    <div class="catalog-badge-row">
                        @if ($producto->categoria && ! $producto->categoria->activa)
                            <span class="catalog-badge catalog-badge-warning">Cat. inactiva</span>
                        @endif

                        @if (! $producto->activo)
                            <span class="catalog-badge catalog-badge-danger">Inactivo</span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="catalog-list-actions">
                <button
                    type="button"
                    class="catalog-icon-btn"
                    title="Editar producto"
                    hx-get="{{ route('catalogo.productos.edit', $producto) }}"
                    hx-target="#catalog_form_modal_body"
                    hx-swap="outerHTML"
                    hx-include="#q,#selected_product_id"
                    onclick="window.dispatchEvent(new CustomEvent('catalog-form-modal-open'))"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                    </svg>
                </button>

                <button
                    type="button"
                    class="catalog-icon-btn is-primary"
                    title="Ver variantes"
                    hx-get="{{ route('catalogo.productos.panel', $producto) }}"
                    hx-target="#variantes_panel"
                    hx-swap="innerHTML"
                    onclick="document.querySelectorAll('#productos_lista .catalog-list-item').forEach(function(item){ item.classList.remove('is-active'); }); this.closest('.catalog-list-item').classList.add('is-active'); var selectedInput = document.getElementById('selected_product_id'); if (selectedInput) { selectedInput.value = '{{ $producto->id }}'; }"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </button>
            </div>
        </div>
    @empty
        <div class="catalog-empty">Sin productos.</div>
    @endforelse
</div>
