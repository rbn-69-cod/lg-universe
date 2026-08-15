<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plataforma;
use Illuminate\Http\Request;

class PlataformaController extends Controller
{
    // LISTAR PLATAFORMAS
    public function index()
    {
        $plataformas = Plataforma::orderBy('orden')->get();

        return view('admin.plataformas.index', compact('plataformas'));
    }

    // FORMULARIO CREAR
    public function create()
    {
        return view('admin.plataformas.create');
    }

    // GUARDAR
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'imagen' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'features' => 'nullable|string',
        ]);

        $featuresArray = [];

        if (! empty($data['features'])) {
            $featuresArray = array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $data['features']))
            );
        }

        $data['features'] = $featuresArray;
        $data['activo'] = 1;

        // orden automático al final
        $data['orden'] = Plataforma::max('orden') + 1;

        Plataforma::create($data);

        return redirect()
            ->route('admin.plataformas.index')
            ->with('success', 'Plataforma creada correctamente.');
    }

    // EDITAR
    public function edit(Plataforma $plataforma)
    {
        return view('admin.plataformas.edit', compact('plataforma'));
    }

    // ACTUALIZAR
    public function update(Request $request, Plataforma $plataforma)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'imagen' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'features' => 'nullable|string',
        ]);

        $featuresArray = [];

        if (! empty($data['features'])) {
            $featuresArray = array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $data['features']))
            );
        }

        $data['features'] = $featuresArray;
        $data['activo'] = 1;

        $plataforma->update($data);

        return redirect()
            ->route('admin.plataformas.index')
            ->with('success', 'Plataforma actualizada correctamente.');
    }

    // ELIMINAR
    public function destroy(Plataforma $plataforma)
    {
        $plataforma->delete();

        return redirect()
            ->route('admin.plataformas.index')
            ->with('success', 'Plataforma eliminada.');
    }

    // 🔼 SUBIR PLATAFORMA
    public function subir($id)
    {
        $actual = Plataforma::findOrFail($id);

        $anterior = Plataforma::where('orden', '<', $actual->orden)
            ->orderBy('orden', 'desc')
            ->first();

        if ($anterior) {

            $temp = $actual->orden;
            $actual->orden = $anterior->orden;
            $anterior->orden = $temp;

            $actual->save();
            $anterior->save();
        }

        return back();
    }

    // 🔽 BAJAR PLATAFORMA
    public function bajar($id)
    {
        $actual = Plataforma::findOrFail($id);

        $siguiente = Plataforma::where('orden', '>', $actual->orden)
            ->orderBy('orden', 'asc')
            ->first();

        if ($siguiente) {

            $temp = $actual->orden;
            $actual->orden = $siguiente->orden;
            $siguiente->orden = $temp;

            $actual->save();
            $siguiente->save();
        }

        return back();
    }
}
