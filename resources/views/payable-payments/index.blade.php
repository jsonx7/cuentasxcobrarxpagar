@extends('layouts.app')

@section('title', 'Pagos a proveedores')
@section('heading', 'Pagos')
@section('subheading', 'Historial de abonos registrados a las obligaciones de los proveedores.')

@section('content')
    @php
        $user = auth()->user();
        $hayFiltros = filled($filtros['q'] ?? null)
            || filled($filtros['desde'] ?? null)
            || filled($filtros['hasta'] ?? null)
            || filled($filtros['metodo'] ?? null);
    @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-stat-card
            label="Total del período"
            :value="'$'.number_format((float) ($totales['total'] ?? 0), 2)"
            hint="Suma de los pagos según los filtros aplicados"
            tone="emerald"
        />
        <x-stat-card
            label="Cantidad de pagos"
            :value="(int) ($totales['cantidad'] ?? 0)"
            hint="Registros encontrados"
            tone="indigo"
        />
    </div>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 font-medium text-slate-700 ring-1 ring-slate-200">
                {{ $payablePayments->total() }} registro(s)
            </span>
            @if ($hayFiltros)
                <a href="{{ route('payable-payments.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Limpiar filtros</a>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('payable-payments.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label for="q" class="sr-only">Buscar</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input id="q" name="q" type="search" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por obligación, referencia o proveedor…"
                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>
            <div>
                <label for="desde" class="sr-only">Desde</label>
                <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label for="hasta" class="sr-only">Hasta</label>
                <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div class="flex gap-2 md:col-span-4">
                <select id="metodo" name="metodo" class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 md:max-w-xs">
                    <option value="">Todos los métodos</option>
                    @foreach (($metodos ?? []) as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['metodo'] ?? '') === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800" title="Filtrar">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                </button>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="confirmAction()">
        @if ($payablePayments->isEmpty())
            <div class="p-6">
                <x-empty-state title="No se encontraron pagos" description="Ajusta los filtros o registra un pago desde una obligación por pagar." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Código / Obligación</th>
                            <th class="px-5 py-3">Proveedor</th>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3 text-right">Monto</th>
                            <th class="px-5 py-3">Método</th>
                            <th class="px-5 py-3">Referencia</th>
                            <th class="px-5 py-3">Usuario</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($payablePayments as $pago)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    @if ($pago->payable)
                                        <a href="{{ route('payables.show', $pago->payable) }}" class="font-medium text-indigo-600 hover:text-indigo-900">{{ $pago->payable->codigo }}</a>
                                        <p class="mt-0.5 max-w-xs truncate text-xs text-slate-400">{{ $pago->payable->concepto }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $pago->payable?->supplier?->nombre ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $pago->fecha_pago?->format('d/m/Y') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-800"><x-money :value="$pago->monto" /></td>
                                <td class="px-5 py-4"><x-status-badge :status="$pago->metodo" /></td>
                                <td class="px-5 py-4 text-slate-500">{{ $pago->referencia ?: '—' }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $pago->user?->name ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($user?->canWrite())
                                        <div class="flex justify-end">
                                            <form method="POST" action="{{ route('payable-payments.destroy', $pago) }}" x-ref="deleteForm-{{ $pago->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="button"
                                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                    title="Eliminar pago"
                                                    aria-label="Eliminar pago"
                                                    @click="ask('¿Eliminar este pago? La obligación se actualizará automáticamente.', () => $refs['deleteForm-{{ $pago->id }}'].submit())"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="block text-right text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-4">
                {{ $payablePayments->withQueryString()->links() }}
            </div>
        @endif

        <div x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="open = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-base font-semibold text-slate-900">Confirmar eliminación</h3>
                <p class="mt-2 text-sm text-slate-500" x-text="message"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="open = false">Volver</button>
                    <button type="button" class="rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-rose-500" @click="confirm()">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
@endsection
