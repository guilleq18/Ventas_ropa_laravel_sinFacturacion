@if (! $stockMatrix['sucursalSeleccionada'])
    <div class="px-6 py-10 text-center text-sm text-gray-500">
        No hay sucursales activas cargadas para trabajar stock.
    </div>
@elseif (count($stockMatrix['rows']) === 0)
    <div class="px-6 py-10 text-center text-sm text-gray-500">
        Crea al menos una variante para poder asignar stock por sucursal.
    </div>
@else
    <form method="POST" action="{{ route('catalogo.stock.update', $producto) }}" class="overflow-x-auto">
        @csrf
        @method('PUT')
        <input type="hidden" name="sucursal_id" value="{{ $stockMatrix['sucursalSeleccionada']->id }}">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Talle</th>
                    @foreach ($stockMatrix['colores'] as $color)
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">{{ $color }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach ($stockMatrix['rows'] as $row)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $row['talle'] }}</td>
                        @foreach ($row['cells'] as $cell)
                            <td class="px-6 py-4 align-top">
                                @if ($cell['variante'])
                                    <label class="block">
                                        <span class="sr-only">Stock {{ $cell['color'] }} {{ $row['talle'] }}</span>
                                        <input
                                            type="number"
                                            min="0"
                                            name="stocks[{{ $cell['variante']->id }}]"
                                            value="{{ old('stocks.'.$cell['variante']->id, $cell['cantidad']) }}"
                                            class="block w-28 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </label>
                                    <p class="mt-2 text-xs text-gray-500">{{ $cell['variante']->sku }}</p>
                                @else
                                    <span class="text-xs text-gray-400">Sin variante</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="border-t border-gray-200 px-6 py-4">
            <x-input-error :messages="$errors->get('stocks')" class="mb-3" />
            <x-primary-button>
                Guardar stock
            </x-primary-button>
        </div>
    </form>
@endif
