<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ $variante ? 'Editar variante' : 'Nueva variante' }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $producto->nombre }} · completa SKU, precio, costo y la combinacion talle/color.
                </p>
            </div>

            <a
                href="{{ route('catalogo.productos.show', $producto) }}"
                class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
            >
                Volver al producto
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h3 class="text-lg font-semibold text-gray-900">Ficha de variante</h3>
                    <p class="mt-1 text-sm text-gray-500">No se permiten duplicados de talle y color dentro del mismo producto.</p>
                </div>

                <form
                    method="POST"
                    action="{{ $variante ? route('catalogo.variantes.update', $variante) : route('catalogo.variantes.store', $producto) }}"
                    class="space-y-6 p-6"
                >
                    @csrf
                    @if ($variante)
                        @method('PUT')
                    @endif

                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            Revisa los datos cargados antes de guardar.
                        </div>
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label for="sku" value="SKU" />
                            <x-text-input
                                id="sku"
                                name="sku"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('sku', $values['sku']) }}"
                                required
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="codigo_barras" value="Codigo de barras" />
                            <x-text-input
                                id="codigo_barras"
                                name="codigo_barras"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('codigo_barras', $values['codigo_barras']) }}"
                            />
                            <x-input-error :messages="$errors->get('codigo_barras')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="talle" value="Talle" />
                            <x-text-input
                                id="talle"
                                name="talle"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('talle', $values['talle']) }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('talle')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="color" value="Color" />
                            <x-text-input
                                id="color"
                                name="color"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('color', $values['color']) }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('color')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="precio" value="Precio final" />
                            <x-text-input
                                id="precio"
                                name="precio"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                value="{{ old('precio', $values['precio']) }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('precio')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="costo" value="Costo" />
                            <x-text-input
                                id="costo"
                                name="costo"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                value="{{ old('costo', $values['costo']) }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('costo')" class="mt-2" />
                        </div>
                    </div>

                    <input type="hidden" name="activo" value="0">
                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            name="activo"
                            value="1"
                            class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-500"
                            @checked((bool) old('activo', $values['activo']))
                        >
                        Variante activa
                    </label>

                    <div class="flex flex-wrap gap-3">
                        <x-primary-button>
                            {{ $variante ? 'Guardar variante' : 'Crear variante' }}
                        </x-primary-button>

                        <a
                            href="{{ route('catalogo.productos.show', $producto) }}"
                            class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                        >
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
