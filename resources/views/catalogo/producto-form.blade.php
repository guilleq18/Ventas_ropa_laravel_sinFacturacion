<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ $producto ? 'Editar producto' : 'Nuevo producto' }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $producto ? 'Actualiza la ficha base del producto.' : 'Crea la base para despues trabajar variantes y stock.' }}
                </p>
            </div>

            <a
                href="{{ $producto ? route('catalogo.productos.show', $producto) : route('catalogo.index', ['tab' => 'productos']) }}"
                class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
            >
                Volver
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h3 class="text-lg font-semibold text-gray-900">Datos del producto</h3>
                    <p class="mt-1 text-sm text-gray-500">Precio y costo se gestionan desde las variantes del producto.</p>
                </div>

                <form
                    method="POST"
                    action="{{ $producto ? route('catalogo.productos.update', $producto) : route('catalogo.productos.store') }}"
                    class="space-y-6 p-6"
                >
                    @csrf
                    @if ($producto)
                        @method('PUT')
                    @endif

                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            Revisa los campos marcados antes de guardar.
                        </div>
                    @endif

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-input-label for="nombre" value="Nombre" />
                            <x-text-input
                                id="nombre"
                                name="nombre"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('nombre', $producto?->nombre) }}"
                                required
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="descripcion" value="Descripcion" />
                            <textarea
                                id="descripcion"
                                name="descripcion"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Detalle comercial, materiales o notas internas"
                            >{{ old('descripcion', $producto?->descripcion) }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="categoria_id" value="Categoria" />
                            <select
                                id="categoria_id"
                                name="categoria_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Sin categoria</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" @selected((string) old('categoria_id', $producto?->categoria_id) === (string) $categoria->id)>
                                        {{ $categoria->nombre }}{{ $categoria->activa ? '' : ' (inactiva)' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('categoria_id')" class="mt-2" />
                        </div>

                    </div>

                    <input type="hidden" name="activo" value="0">
                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            name="activo"
                            value="1"
                            class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-500"
                            @checked((bool) old('activo', $producto?->activo ?? true))
                        >
                        Producto activo
                    </label>

                    <div class="flex flex-wrap gap-3">
                        <x-primary-button>
                            {{ $producto ? 'Guardar producto' : 'Crear producto' }}
                        </x-primary-button>

                        <a
                            href="{{ $producto ? route('catalogo.productos.show', $producto) : route('catalogo.index', ['tab' => 'productos']) }}"
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
