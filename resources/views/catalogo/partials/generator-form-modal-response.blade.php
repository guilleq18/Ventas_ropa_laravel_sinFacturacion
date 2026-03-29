@include('catalogo.partials.generator-form-modal-body', [
    'producto' => $producto,
    'values' => $values,
    'selectedProductId' => $selectedProductId,
    'successMessage' => $successMessage,
    'validationErrors' => $validationErrors,
])

<div id="variantes_panel" hx-swap-oob="innerHTML">
    @if ($selectedProduct)
        @include('catalogo.partials.variants-panel', [
            'selectedProduct' => $selectedProduct,
            'selectedVariants' => $selectedVariants,
        ])
    @else
        <p class="catalog-empty">Sin producto seleccionado.</p>
    @endif
</div>

<input type="hidden" id="selected_product_id" value="{{ $selectedProductId }}" hx-swap-oob="outerHTML">
