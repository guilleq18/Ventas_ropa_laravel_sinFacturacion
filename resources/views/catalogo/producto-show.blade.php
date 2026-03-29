<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $producto->nombre }}</h2>
                    <span class="{{ $producto->activo ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }} inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                        {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $producto->categoria?->nombre ?? 'Sin categoria' }} · {{ $variantes->count() }} variantes cargadas
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('catalogo.index', ['tab' => 'productos']) }}"
                    class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                >
                    Volver al catalogo
                </a>
                <a
                    href="{{ route('catalogo.productos.edit', $producto) }}"
                    class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                >
                    Editar producto
                </a>
                <a
                    href="{{ route('catalogo.variantes.create', $producto) }}"
                    class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800"
                >
                    Nueva variante
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                    Revisa los datos cargados antes de continuar.
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Precio base</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">${{ number_format((float) $producto->precio_base, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Costo base</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">${{ number_format((float) $producto->costo_base, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Descripcion</p>
                    <p class="mt-2 text-sm leading-6 text-gray-700">{{ $producto->descripcion ?: 'Sin descripcion cargada.' }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_24rem]">
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Variantes</h3>
                                <p class="mt-1 text-sm text-gray-500">Cada combinacion de talle y color queda registrada por separado.</p>
                            </div>
                            <a
                                href="{{ route('catalogo.variantes.create', $producto) }}"
                                class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                            >
                                Cargar manual
                            </a>
                        </div>
                    </div>

                    @include('catalogo.partials.variants-table', ['variantes' => $variantes])
                </section>

                <aside class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Generador de variantes</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Crea combinaciones de talle y color en lote. Las ya existentes se omiten solas.
                    </p>

                    <form method="POST" action="{{ route('catalogo.variantes.generate', $producto) }}" class="mt-5 space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="talles" value="Talles" />
                            <x-text-input id="talles" name="talles" type="text" class="mt-1 block w-full" value="{{ old('talles') }}" placeholder="S,M,L,XL" />
                            <x-input-error :messages="$errors->get('talles')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="colores" value="Colores" />
                            <x-text-input id="colores" name="colores" type="text" class="mt-1 block w-full" value="{{ old('colores') }}" placeholder="Negro,Blanco,Azul" />
                            <x-input-error :messages="$errors->get('colores')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="codigo_barras_base" value="Codigo de barras base" />
                            <x-text-input id="codigo_barras_base" name="codigo_barras_base" type="text" class="mt-1 block w-full" value="{{ old('codigo_barras_base') }}" placeholder="Opcional" />
                            <x-input-error :messages="$errors->get('codigo_barras_base')" class="mt-2" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="precio" value="Precio" />
                                <x-text-input id="precio" name="precio" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('precio', $producto->precio_base) }}" />
                                <x-input-error :messages="$errors->get('precio')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="costo" value="Costo" />
                                <x-text-input id="costo" name="costo" type="number" step="0.01" min="0" class="mt-1 block w-full" value="{{ old('costo', $producto->costo_base) }}" />
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
                                @checked((bool) old('activo', true))
                            >
                            Generar variantes activas
                        </label>

                        <x-primary-button>
                            Generar combinaciones
                        </x-primary-button>
                    </form>
                </aside>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-gray-200 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Stock por sucursal</h3>
                        <p class="mt-1 text-sm text-gray-500">Planilla color x talle sobre la sucursal seleccionada.</p>
                    </div>

                    <form method="GET" action="{{ route('catalogo.productos.show', $producto) }}">
                        <label class="block text-sm font-medium text-gray-700">
                            Sucursal
                            <select
                                name="sucursal"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                onchange="this.form.submit()"
                            >
                                @foreach ($stockMatrix['sucursales'] as $sucursal)
                                    <option value="{{ $sucursal->id }}" @selected(optional($stockMatrix['sucursalSeleccionada'])->id === $sucursal->id)>
                                        {{ $sucursal->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </form>
                </div>

                @include('catalogo.partials.stock-matrix', ['producto' => $producto, 'stockMatrix' => $stockMatrix])
            </section>
        </div>
    </div>
</x-app-layout>
