<div id="catalog_form_modal_body" class="catalog-modal-card" data-generator-preview-root>
    <div class="catalog-modal-head">
        <div class="catalog-modal-copy">
            <h3 class="catalog-modal-title">Generador de variantes</h3>
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
            <p class="catalog-note">Genera combinaciones de talle y color de una sola vez. Las variantes existentes se omiten automaticamente.</p>
        </div>

        <form
            method="POST"
            action="{{ route('catalogo.variantes.generate', $producto) }}"
            hx-post="{{ route('catalogo.variantes.generate', $producto) }}"
            hx-target="#catalog_form_modal_body"
            hx-swap="outerHTML"
            class="catalog-modal-form"
        >
            @csrf
            <input type="hidden" name="selected_product_id" value="{{ $selectedProductId }}">

            <div class="catalog-modal-grid">
                <div>
                    <label>
                        <span class="catalog-modal-label">Talles</span>
                        <textarea
                            name="talles"
                            class="catalog-modal-textarea catalog-modal-textarea-compact"
                            placeholder="S, M, L, XL"
                            required
                            autofocus
                        >{{ $values['talles'] }}</textarea>
                    </label>
                    <p class="catalog-modal-hint">Separalos por coma. Ejemplo: `S, M, L, XL`.</p>
                    @if ($validationErrors?->has('talles'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('talles') }}</p>
                    @endif
                </div>

                <div>
                    <label>
                        <span class="catalog-modal-label">Colores</span>
                        <textarea
                            name="colores"
                            class="catalog-modal-textarea catalog-modal-textarea-compact"
                            placeholder="Negro, Blanco, Azul"
                            required
                        >{{ $values['colores'] }}</textarea>
                    </label>
                    <p class="catalog-modal-hint">Se cruza cada color con cada talle para generar el lote.</p>
                    @if ($validationErrors?->has('colores'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('colores') }}</p>
                    @endif
                </div>

                <div class="is-span-2">
                    <label>
                        <span class="catalog-modal-label">Codigo de barras base</span>
                        <input
                            type="text"
                            name="codigo_barras_base"
                            value="{{ $values['codigo_barras_base'] }}"
                            class="catalog-modal-input"
                            placeholder="Opcional"
                        >
                    </label>
                    <p class="catalog-modal-hint">Si lo completas, se replica el mismo EAN en todas las variantes generadas.</p>
                    @if ($validationErrors?->has('codigo_barras_base'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('codigo_barras_base') }}</p>
                    @endif
                </div>

                <div>
                    <label>
                        <span class="catalog-modal-label">Precio final</span>
                        <input type="number" step="0.01" min="0" name="precio" value="{{ $values['precio'] }}" class="catalog-modal-input" required>
                    </label>
                    <p class="catalog-modal-hint">Se usa como precio inicial para cada variante nueva.</p>
                    @if ($validationErrors?->has('precio'))
                        <p class="catalog-modal-error">{{ $validationErrors->first('precio') }}</p>
                    @endif
                </div>

                <div>
                    <label>
                        <span class="catalog-modal-label">Costo</span>
                        <input type="number" step="0.01" min="0" name="costo" value="{{ $values['costo'] }}" class="catalog-modal-input" required>
                    </label>
                    <p class="catalog-modal-hint">Se asigna el mismo costo a todas las combinaciones creadas.</p>
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
                Generar variantes activas
            </label>

            <div class="catalog-modal-preview">
                <div class="catalog-modal-preview-head">
                    <strong>Vista previa</strong>
                    <span>SKU automatico: 4 letras del producto + 3 del color + talle.</span>
                </div>

                <div class="catalog-modal-preview-grid">
                    <div class="catalog-modal-preview-item">
                        <span>Combinaciones</span>
                        <strong data-generator-total>0</strong>
                    </div>

                    <div class="catalog-modal-preview-item">
                        <span>Precio final</span>
                        <strong data-generator-final>$0,00</strong>
                    </div>

                    <div class="catalog-modal-preview-item">
                        <span>Base sin IVA</span>
                        <strong data-generator-neto>$0,00</strong>
                    </div>

                    <div class="catalog-modal-preview-item">
                        <span>IVA 21%</span>
                        <strong data-generator-iva>$0,00</strong>
                    </div>
                </div>
            </div>

            <div class="catalog-modal-actions">
                <p class="catalog-modal-actions-copy">Al generar, el panel de variantes se actualiza dentro del catalogo con las combinaciones nuevas.</p>

                <div class="catalog-panel-actions" style="margin: 0;">
                    <button type="submit" class="catalog-btn">Generar variantes</button>
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

    <script>
        (function () {
            const root = document.currentScript.closest('[data-generator-preview-root]');
            if (!root) return;

            const tallesInput = root.querySelector('textarea[name="talles"]');
            const coloresInput = root.querySelector('textarea[name="colores"]');
            const precioInput = root.querySelector('input[name="precio"]');
            const totalEl = root.querySelector('[data-generator-total]');
            const finalEl = root.querySelector('[data-generator-final]');
            const netoEl = root.querySelector('[data-generator-neto]');
            const ivaEl = root.querySelector('[data-generator-iva]');

            if (!tallesInput || !coloresInput || !precioInput || !totalEl || !finalEl || !netoEl || !ivaEl) {
                return;
            }

            const fmt = new Intl.NumberFormat('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            function parseList(raw) {
                return Array.from(new Set(
                    String(raw || '')
                        .split(',')
                        .map((value) => value.trim())
                        .filter(Boolean)
                ));
            }

            function parseDecimal(raw) {
                let txt = String(raw || '').trim();
                if (!txt) return NaN;

                txt = txt.replace(/\$/g, '').replace(/\s/g, '');

                const hasComma = txt.includes(',');
                const hasDot = txt.includes('.');

                if (hasComma && hasDot) {
                    if (txt.lastIndexOf(',') > txt.lastIndexOf('.')) {
                        txt = txt.replace(/\./g, '').replace(',', '.');
                    } else {
                        txt = txt.replace(/,/g, '');
                    }
                } else if (hasComma) {
                    txt = txt.replace(',', '.');
                }

                return Number(txt);
            }

            function money(value) {
                return Math.round((value + Number.EPSILON) * 100) / 100;
            }

            function updatePreview() {
                const talles = parseList(tallesInput.value);
                const colores = parseList(coloresInput.value);
                totalEl.textContent = String(talles.length * colores.length);

                const finalValue = parseDecimal(precioInput.value);
                if (!Number.isFinite(finalValue) || finalValue < 0) {
                    finalEl.textContent = '$0,00';
                    netoEl.textContent = '$0,00';
                    ivaEl.textContent = '$0,00';
                    return;
                }

                const finalMonto = money(finalValue);
                const neto = money(finalMonto / 1.21);
                const iva = money(finalMonto - neto);

                finalEl.textContent = '$' + fmt.format(finalMonto);
                netoEl.textContent = '$' + fmt.format(neto);
                ivaEl.textContent = '$' + fmt.format(iva);
            }

            tallesInput.addEventListener('input', updatePreview);
            coloresInput.addEventListener('input', updatePreview);
            precioInput.addEventListener('input', updatePreview);
            precioInput.addEventListener('change', updatePreview);
            updatePreview();
        })();
    </script>
</div>
