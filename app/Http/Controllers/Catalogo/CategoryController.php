<?php

namespace App\Http\Controllers\Catalogo;

use App\Domain\Catalogo\Models\Categoria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\CategoryRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function store(CategoryRequest $request): RedirectResponse
    {
        $categoria = Categoria::query()->create($request->validatedPayload());

        return redirect()
            ->route('catalogo.index', ['tab' => 'categorias'])
            ->with('success', "Categoria creada: {$categoria->nombre}.");
    }

    public function update(CategoryRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validatedPayload());

        return redirect()
            ->route('catalogo.index', ['tab' => 'categorias'])
            ->with('success', "Categoria actualizada: {$categoria->nombre}.");
    }

    public function toggle(Categoria $categoria): RedirectResponse
    {
        $categoria->update([
            'activa' => ! $categoria->activa,
        ]);

        $estado = $categoria->activa ? 'activada' : 'desactivada';

        return redirect()
            ->route('catalogo.index', ['tab' => 'categorias'])
            ->with('success', "Categoria {$categoria->nombre} {$estado}.");
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $nombre = $categoria->nombre;

        try {
            $categoria->delete();
        } catch (QueryException) {
            return redirect()
                ->route('catalogo.index', ['tab' => 'categorias'])
                ->with('error', "No se puede eliminar {$nombre}: tiene productos asociados.");
        }

        return redirect()
            ->route('catalogo.index', ['tab' => 'categorias'])
            ->with('success', "Categoria eliminada: {$nombre}.");
    }
}
