<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'estado']);

        $clients = Client::query()
            ->search($filtros['q'] ?? null)
            ->when(($filtros['estado'] ?? null) === 'activo', fn ($query) => $query->where('activo', true))
            ->when(($filtros['estado'] ?? null) === 'inactivo', fn ($query) => $query->where('activo', false))
            ->withCount('receivables')
            ->with([
                'receivables' => fn ($query) => $query
                    ->where('estado', 'activo')
                    ->withSum('payments', 'monto'),
            ])
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('clients.create', [
            'client' => new Client(['activo' => true]),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['activo'] ??= true;

        $client = Client::create($data);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Client $client): View
    {
        $client->load([
            'receivables' => fn ($query) => $query
                ->withSum('payments', 'monto')
                ->orderByDesc('fecha_desembolso')
                ->orderByDesc('id'),
        ]);

        $activos = $client->receivables->filter(fn ($receivable) => $receivable->isActivo());

        return view('clients.show', [
            'client' => $client,
            'receivables' => $client->receivables,
            'cartera' => round($client->receivables->sum(fn ($receivable) => (float) $receivable->monto), 2),
            'totalPagado' => round($client->receivables->sum(fn ($receivable) => $receivable->total_pagado), 2),
            'saldoPendiente' => round($activos->sum(fn ($receivable) => $receivable->saldo_pendiente), 2),
        ]);
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->receivables()->exists()) {
            return back()->withErrors([
                'cliente' => 'No se puede eliminar un cliente con contratos registrados. Puedes marcarlo como inactivo.',
            ]);
        }

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
