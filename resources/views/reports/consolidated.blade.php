@extends('layouts.app')

@section('title', 'Reporte consolidado')
@section('heading', 'Consolidado general')
@section('subheading', 'Cuentas por cobrar y por pagar con sus saldos al '.\Illuminate\Support\Carbon::now()->format('d/m/Y').'.')

@section('content')
    @php
        $hayFiltros = filled($filtros['q'] ?? null)
            || filled($filtros['estado'] ?? null)
            || filled($filtros['frecuencia'] ?? null)
            || filled($filtros['proveedor'] ?? null);
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Volver a reportes
        </a>
        <a href="{{ route('reports.consolidated.pdf', $filtros) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Descargar PDF
        </a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Contratos" :value="$totalesCobrar['cantidad']" hint="Incluidos en el filtro actual" tone="indigo" />
        <x-stat-card label="Total por cobrar" :value="'$'.number_format($totalesCobrar['total'], 2)" hint="Capital + intereses" tone="sky" />
        <x-stat-card label="Obligaciones" :value="$totalesPagar['cantidad']" hint="Incluidas en el filtro actual" tone="slate" />
        <x-stat-card label="Saldo por pagar" :value="'$'.number_format($totalesPagar['saldo'], 2)" hint="Monto menos pagos directos" tone="rose" />
    </div>

    <form method="GET" action="{{ route('reports.consolidated') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label for="q" class="sr-only">Buscar</label>
                <input id="q" name="q" type="search" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por código, cliente o proveedor…" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label for="estado" class="sr-only">Estado</label>
                <select id="estado" name="estado" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="frecuencia" class="sr-only">Frecuencia</label>
                <select id="frecuencia" name="frecuencia" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Todas las frecuencias</option>
                    @foreach ($frecuencias as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['frecuencia'] ?? '') === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
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
        <div class="mt-4 flex gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Filtrar</button>
            <a href="{{ route('reports.consolidated') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Cuentas por cobrar</h2>
                <p class="text-xs text-slate-500">{{ $totalesCobrar['cantidad'] }} contrato(s) · Capital ${{ number_format($totalesCobrar['monto'], 2) }} · Pagado ${{ number_format($totalesCobrar['pagado'], 2) }}</p>
            </div>
            <p class="text-sm font-semibold text-emerald-600">Saldo: ${{ number_format($totalesCobrar['saldo'], 2) }}</p>
        </div>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($receivables as $receivable)
                        <tr>
                            <td class="px-5 py-3 font-medium text-indigo-600">{{ $receivable->codigo }}</td>
                            <td class="px-5 py-3 text-slate-800">{{ $receivable->client->nombre }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $receivable->frecuencia->label() }}</td>
                            <td class="px-5 py-3 text-right text-slate-700"><x-money :value="$receivable->total_a_pagar" /></td>
                            <td class="px-5 py-3 text-right text-slate-500"><x-money :value="$receivable->total_pagado" /></td>
                            <td class="px-5 py-3 text-right font-medium text-emerald-600"><x-money :value="$receivable->saldo_pendiente" /></td>
                            <td class="px-5 py-3"><x-status-badge :status="$receivable->estado" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-6 text-center text-slate-500">No hay contratos que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Cuentas por pagar</h2>
                <p class="text-xs text-slate-500">{{ $totalesPagar['cantidad'] }} obligación(es) · Monto ${{ number_format($totalesPagar['monto'], 2) }} · Pagado ${{ number_format($totalesPagar['pagado'], 2) }}</p>
            </div>
            <p class="text-sm font-semibold text-rose-600">Saldo: ${{ number_format($totalesPagar['saldo'], 2) }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Código</th>
                        <th class="px-5 py-3">Proveedor</th>
                        <th class="px-5 py-3">Concepto</th>
                        <th class="px-5 py-3">Vencimiento</th>
                        <th class="px-5 py-3 text-right">Monto</th>
                        <th class="px-5 py-3 text-right">Pagado</th>
                        <th class="px-5 py-3 text-right">Saldo</th>
                        <th class="px-5 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payables as $payable)
                        <tr>
                            <td class="px-5 py-3 font-medium text-indigo-600">{{ $payable->codigo }}</td>
                            <td class="px-5 py-3 text-slate-800">{{ $payable->supplier->nombre }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $payable->concepto }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $payable->fecha_vencimiento?->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-right text-slate-700"><x-money :value="$payable->monto" /></td>
                            <td class="px-5 py-3 text-right text-slate-500"><x-money :value="$payable->total_pagado" /></td>
                            <td class="px-5 py-3 text-right font-medium text-rose-600"><x-money :value="$payable->saldo_pendiente" /></td>
                            <td class="px-5 py-3"><x-status-badge :status="$payable->estado" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-6 text-center text-slate-500">No hay obligaciones que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
