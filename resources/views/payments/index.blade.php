@extends('layouts.app')

@section('title', 'Abonos a cuentas por cobrar')
@section('heading', 'Abonos')
@section('subheading', 'Historial de abonos en dinero y materiales registrados a los contratos.')

@section('content')
    @php
        $user = auth()->user();
        $hayFiltros = filled($filtros['q'] ?? null)
            || filled($filtros['desde'] ?? null)
            || filled($filtros['hasta'] ?? null)
            || filled($filtros['metodo'] ?? null);
    @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total del período" :value="'$'.number_format((float) ($totales['total'] ?? 0), 2)" hint="Dinero, mora y materiales" tone="emerald" />
        <x-stat-card label="Abonos a cuotas" :value="'$'.number_format((float) ($totales['monto'] ?? 0), 2)" hint="Capital + intereses" tone="indigo" />
        <x-stat-card label="Mora pagada" :value="'$'.number_format((float) ($totales['mora'] ?? 0), 2)" hint="No se capitaliza" tone="rose" />
        <x-stat-card label="Materiales" :value="'$'.number_format((float) ($totales['materiales'] ?? 0), 2)" hint="Valor de los materiales entregados" tone="amber" />
    </div>

    <form method="GET" action="{{ route('payments.index') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label for="q" class="sr-only">Buscar</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input id="q" name="q" type="search" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar por contrato, cliente, referencia…"
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
                <select id="metodo" name="metodo" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 md:max-w-xs">
                    <option value="">Todos los métodos</option>
                    @foreach (($metodos ?? []) as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['metodo'] ?? '') === (string) $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Filtrar</button>
                @if ($hayFiltros)
                    <a href="{{ route('payments.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($payments->isEmpty())
            <div class="p-6">
                <x-empty-state title="No se encontraron abonos" description="Registra abonos desde el detalle de un contrato por cobrar." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Contrato</th>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3 text-right">A cuotas</th>
                            <th class="px-5 py-3 text-right">Mora</th>
                            <th class="px-5 py-3 text-right">Materiales</th>
                            <th class="px-5 py-3">Método</th>
                            <th class="px-5 py-3">Referencia</th>
                            <th class="px-5 py-3">Usuario</th>
                            @if ($user?->canWrite())
                                <th class="px-5 py-3 text-right">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($payments as $payment)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $payment->fecha_pago?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('receivables.show', $payment->receivable) }}" class="font-medium text-indigo-600 hover:text-indigo-900">{{ $payment->receivable?->codigo ?? '—' }}</a>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $payment->receivable?->client?->nombre ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-emerald-600"><x-money :value="$payment->monto" /></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-rose-600"><x-money :value="$payment->monto_mora" /></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-amber-600">
                                    <x-money :value="$payment->materials->sum('monto')" />
                                </td>
                                <td class="px-5 py-4"><x-status-badge :status="$payment->metodo" /></td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $payment->referencia ?: '—' }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $payment->user?->name ?? 'Sistema' }}</td>
                                @if ($user?->canWrite())
                                    <td class="px-5 py-4 text-right">
                                        <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('¿Eliminar este abono? Las cuotas se recalcularán automáticamente.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="Eliminar abono" aria-label="Eliminar abono">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $payments->withQueryString()->links() }}
            </div>
        @endif
    </section>
@endsection
