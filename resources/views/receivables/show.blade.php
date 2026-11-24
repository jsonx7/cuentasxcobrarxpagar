@extends('layouts.app')

@section('title', $receivable->codigo)
@section('heading', 'Detalle del contrato')
@section('subheading', $receivable->codigo.' · '.$receivable->client->nombre)

@section('content')
    @php
        $user = auth()->user();
        $moraService = app(\App\Services\MoraService::class);
        $installments = $receivable->installments->sortBy('numero');
        $moraPorCuota = $installments->mapWithKeys(fn ($cuota) => [
            $cuota->id => $moraService->pendiente($cuota, today()),
        ]);
        $puedeEliminar = $receivable->payments->isEmpty();
        $puedeCancelar = ! $receivable->isCancelado() && ! $receivable->isPagado();

        // Saldo de cuotas después de cada abono (la mora se contabiliza aparte).
        $saldoPosterior = [];
        $acumulado = 0;
        foreach ($receivable->payments->sortBy(['fecha_pago', 'id']) as $pagoHistorial) {
            $acumulado += (float) $pagoHistorial->monto;
            $saldoPosterior[$pagoHistorial->id] = max(0, (float) $receivable->total_a_pagar - $acumulado);
        }
    @endphp

    <div x-data="paymentModal({
        today: '{{ now()->toDateString() }}',
        defaultMethod: 'efectivo',
        materiales: @js($catalogoMateriales->map(fn ($material) => [
            'id' => $material->id,
            'nombre' => $material->nombre,
            'precio' => $material->precio,
        ])->values())
    })">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('receivables.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Volver a cuentas por cobrar
            </a>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('receivables.pdf', $receivable) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Plan en PDF
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Imprimir
                </button>
                @if ($user?->canWrite() && ! $receivable->isCancelado() && $mora['pendiente'] > 0)
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500"
                        @click="show({ monto: {{ $mora['pendiente'] }}, mora: {{ $mora['pendiente'] }} })">
                        Cobrar mora pendiente
                    </button>
                @endif
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-6 text-white">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-semibold uppercase tracking-wider text-indigo-300">{{ $receivable->codigo }}</span>
                            <x-status-badge :status="$receivable->estado" class="!bg-white/10 !text-white !ring-white/20" />
                            @if ($receivable->cuotas_vencidas > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/15 px-2 py-0.5 text-xs font-medium text-rose-300 ring-1 ring-inset ring-rose-400/30">
                                    {{ $receivable->cuotas_vencidas }} vencida(s)
                                </span>
                            @endif
                        </div>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight"><x-money :value="$receivable->monto" /></h2>
                        <p class="mt-1 text-sm text-slate-400">
                            {{ $receivable->client->nombre }} · {{ $receivable->frecuencia->label() }} · {{ $receivable->plazo }} cuota(s)
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($user?->canWrite() && ! $receivable->isCancelado())
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-400" @click="show()">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Registrar abono
                            </button>
                        @endif

                        @if ($user?->canWrite() && $puedeCancelar)
                            <div x-data="{ open: false }">
                                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-sm font-medium text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15" @click="open = true">
                                    Cancelar contrato
                                </button>

                                <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true">
                                    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="open = false"></div>
                                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 text-left shadow-2xl">
                                        <h3 class="text-base font-semibold text-slate-900">Cancelar contrato</h3>
                                        <p class="mt-1 text-sm text-slate-500">El contrato quedará marcado como cancelado y no se podrán registrar nuevos abonos.</p>
                                        <form method="POST" action="{{ route('receivables.cancel', $receivable) }}" class="mt-4">
                                            @csrf
                                            <label for="motivo" class="block text-sm font-medium text-slate-700">Motivo (opcional)</label>
                                            <textarea id="motivo" name="motivo" rows="3" maxlength="500" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" placeholder="Detalle del motivo de cancelación…"></textarea>
                                            <div class="mt-5 flex justify-end gap-2">
                                                <button type="button" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="open = false">Volver</button>
                                                <button type="submit" class="rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-rose-500">Confirmar cancelación</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($user?->canWrite() && $puedeEliminar)
                            <form method="POST" action="{{ route('receivables.destroy', $receivable) }}" onsubmit="return confirm('¿Eliminar definitivamente este contrato y su plan de pagos?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-sm font-medium text-rose-200 ring-1 ring-inset ring-white/15 transition hover:bg-rose-500/20 hover:text-white">
                                    Eliminar
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Cuota</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900"><x-money :value="$receivable->cuota" /></p>
                    <p class="text-xs text-slate-400">{{ $receivable->frecuencia->shortLabel() }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total a pagar</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900"><x-money :value="$receivable->total_a_pagar" /></p>
                    <p class="text-xs text-slate-400">Interés: <x-money :value="$receivable->interes_total" /></p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Abonado a cuotas</p>
                    <p class="mt-1 text-xl font-semibold text-emerald-600"><x-money :value="$receivable->total_pagado" /></p>
                    <p class="text-xs text-slate-400">{{ $receivable->cuotas_pagadas }} de {{ $receivable->plazo }} cuota(s)</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Saldo pendiente</p>
                    <p class="mt-1 text-xl font-semibold {{ $receivable->saldo_pendiente > 0 ? 'text-rose-600' : 'text-slate-400' }}"><x-money :value="$receivable->saldo_pendiente" /></p>
                    <p class="text-xs text-slate-400">{{ number_format($receivable->progreso, 1) }}% completado</p>
                </div>
            </div>

            <div class="grid gap-4 border-t border-slate-100 bg-rose-50/40 p-5 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-rose-700">Mora calculada</p>
                    <p class="mt-1 text-lg font-semibold text-rose-900"><x-money :value="$mora['calculada']" /></p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-rose-700">Mora pagada</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-700"><x-money :value="$mora['pagada']" /></p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-rose-700">Mora pendiente</p>
                    <p class="mt-1 text-lg font-semibold text-rose-700"><x-money :value="$mora['pendiente']" /></p>
                </div>
            </div>

            <div class="border-t border-slate-100 px-5 py-4">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Progreso del contrato</span>
                    <span>{{ number_format($receivable->progreso, 0) }}%</span>
                </div>
                <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $receivable->progreso >= 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-indigo-500 to-violet-500' }}" style="width: {{ $receivable->progreso }}%"></div>
                </div>
            </div>

            <div class="grid gap-6 border-t border-slate-100 p-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Desembolso</p>
                    <p class="mt-1 text-slate-800">{{ $receivable->fecha_desembolso->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Primer pago</p>
                    <p class="mt-1 text-slate-800">{{ $receivable->fecha_primer_pago->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tasa anual / mora</p>
                    <p class="mt-1 text-slate-800">{{ number_format((float) $receivable->tasa_interes_anual, 2) }}% / {{ number_format((float) $receivable->tasa_mora_mensual, 2) }}%</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registrado por</p>
                    <p class="mt-1 text-slate-800">{{ $receivable->creator?->name ?? 'Sistema' }}</p>
                </div>
                @if ($receivable->observaciones)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observaciones</p>
                        <p class="mt-1 whitespace-pre-line text-slate-600">{{ $receivable->observaciones }}</p>
                    </div>
                @endif
            </div>
        </section>

        @if ($siguiente && ! $receivable->isCancelado())
            <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-indigo-900">Próxima cuota: #{{ $siguiente->numero }} · {{ $siguiente->fecha_vencimiento->format('d/m/Y') }}</p>
                        <p class="text-xs text-indigo-700">Saldo: <strong><x-money :value="$siguiente->saldo" /></strong> · Estado: {{ $siguiente->estado->label() }}</p>
                    </div>
                </div>
                @if ($user?->canWrite())
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500" @click="show({ id: {{ $siguiente->id }}, monto: {{ $siguiente->saldo }} })">
                        Cobrar esta cuota
                    </button>
                @endif
            </div>
        @endif

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="{ tab: 'plan' }">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                <div class="flex gap-1 rounded-xl bg-slate-100 p-1">
                    <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-medium transition" x-bind:class="tab === 'plan' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" @click="tab = 'plan'">
                        Plan de pagos <span class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ $installments->count() }}</span>
                    </button>
                    <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-medium transition" x-bind:class="tab === 'abonos' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" @click="tab = 'abonos'">
                        Historial de pagos <span class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ $receivable->payments->count() }}</span>
                    </button>
                </div>

                @if ($user?->canWrite() && ! $receivable->isCancelado() && ($receivable->saldo_pendiente > 0 || $mora['pendiente'] > 0))
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-800" @click="show()">
                        Nuevo abono
                    </button>
                @endif
            </div>

            <div x-show="tab === 'plan'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Vence</th>
                            <th class="px-4 py-3 text-right">Cuota</th>
                            <th class="px-4 py-3 text-right">Interés</th>
                            <th class="px-4 py-3 text-right">Capital</th>
                            <th class="px-4 py-3 text-right">Abonado</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3">Mora</th>
                            <th class="px-4 py-3">Estado</th>
                            @if ($user?->canWrite())
                                <th class="px-4 py-3"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($installments as $installment)
                            <tr class="transition hover:bg-slate-50/70 {{ $installment->esta_pagada ? 'bg-emerald-50/30' : '' }}">
                                <td class="px-4 py-3 text-slate-500">{{ $installment->numero }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $installment->fecha_vencimiento->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-800"><x-money :value="$installment->monto" /></td>
                                <td class="px-4 py-3 text-right text-slate-500"><x-money :value="$installment->interes" /></td>
                                <td class="px-4 py-3 text-right text-slate-500"><x-money :value="$installment->capital" /></td>
                                <td class="px-4 py-3 text-right text-emerald-600"><x-money :value="$installment->monto_pagado" /></td>
                                <td class="px-4 py-3 text-right font-medium text-slate-800"><x-money :value="$installment->saldo" /></td>
                                <td class="px-4 py-3 text-right text-rose-600"><x-money :value="$moraPorCuota[$installment->id] ?? 0" /></td>
                                <td class="px-4 py-3"><x-status-badge :status="$installment->estado" /></td>
                                @if ($user?->canWrite())
                                    <td class="px-4 py-3 text-right">
                                        @if ($receivable->isCancelado())
                                            —
                                        @elseif (! $installment->esta_pagada)
                                            <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-50" @click="show({ id: {{ $installment->id }}, monto: {{ $installment->saldo }} })">
                                                Cobrar
                                            </button>
                                        @elseif (($moraPorCuota[$installment->id] ?? 0) > 0)
                                            <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50" @click="show({ monto: {{ $moraPorCuota[$installment->id] }}, mora: {{ $moraPorCuota[$installment->id] }} })">
                                                Cobrar mora
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 text-sm font-semibold text-slate-700">
                        <tr>
                            <td colspan="2" class="px-4 py-3">Totales</td>
                            <td class="px-4 py-3 text-right"><x-money :value="$receivable->total_a_pagar" /></td>
                            <td class="px-4 py-3 text-right"><x-money :value="$receivable->interes_total" /></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3 text-right text-emerald-600"><x-money :value="$receivable->total_pagado" /></td>
                            <td class="px-4 py-3 text-right"><x-money :value="$receivable->saldo_pendiente" /></td>
                            <td class="px-4 py-3 text-right text-rose-600"><x-money :value="$mora['pendiente']" /></td>
                            <td colspan="{{ $user?->canWrite() ? 2 : 1 }}"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div x-show="tab === 'abonos'" x-cloak>
                @if ($receivable->payments->isEmpty())
                    <div class="p-5">
                        <x-empty-state title="Sin abonos registrados" description="Los abonos en dinero o materiales aparecerán aquí con su distribución por cuota.">
                            @if ($user?->canWrite() && ! $receivable->isCancelado() && ($receivable->saldo_pendiente > 0 || $mora['pendiente'] > 0))
                                <x-slot:action>
                                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500" @click="show()">Registrar primer abono</button>
                                </x-slot:action>
                            @endif
                        </x-empty-state>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Fecha</th>
                                    <th class="px-5 py-3 text-right">A cuotas</th>
                                    <th class="px-5 py-3 text-right">Mora</th>
                                    <th class="px-5 py-3 text-right">Saldo posterior</th>
                                    <th class="px-5 py-3">Método</th>
                                    <th class="px-5 py-3">Materiales</th>
                                    <th class="px-5 py-3">Referencia</th>
                                    <th class="px-5 py-3">Registrado por</th>
                                    <th class="px-5 py-3">Notas</th>
                                    @if ($user?->canWrite())
                                        <th class="px-5 py-3"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($receivable->payments as $payment)
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $payment->fecha_pago->format('d/m/Y') }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-emerald-600"><x-money :value="$payment->monto" /></td>
                                        <td class="whitespace-nowrap px-5 py-4 text-right text-rose-600"><x-money :value="$payment->monto_mora" /></td>
                                        <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-700"><x-money :value="$saldoPosterior[$payment->id] ?? 0" /></td>
                                        <td class="px-5 py-4"><x-status-badge :status="$payment->metodo" /></td>
                                        <td class="px-5 py-4 text-xs text-slate-500">
                                            @forelse ($payment->materials as $material)
                                                <div>{{ $material->cantidad }} × {{ $material->material->nombre }} · <x-money :value="$material->monto" /></div>
                                            @empty
                                                —
                                            @endforelse
                                        </td>
                                        <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $payment->referencia ?: '—' }}</td>
                                        <td class="px-5 py-4 text-slate-500">{{ $payment->user?->name ?? 'Sistema' }}</td>
                                        <td class="px-5 py-4 max-w-xs truncate text-slate-500" title="{{ $payment->notas }}">{{ $payment->notas ?: '—' }}</td>
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
                            <tfoot class="bg-slate-50 text-sm font-semibold text-slate-700">
                                <tr>
                                    <td class="px-5 py-3">Total</td>
                                    <td class="px-5 py-3 text-right text-emerald-600"><x-money :value="$receivable->total_pagado" /></td>
                                    <td class="px-5 py-3 text-right text-rose-600"><x-money :value="$receivable->total_mora_pagado" /></td>
                                    <td colspan="{{ $user?->canWrite() ? 6 : 5 }}"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-end justify-center p-0 sm:items-center sm:p-4 no-print" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="close()"></div>

            <div class="relative flex max-h-[94vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 bg-slate-50/80 px-6 py-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                        </span>
                        <div>
                            <h3 id="payment-modal-title" class="text-base font-semibold text-slate-900">Registrar abono</h3>
                            <p class="text-xs text-slate-500" x-show="installmentLabel">Aplicar a <span class="font-semibold text-slate-700" x-text="installmentLabel"></span></p>
                            <p class="text-xs text-slate-500" x-show="!installmentLabel">Se distribuye en las cuotas pendientes más antiguas.</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-200 hover:text-slate-600" @click="close()" aria-label="Cerrar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('payments.store', $receivable) }}" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <input type="hidden" name="installment_id" x-model="installmentId">

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] font-bold text-white">1</span>
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Datos del abono</h4>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="monto" class="block text-sm font-medium text-slate-700">Monto en dinero</label>
                                    <div class="relative mt-1.5">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-400">$</span>
                                        <input id="monto" name="monto" type="number" step="0.01" min="0" x-model.number="monto" x-ref="montoInput"
                                            class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-8 pr-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Puedes dejarlo en 0 si entregas solo materiales.</p>
                                </div>

                                <div>
                                    <label for="fecha_pago" class="block text-sm font-medium text-slate-700">Fecha del abono <span class="text-rose-500">*</span></label>
                                    <input id="fecha_pago" name="fecha_pago" type="date" x-model="fecha" max="{{ now()->toDateString() }}" required
                                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="metodo" class="block text-sm font-medium text-slate-700">Método de pago <span class="text-rose-500">*</span></label>
                                    <select id="metodo" name="metodo" x-model="metodo" required
                                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                        @foreach ($metodosPago as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-rose-100 bg-rose-50/50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">2</span>
                                    <h4 class="text-xs font-semibold uppercase tracking-wide text-rose-700">Mora (opcional)</h4>
                                </div>
                                <span class="text-xs font-medium text-rose-700">Pendiente hoy: <x-money :value="$mora['pendiente']" /></span>
                            </div>
                            <label for="monto_mora" class="block text-sm font-medium text-slate-700">Monto para mora</label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-rose-400">$</span>
                                <input id="monto_mora" name="monto_mora" type="number" step="0.01" min="0" x-model.number="montoMora"
                                    class="block w-full rounded-lg border border-rose-200 bg-white py-2 pl-8 pr-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-400/20">
                            </div>
                            <p class="mt-1 text-xs text-rose-700/80">Se descuenta del total entregado; el resto se aplica a cuotas. Puede pagarse aunque el contrato esté saldado.</p>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] font-bold text-white">3</span>
                                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Materiales entregados</h4>
                                </div>
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" @click="addMaterial()">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    Agregar
                                </button>
                            </div>
                            <p class="mb-3 text-xs text-slate-400">Se valoran con el precio del catálogo; puedes ajustar el precio unitario.</p>

                            <div class="space-y-3">
                                <template x-for="(material, index) in materiales" :key="index">
                                    <div class="grid gap-2 rounded-lg border border-slate-200 bg-slate-50/60 p-3 sm:grid-cols-[1fr_80px_110px_32px]">
                                        <select :name="'materiales[' + index + '][material_id]'" x-model="material.material_id" @change="seleccionarMaterial(index)"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                            <option value="">Selecciona un material…</option>
                                            <template x-for="item in catalogo" :key="item.id">
                                                <option :value="item.id" x-text="item.nombre + ' — $' + Number(item.precio).toFixed(2)"></option>
                                            </template>
                                        </select>
                                        <input type="number" step="0.001" min="0.001" placeholder="Cant." :name="'materiales[' + index + '][cantidad]'" x-model.number="material.cantidad"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                        <input type="number" step="0.01" min="0" placeholder="Precio" :name="'materiales[' + index + '][precio_unitario]'" x-model.number="material.precio_unitario"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                        <button type="button" class="flex h-9 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" @click="removeMaterial(index)" title="Quitar material" aria-label="Quitar material">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] font-bold text-white">4</span>
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Detalles adicionales</h4>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="referencia" class="block text-sm font-medium text-slate-700">Referencia</label>
                                    <input id="referencia" name="referencia" type="text" maxlength="80" x-model="referencia"
                                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        placeholder="Número de recibo, boleta o transferencia">
                                </div>
                                <div>
                                    <label for="notas" class="block text-sm font-medium text-slate-700">Notas</label>
                                    <input id="notas" name="notas" type="text" maxlength="500" x-model="notas"
                                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        placeholder="Observación opcional del abono…">
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="shrink-0 border-t border-slate-100 bg-white px-6 py-4">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs sm:grid-cols-4">
                            <div><span class="text-slate-500">Efectivo</span><p class="font-semibold text-slate-800" x-text="money(efectivo)"></p></div>
                            <div><span class="text-slate-500">Materiales</span><p class="font-semibold text-slate-800" x-text="money(materialTotal)"></p></div>
                            <div><span class="text-slate-500">Total entregado</span><p class="font-semibold text-slate-800" x-text="money(total)"></p></div>
                            <div><span class="text-slate-500">Para mora</span><p class="font-semibold text-rose-600" x-text="money(moraAsignada)"></p></div>
                        </div>
                        <div class="mt-3 flex flex-col gap-3 rounded-xl bg-indigo-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-indigo-900">
                                <p>Aplicado a cuotas: <strong class="text-base" x-text="money(totalCuotas)"></strong></p>
                                <p class="text-xs text-indigo-700">La mora se contabiliza aparte y nunca se capitaliza.</p>
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Registrar abono
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
