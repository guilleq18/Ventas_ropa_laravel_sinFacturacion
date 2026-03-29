<div>
    <div class="catalog-panel-copy">
        <h5 class="catalog-panel-title">{{ $selectedProduct->nombre }}</h5>
        <p class="catalog-note">Variantes del producto. Los precios se cargan como precio final.</p>
    </div>

    <div class="catalog-panel-actions">
        <a
            href="{{ route('catalogo.variantes.create', $selectedProduct) }}"
            class="catalog-btn"
        >
            Nueva variante
        </a>

        <a
            href="javascript:void(0)"
            class="catalog-btn catalog-btn-secondary"
            hx-get="{{ route('catalogo.variantes.generator', $selectedProduct) }}"
            hx-target="#catalog_form_modal_body"
            hx-swap="outerHTML"
            hx-include="#selected_product_id"
            onclick="window.dispatchEvent(new CustomEvent('catalog-form-modal-open'))"
        >
            Generador
        </a>
    </div>

    <div class="catalog-table-wrap">
        <table class="catalog-table catalog-table-variants">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>EAN</th>
                    <th>Color</th>
                    <th>Talle</th>
                    <th class="is-right">Precio</th>
                    <th class="is-right">Costo</th>
                    <th class="is-right">Stock total</th>
                    <th class="is-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($selectedVariants as $item)
                    <tr>
                        <td><span class="catalog-code">{{ $item['variante']->sku }}</span></td>
                        <td>
                            <span class="catalog-code is-muted">
                                {{ $item['variante']->codigo_barras ?: 'Sin EAN' }}
                            </span>
                        </td>
                        <td>{{ $item['color'] }}</td>
                        <td><strong>{{ $item['talle'] }}</strong></td>
                        <td class="is-right">
                            <span class="catalog-money">
                                ${{ number_format((float) $item['variante']->precio, 2, ',', '.') }}
                            </span>
                        </td>
                        <td class="is-right">
                            <span class="catalog-money">
                                ${{ number_format((float) $item['variante']->costo, 2, ',', '.') }}
                            </span>
                        </td>
                        <td class="is-right">
                            <button
                                type="button"
                                style="border: 0; background: transparent; padding: 0; cursor: pointer;"
                                hx-get="{{ route('catalogo.productos.stock-modal', ['producto' => $selectedProduct, 'variante' => $item['variante']->id]) }}"
                                hx-target="#catalog_stock_modal_body"
                                hx-swap="outerHTML"
                                onclick="window.dispatchEvent(new CustomEvent('catalog-stock-modal-open'))"
                            >
                                <span class="catalog-stock-pill">{{ $item['stock_total'] }}</span>
                            </button>
                        </td>
                        <td class="is-right">
                            <div class="catalog-list-actions" style="justify-content: flex-end;">
                                <a
                                    href="javascript:void(0)"
                                    class="catalog-icon-btn"
                                    title="Editar variante"
                                    hx-get="{{ route('catalogo.variantes.edit', $item['variante']) }}"
                                    hx-target="#catalog_form_modal_body"
                                    hx-swap="outerHTML"
                                    hx-include="#selected_product_id"
                                    onclick="window.dispatchEvent(new CustomEvent('catalog-form-modal-open'))"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 20h9"/>
                                        <path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                                    </svg>
                                </a>

                                <form method="POST" action="{{ route('catalogo.variantes.destroy', $item['variante']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="catalog-icon-btn delete"
                                        title="Eliminar variante"
                                        onclick="return confirm('Se eliminara la variante {{ addslashes($item['variante']->sku) }}. Continuar?')"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4h8v2"/>
                                            <path d="M19 6l-1 14H6L5 6"/>
                                            <path d="M10 11v6"/>
                                            <path d="M14 11v6"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="catalog-empty">Sin variantes todavia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
