@extends('layouts.app')

@section('title', 'Cuentas por cobrar')
@section('heading', 'Cuentas por cobrar')
@section('subheading', 'Contratos de clientes externos, planes de pago y saldos pendientes.')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200">
                {{ $receivables->total() }} contrato(s)
            </span>
            @if (filled($filtros['q'] ?? null) || filled($filtros['estado'] ?? null) || filled($filtros['frecuencia'] ?? null))
                <a href="{{ route('receivables.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Limpiar filtros</a>
            @endif
        </div>

        @if ($user?->canWrite())
            <a href="{{ route('receivables.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo contrato
            </a>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Contratos" :value="$totales['cantidad']" hint="Registrados en el filtro actual" tone="indigo" />
        <x-stat-card label="Monto original" :value="'$'.number_format($totales['monto'], 2)" hint="Capital de los contratos" tone="slate" />
        <x-stat-card label="Total por cobrar" :value="'$'.number_format($totales['total'], 2)" hint="Capital + intereses" tone="sky" />
        <x-stat-card label="Saldo por cobrar" :value="'$'.number_format($totales['saldo'], 2)" hint="Total menos abonos a cuotas" tone="emerald" />
    </div>

    <form method="GET" action="{{ route('receivables.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-1">
                <label for="q" class="sr-only">Buscar</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input id="q" name="q" type="search" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por código, cliente, DUI o NIT…"
                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>
            <div>
                <label for="estado" class="sr-only">Estado</label>
                <select id="estado" name="estado" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="frecuencia" class="sr-only">Frecuencia</label>
                <select id="frecuencia" name="frecuencia" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todas las frecuencias</option>
                    @foreach ($frecuencias as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['frecuencia'] ?? '') === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">Filtrar</button>
            <a href="{{ route('receivables.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($receivables->isEmpty())
            <div class="p-5">
                <x-empty-state title="Sin contratos" description="Registra el primer contrato por cobrar para comenzar a generar el plan de pagos.">
                    @if ($user?->canWrite())
                        <x-slot:action>
                            <a href="{{ route('receivables.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Nuevo contrato</a>
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
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Frecuencia</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-right">Pagado</th>
                            <th class="px-5 py-3 text-right">Saldo</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($receivables as $receivable)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-3">
                                    <a href="{{ route('receivables.show', $receivable) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $receivable->codigo }}</a>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-800">{{ $receivable->client->nombre }}</p>
                                    <p class="text-xs text-slate-400">{{ $receivable->client->dui ?? $receivable->client->nit ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-3 text-slate-500">{{ $receivable->frecuencia->label() }}</td>
                                <td class="px-5 py-3 text-right font-medium text-slate-800"><x-money :value="$receivable->total_a_pagar" /></td>
                                <td class="px-5 py-3 text-right text-slate-500"><x-money :value="$receivable->total_pagado" /></td>
                                <td class="px-5 py-3 text-right font-medium text-emerald-600"><x-money :value="$receivable->saldo_pendiente" /></td>
                                <td class="px-5 py-3"><x-status-badge :status="$receivable->estado" /></td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3 text-xs font-medium">
                                        <a href="{{ route('receivables.show', $receivable) }}" class="text-indigo-600 hover:text-indigo-700">Ver</a>
                                        <a href="{{ route('receivables.pdf', $receivable) }}" class="text-slate-500 hover:text-slate-700">PDF</a>
                                        @if ($user?->canWrite() && ! $receivable->isCancelado())
                                            <form method="POST" action="{{ route('receivables.cancel', $receivable) }}" onsubmit="return confirm('¿Cancelar este contrato?')">
                                                @csrf
                                                <button type="submit" class="text-amber-600 hover:text-amber-700">Cancelar</button>
                                            </form>
                                            <form method="POST" action="{{ route('receivables.destroy', $receivable) }}" onsubmit="return confirm('¿Eliminar este contrato? Esta acción no se puede deshacer.')">
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
                {{ $receivables->links() }}
            </div>
        @endif
    </section>
@endsection
