<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">SKU</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Talle</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Color</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Precio</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Stock total</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Estado</th>
                <th class="px-6 py-3 text-right font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($variantes as $item)
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $item['variante']->sku }}</div>
                        @if ($item['variante']->codigo_barras)
                            <div class="mt-1 text-xs text-gray-500">EAN: {{ $item['variante']->codigo_barras }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $item['talle'] }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $item['color'] }}</td>
                    <td class="px-6 py-4 text-gray-600">${{ number_format((float) $item['variante']->precio, 2, ',', '.') }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $item['stock_total'] }}</td>
                    <td class="px-6 py-4">
                        <span class="{{ $item['variante']->activo ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }} inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                            {{ $item['variante']->activo ? 'Activa' : 'Inactiva' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap justify-end gap-2">
                            <a
                                href="{{ route('catalogo.variantes.edit', $item['variante']) }}"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100"
                            >
                                Editar
                            </a>
                            <form method="POST" action="{{ route('catalogo.variantes.destroy', $item['variante']) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50"
                                    onclick="return confirm('Se eliminara la variante {{ addslashes($item['variante']->sku) }}. Continuar?')"
                                >
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                        Todavia no hay variantes para este producto.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
