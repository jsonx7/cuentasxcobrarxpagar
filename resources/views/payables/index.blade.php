@extends('layouts.app')

@section('title', 'Cuentas por pagar')
@section('heading', 'Cuentas por pagar')
@section('subheading', 'Obligaciones con proveedores, vencimientos y pagos registrados.')

@section('content')
    @php
        $user = auth()->user();
        $hayFiltros = filled($filtros['q'] ?? null)
            || filled($filtros['estado'] ?? null)
            || filled($filtros['proveedor'] ?? null)
            || filled($filtros['desde'] ?? null)
            || filled($filtros['hasta'] ?? null);
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200">
                {{ $payables->total() }} obligación(es)
            </span>
            @if ($hayFiltros)
                <a href="{{ route('payables.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Limpiar filtros</a>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('payables.overdue') }}" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-50">
                Ver pagos vencidos
            </a>
            @if ($user?->canWrite())
                <a href="{{ route('payables.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nueva obligación
                </a>
            @endif
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Obligaciones" :value="$totales['cantidad']" hint="Registradas en el filtro actual" tone="indigo" />
        <x-stat-card label="Monto original" :value="'$'.number_format($totales['monto'], 2)" hint="Total de las obligaciones" tone="slate" />
        <x-stat-card label="Pagado" :value="'$'.number_format($totales['pagado'], 2)" hint="Pagos directos registrados" tone="emerald" />
        <x-stat-card label="Saldo por pagar" :value="'$'.number_format($totales['saldo'], 2)" hint="Monto menos pagos" tone="rose" />
    </div>

    <form method="GET" action="{{ route('payables.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-5">
            <div class="md:col-span-2 xl:col-span-2">
                <label for="q" class="sr-only">Buscar</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input id="q" name="q" type="search" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por código, concepto o proveedor…"
                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>
            <div>
                <label for="estado" class="sr-only">Estado</label>
                <select id="estado" name="estado" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="proveedor" class="sr-only">Proveedor</label>
                <select id="proveedor" name="proveedor" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todos los proveedores</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(($filtros['proveedor'] ?? '') === (string) $supplier->id)>{{ $supplier->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <label for="desde" class="sr-only">Desde</label>
                <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                <label for="hasta" class="sr-only">Hasta</label>
                <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
        </div>
        <div class="mt-4 flex gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">Filtrar</button>
            <a href="{{ route('payables.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($payables->isEmpty())
            <div class="p-5">
                <x-empty-state title="Sin obligaciones" description="Registra la primera obligación por pagar para comenzar a llevar el control de pagos a proveedores.">
                    @if ($user?->canWrite())
                        <x-slot:action>
                            <a href="{{ route('payables.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Nueva obligación</a>
                        </x-slot:action>
                    @endif
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Código</th>
                            <th class="px-5 py-3">Proveedor</th>
                            <th class="px-5 py-3">Concepto</th>
                            <th class="px-5 py-3">Próximo pago</th>
                            <th class="px-5 py-3 text-right">Total de la deuda</th>
                            <th class="px-5 py-3 text-right">Pagado</th>
                            <th class="px-5 py-3 text-right">Saldo</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($payables as $payable)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-3">
                                    <a href="{{ route('payables.show', $payable) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $payable->codigo }}</a>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-800">{{ $payable->supplier->nombre }}</p>
                                    <p class="text-xs text-slate-400">{{ $payable->supplier->nit ?: 'Sin NIT' }}</p>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $payable->concepto }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $payable->fecha_proxima_pago?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-medium text-slate-800"><x-money :value="$payable->monto" /></td>
                                <td class="px-5 py-3 text-right text-slate-500"><x-money :value="$payable->total_pagado" /></td>
                                <td class="px-5 py-3 text-right font-medium text-rose-600"><x-money :value="$payable->saldo_pendiente" /></td>
                                <td class="px-5 py-3"><x-status-badge :status="$payable->estado" /></td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3 text-xs font-medium">
                                        <a href="{{ route('payables.show', $payable) }}" class="text-indigo-600 hover:text-indigo-700">Ver</a>
                                        @if ($user?->canWrite() && ! $payable->isCancelado() && ! $payable->isPagado())
                                            <form method="POST" action="{{ route('payables.cancel', $payable) }}" onsubmit="return confirm('¿Cancelar esta obligación?')">
                                                @csrf
                                                <button type="submit" class="text-amber-600 hover:text-amber-700">Cancelar</button>
                                            </form>
                                        @endif
                                        @if ($user?->canWrite() && $payable->payments->isEmpty())
                                            <form method="POST" action="{{ route('payables.destroy', $payable) }}" onsubmit="return confirm('¿Eliminar esta obligación? Esta acción no se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-700">Eliminar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $payables->links() }}
            </div>
        @endif
    </section>
@endsection
