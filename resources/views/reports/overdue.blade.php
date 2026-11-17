@extends('layouts.app')

@section('title', 'Cuotas vencidas')
@section('heading', 'Cuotas vencidas')
@section('subheading', 'Cuotas pendientes con fecha de vencimiento anterior a '.\Illuminate\Support\Carbon::today()->format('d/m/Y').'.')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Volver a reportes
        </a>
        <a href="{{ route('reports.overdue.pdf', $filtros) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Descargar PDF
        </a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Cuotas vencidas" :value="$totales['cantidad']" hint="Pendientes con fecha pasada" tone="rose" />
        <x-stat-card label="Contratos afectados" :value="$totales['contratos']" hint="Cuentas por cobrar con cuotas vencidas" tone="indigo" />
        <x-stat-card label="Saldo vencido" :value="'$'.number_format($totales['monto'], 2)" hint="Capital + intereses pendientes" tone="amber" />
        <x-stat-card label="Mora pendiente" :value="'$'.number_format($totales['mora'], 2)" hint="Interés moratorio calculado" tone="slate" />
    </div>

    <form method="GET" action="{{ route('reports.overdue') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-2">
            <div>
                <label for="q" class="sr-only">Buscar</label>
                <input id="q" name="q" type="search" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por código o cliente…" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Filtrar</button>
                <a href="{{ route('reports.overdue') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Limpiar</a>
            </div>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Contrato</th>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3 text-center">Cuota</th>
                        <th class="px-5 py-3">Vencimiento</th>
                        <th class="px-5 py-3 text-center">Días</th>
                        <th class="px-5 py-3 text-right">Monto</th>
                        <th class="px-5 py-3 text-right">Pagado</th>
                        <th class="px-5 py-3 text-right">Saldo</th>
                        <th class="px-5 py-3 text-right">Mora pendiente</th>
                        <th class="px-5 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($installments as $cuota)
                        <tr>
                            <td class="px-5 py-3">
                                <a href="{{ route('receivables.show', $cuota->receivable) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $cuota->receivable->codigo }}</a>
                            </td>
                            <td class="px-5 py-3 text-slate-800">{{ $cuota->receivable->client->nombre }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $cuota->numero }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $cuota->fecha_vencimiento?->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $cuota->dias_atraso }}</td>
                            <td class="px-5 py-3 text-right text-slate-700"><x-money :value="$cuota->monto" /></td>
                            <td class="px-5 py-3 text-right text-slate-500"><x-money :value="$cuota->monto_pagado" /></td>
                            <td class="px-5 py-3 text-right font-medium text-amber-600"><x-money :value="$cuota->saldo" /></td>
                            <td class="px-5 py-3 text-right font-medium text-rose-600"><x-money :value="$cuota->mora_pendiente" /></td>
                            <td class="px-5 py-3"><x-status-badge :status="$cuota->estado" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-8 text-center text-slate-500">No hay cuotas vencidas con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
