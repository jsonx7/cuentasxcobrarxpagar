<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'estado']);

        $suppliers = Supplier::query()
            ->search($filtros['q'] ?? null)
            ->when(($filtros['estado'] ?? null) === 'activo', fn ($query) => $query->where('activo', true))
            ->when(($filtros['estado'] ?? null) === 'inactivo', fn ($query) => $query->where('activo', false))
            ->withCount('payables')
            ->with([
                'payables' => fn ($query) => $query
                    ->whereNotIn('estado', ['pagado', 'cancelado'])
                    ->withSum('payments', 'monto'),
            ])
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create', [
            'supplier' => new Supplier(['activo' => true]),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['activo'] ??= true;

        $supplier = Supplier::create($data);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Proveedor registrado correctamente.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'payables' => fn ($query) => $query
                ->withSum('payments', 'monto')
                ->orderByDesc('fecha_emision')
                ->orderByDesc('id'),
        ]);

        $total = round($supplier->payables->sum(fn ($payable) => (float) $payable->monto), 2);
        $totalPagado = round($supplier->payables->sum(fn ($payable) => $payable->total_pagado), 2);

        return view('suppliers.show', [
            'supplier' => $supplier,
            'payables' => $supplier->payables,
            'total' => $total,
            'totalPagado' => $totalPagado,
            'saldoPendiente' => round(max($total - $totalPagado, 0), 2),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->payables()->exists()) {
            return back()->withErrors([
                'proveedor' => 'No se puede eliminar un proveedor con obligaciones registradas. Puedes marcarlo como inactivo.',
            ]);
        }

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}
