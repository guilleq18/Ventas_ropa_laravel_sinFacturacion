@php
    $money = $money ?? static fn ($value) => number_format((float) $value, 2, ',', '.');
@endphp

@if ($searchError)
    <div class="modal-inline-state is-error">
        {{ $searchError }}
    </div>
@elseif ($query === '')
    <div class="modal-inline-state">Escribi para buscar...</div>
@elseif ($results->isEmpty())
    <div class="modal-inline-state">No se encontraron coincidencias para "{{ $query }}".</div>
@else
    <div class="modal-search-results">
        @foreach ($results as $row)
            @php
                $stock = (int) ($row['stock'] ?? 0);
                $canAdd = $canOperate && ($allowSellWithoutStock || $stock > 0);
            @endphp
            <div class="modal-search-item">
                <div class="modal-search-item-main">
                    <div class="modal-search-item-title">
                        {{ $row['variant']->producto?->nombre ?? $row['label'] }}
                    </div>
                    <div class="modal-search-item-sku">
                        {{ $row['variant']->sku ?: 'SKU s/n' }}
                    </div>
                    <div class="modal-search-item-meta">
                        @if ($branch)
                            <span class="modal-search-branch">{{ $branch->nombre }}</span>
                        @endif
                        @if (! $allowSellWithoutStock)
                            @if ($stock <= 0)
                                <span class="modal-search-badge is-danger">Sin stock</span>
                            @else
                                <span class="modal-search-badge is-positive">Stock: {{ $stock }}</span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="modal-search-side">
                    <div class="modal-search-price">${{ $money($row['price']) }}</div>

                    <form method="POST" action="{{ route('caja.carrito.agregar', $row['variant']) }}" class="modal-search-action">
                        @csrf
                        <input type="hidden" name="return_modal" value="buscar">
                        <input type="hidden" name="return_q" value="{{ $query }}">
                        <button
                            type="submit"
                            class="btn waves-effect waves-light modal-search-add-btn"
                            @disabled(! $canAdd)
                            title="{{ $canAdd ? 'Agregar' : 'Sin stock' }}"
                        >
                            Agregar
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif
