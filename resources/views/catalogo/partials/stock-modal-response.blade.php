@include('catalogo.partials.stock-modal-body', [
    'producto' => $producto,
    'stockMatrix' => $stockMatrix,
    'focusedVariantId' => $focusedVariantId,
    'successMessage' => $successMessage,
    'validationErrors' => $validationErrors,
])

<div id="variantes_panel" hx-swap-oob="innerHTML">
    @include('catalogo.partials.variants-panel', [
        'selectedProduct' => $selectedProduct,
        'selectedVariants' => $selectedVariants,
    ])
</div>
