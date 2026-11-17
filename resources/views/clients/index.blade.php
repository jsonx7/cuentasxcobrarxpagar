@extends('layouts.app')

@section('title', 'Clientes')
@section('heading', 'Clientes')
@section('subheading', 'Registro de clientes externos y sus contratos por cobrar.')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200">
                {{ $clients->total() }} registro(s)
            </span>
            @if (filled($filtros['q'] ?? null) || filled($filtros['estado'] ?? null))
                <a href="{{ route('clients.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Limpiar filtros</a>
            @endif
        </div>

        @if ($user?->canWrite())
            <a href="{{ route('clients.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo cliente
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('clients.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label for="q" class="sr-only">Buscar</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input id="q" name="q" type="search" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por nombre, DUI o NIT…"
                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>

            <div class="flex gap-2">
                <div class="flex-1">
                    <label for="estado" class="sr-only">Estado</label>
                    <select id="estado" name="estado" class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">Todos</option>
                        <option value="activo" @selected(($filtros['estado'] ?? '') === 'activo')>Activos</option>
                        <option value="inactivo" @selected(($filtros['estado'] ?? '') === 'inactivo')>Inactivos</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800" title="Filtrar">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                </button>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($clients->isEmpty())
            <div class="p-6">
                <x-empty-state title="No se encontraron clientes" description="Ajusta los filtros o registra un nuevo cliente para comenzar.">
                    @if ($user?->canWrite())
                        <x-slot:action>
                            <a href="{{ route('clients.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Registrar cliente</a>
                        </x-slot:action>
                    @endif
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">DUI</th>
                            <th class="px-5 py-3">NIT</th>
                            <th class="px-5 py-3 text-center">Contratos</th>
                            <th class="px-5 py-3 text-right">Saldo</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($clients as $client)
                            @php
                                $contratos = $client->receivables->count();
                                $saldo = $client->receivables->sum(fn ($receivable) => $receivable->saldo_pendiente);
                            @endphp
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-600/10">
                                            {{ $client->iniciales }}
                                        </span>
                                        <div class="min-w-0">
                                            <a href="{{ route('clients.show', $client) }}" class="block truncate font-medium text-slate-800 hover:text-indigo-600">{{ $client->nombre }}</a>
                                            <p class="truncate text-xs text-slate-400">{{ $client->email ?: ($client->telefono ?: 'Sin contacto registrado') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $client->dui ?: '—' }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $client->nit ?: '—' }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">{{ $contratos }}</span>
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-slate-800">
                                    @if ($contratos > 0)
                                        <x-money :value="$saldo" />
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($client->activo)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500 ring-1 ring-inset ring-slate-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('clients.show', $client) }}" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Ver detalle">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </a>
                                        @if ($user?->canWrite())
                                            <a href="{{ route('clients.edit', $client) }}" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600" title="Editar">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v2.25A2.25 2.25 0 0117.25 18.75H5.25A2.25 2.25 0 013 16.5V4.5A2.25 2.25 0 015.25 2.25H7.5"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($clients->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $clients->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
