<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'activo']);

        $materials = Material::query()
            ->search($filtros['q'] ?? null)
            ->when(($filtros['activo'] ?? null) === 'activo', fn ($query) => $query->where('activo', true))
            ->when(($filtros['activo'] ?? null) === 'inactivo', fn ($query) => $query->where('activo', false))
            ->withCount('paymentMaterials')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('materials.index', [
            'materials' => $materials,
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('materials.create', [
            'material' => new Material(['activo' => true]),
        ]);
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['activo'] ??= true;

        $material = Material::create($data);

        return redirect()
            ->route('materials.show', $material)
            ->with('success', 'Material registrado correctamente.');
    }

    public function show(Material $material): View
    {
        $material->load([
            'paymentMaterials' => fn ($query) => $query
                ->with('payment.receivable.client')
                ->latest('id'),
        ]);

        return view('materials.show', [
            'material' => $material,
        ]);
    }

    public function edit(Material $material): View
    {
        return view('materials.edit', [
            'material' => $material,
        ]);
    }

    public function update(UpdateMaterialRequest $request, Material $material): RedirectResponse
    {
        $material->update($request->validated());

        return redirect()
            ->route('materials.show', $material)
            ->with('success', 'Material actualizado correctamente.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        if ($material->paymentMaterials()->exists()) {
            return back()->withErrors([
                'material' => 'No se puede eliminar un material usado en abonos. Puedes marcarlo como inactivo.',
            ]);
        }

        $material->delete();

        return redirect()
            ->route('materials.index')
            ->with('success', 'Material eliminado correctamente.');
    }
}
