<div id="catalog_stock_modal_body" class="catalog-modal-card">
    <div class="catalog-modal-head">
        <div class="catalog-modal-copy">
            <h3 class="catalog-modal-title">Gestion de Stock</h3>
        </div>

        <button
            type="button"
            class="catalog-modal-close"
            onclick="window.dispatchEvent(new CustomEvent('catalog-stock-modal-close'))"
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

        <div class="catalog-modal-toolbar">
            <div class="catalog-panel-copy" style="margin-bottom: 0;">
                <h4 class="catalog-panel-title" style="font-size: 1.2rem;">{{ $producto->nombre }}</h4>
                <p class="catalog-note">Cambia la sucursal para ajustar la misma grilla sin salir del catálogo.</p>
            </div>

            <form
                class="catalog-modal-filter"
                hx-get="{{ route('catalogo.productos.stock-modal', $producto) }}"
                hx-target="#catalog_stock_modal_body"
                hx-swap="outerHTML"
            >
                @if ($focusedVariantId)
                    <input type="hidden" name="variante" value="{{ $focusedVariantId }}">
                @endif

                <label class="catalog-modal-field">
                    <span>Sucursal</span>
                    <select
                        name="sucursal"
                        class="catalog-modal-select"
                        onchange="this.form.requestSubmit()"
                    >
                        @forelse ($stockMatrix['sucursales'] as $sucursal)
                            <option value="{{ $sucursal->id }}" @selected(optional($stockMatrix['sucursalSeleccionada'])->id === $sucursal->id)>
                                {{ $sucursal->nombre }}
                            </option>
                        @empty
                            <option value="">Sin sucursales activas</option>
                        @endforelse
                    </select>
                </label>
            </form>
        </div>

        @if (! $stockMatrix['sucursalSeleccionada'])
            <div class="catalog-empty">
                No hay sucursales activas cargadas para trabajar stock.
            </div>
        @elseif (count($stockMatrix['rows']) === 0)
            <div class="catalog-empty">
                Crea al menos una variante para poder asignar stock por sucursal.
            </div>
        @else
            <form
                method="POST"
                action="{{ route('catalogo.stock.update', $producto) }}"
                hx-put="{{ route('catalogo.stock.update', $producto) }}"
                hx-target="#catalog_stock_modal_body"
                hx-swap="outerHTML"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="sucursal_id" value="{{ $stockMatrix['sucursalSeleccionada']->id }}">
                @if ($focusedVariantId)
                    <input type="hidden" name="variante" value="{{ $focusedVariantId }}">
                @endif

                <div class="catalog-stock-table-wrap">
                    <table class="catalog-stock-table">
                        <thead>
                            <tr>
                                <th>Talle</th>
                                @foreach ($stockMatrix['colores'] as $color)
                                    <th>{{ $color }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockMatrix['rows'] as $row)
                                <tr>
                                    <td>{{ $row['talle'] }}</td>
                                    @foreach ($row['cells'] as $cell)
                                        <td>
                                            @if ($cell['variante'])
                                                <label class="catalog-stock-cell {{ $focusedVariantId === $cell['variante']->id ? 'is-focused' : '' }}">
                                                    <span class="catalog-stock-sku">{{ $cell['variante']->sku }}</span>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        name="stocks[{{ $cell['variante']->id }}]"
                                                        value="{{ old('stocks.'.$cell['variante']->id, $cell['cantidad']) }}"
                                                        class="catalog-stock-input"
                                                    >
                                                </label>
                                            @else
                                                <span class="catalog-stock-empty-cell">Sin variante</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="catalog-stock-actions">
                    <p class="catalog-stock-note">
                        Los cambios impactan sobre la sucursal seleccionada y actualizan el stock total del panel derecho.
                    </p>

                    <div class="catalog-panel-actions" style="margin: 0;">
                        <button type="submit" class="catalog-btn">
                            Guardar stock
                        </button>
                        <button
                            type="button"
                            class="catalog-btn catalog-btn-secondary"
                            onclick="window.dispatchEvent(new CustomEvent('catalog-stock-modal-close'))"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
