@extends('layouts.app')

@section('title', 'Pagos vencidos')
@section('heading', 'Pagos vencidos')
@section('subheading', 'Recordatorios mensuales vencidos al '.\Illuminate\Support\Carbon::today()->format('d/m/Y').'. Solo recordatorio: sin mora ni intereses.')

@section('content')
    @php($user = auth()->user())

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('payables.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Volver a cuentas por pagar
        </a>

        @if ($user?->canWrite())
            <a href="{{ route('payables.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva obligación
            </a>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Recordatorios vencidos" :value="$totales['cantidad']" hint="Obligaciones con próxima fecha pasada" tone="rose" />
        <x-stat-card label="Total de la deuda" :value="'$'.number_format($totales['deuda'], 2)" hint="De las obligaciones listadas" tone="slate" />
        <x-stat-card label="Saldo por pagar" :value="'$'.number_format($totales['saldo'], 2)" hint="Total menos abonos y prima" tone="amber" />
        <x-stat-card label="Días de atraso" :value="$totales['dias']" hint="Mayor atraso del listado" tone="indigo" />
    </div>

    <form method="GET" action="{{ route('payables.overdue') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label for="q" class="sr-only">Buscar</label>
                <input id="q" name="q" type="search" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por código, concepto o proveedor…" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label for="proveedor" class="sr-only">Proveedor</label>
                <select id="proveedor" name="proveedor" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todos los proveedores</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filtros['proveedor'] ?? '') === (string) $supplier->id)>{{ $supplier->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Filtrar</button>
            <a href="{{ route('payables.overdue') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Código</th>
                        <th class="px-5 py-3">Proveedor</th>
                        <th class="px-5 py-3">Concepto</th>
                        <th class="px-5 py-3">Próxima fecha</th>
                        <th class="px-5 py-3 text-center">Días</th>
                        <th class="px-5 py-3 text-right">Total de la deuda</th>
                        <th class="px-5 py-3 text-right">Pagado</th>
                        <th class="px-5 py-3 text-right">Saldo</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payables as $payable)
                        <tr class="transition hover:bg-rose-50/40">
                            <td class="px-5 py-3">
                                <a href="{{ route('payables.show', $payable) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $payable->codigo }}</a>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $payable->supplier->nombre }}</p>
                                <p class="text-xs text-slate-400">{{ $payable->supplier->nit ?: 'Sin NIT' }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $payable->concepto }}</td>
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-rose-600">{{ $payable->fecha_proxima_pago->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $payable->fecha_proxima_pago->diffInDays(today()) }}</td>
                            <td class="px-5 py-3 text-right font-medium text-slate-800"><x-money :value="$payable->monto" /></td>
                            <td class="px-5 py-3 text-right text-slate-500"><x-money :value="$payable->total_pagado" /></td>
                            <td class="px-5 py-3 text-right font-medium text-amber-600"><x-money :value="$payable->saldo_pendiente" /></td>
                            <td class="px-5 py-3"><x-status-badge :status="$payable->estado" /></td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('payables.show', $payable) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-8 text-center text-sm text-slate-500">No hay pagos vencidos con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payables->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $payables->links() }}
            </div>
        @endif
    </section>
@endsection
