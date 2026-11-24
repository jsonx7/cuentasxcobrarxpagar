@extends('layouts.app')

@section('title', 'Obligación '.$payable->codigo)

@section('content')
    @php($user = auth()->user())

    <div x-data="payablePaymentModal({
        today: '{{ now()->toDateString() }}',
        defaultMethod: 'efectivo',
        concepto: @js($payable->concepto),
    })">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('payables.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Volver a cuentas por pagar
            </a>

            <div class="flex flex-wrap gap-2">
                @if ($user?->canWrite() && ! $payable->isCancelado() && ! $payable->isPagado())
                    <button type="button" @click="show()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Registrar pago
                    </button>
                @endif

                @if ($user?->canWrite() && ! $payable->isCancelado() && ! $payable->isPagado())
                    <div x-data="{ open: false }">
                        <button type="button" @click="open = true" class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-white px-4 py-2.5 text-sm font-medium text-amber-700 transition hover:bg-amber-50">Cancelar obligación</button>
                        <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4" role="dialog" aria-modal="true">
                            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="open = false"></div>
                            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                                <h3 class="text-lg font-semibold text-slate-900">Cancelar obligación</h3>
                                <p class="mt-1 text-sm text-slate-500">La obligación {{ $payable->codigo }} quedará cancelada. Esta acción no se puede deshacer.</p>
                                <form method="POST" action="{{ route('payables.cancel', $payable) }}" class="mt-5 space-y-4">
                                    @csrf
                                    <div>
                                        <label for="motivo-cancelacion" class="block text-sm font-medium text-slate-700">Motivo (opcional)</label>
                                        <textarea id="motivo-cancelacion" name="motivo" rows="3" maxlength="500" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="open = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Volver</button>
                                        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Cancelar obligación</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($user?->canWrite())
                    <form method="POST" action="{{ route('payables.destroy', $payable) }}" onsubmit="return confirm('¿Eliminar la obligación y sus pagos? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-rose-300 bg-white px-4 py-2.5 text-sm font-medium text-rose-700 transition hover:bg-rose-50">Eliminar</button>
                    </form>
                @endif
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-6 text-white">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-semibold uppercase tracking-wider text-indigo-300">{{ $payable->codigo }}</span>
                            <x-status-badge :status="$payable->estado" class="!bg-white/10 !text-white !ring-white/20" />
                        </div>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight"><x-money :value="$payable->monto" /></h2>
                        <p class="mt-1 text-sm text-slate-400">{{ $payable->supplier->nombre }} · {{ $payable->concepto }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-right sm:grid-cols-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Pagado</p>
                            <p class="mt-1 font-semibold text-emerald-300"><x-money :value="$payable->total_pagado" /></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Saldo</p>
                            <p class="mt-1 font-semibold text-white"><x-money :value="$payable->saldo_pendiente" /></p>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Vencimiento</p>
                            <p class="mt-1 font-semibold text-white">{{ $payable->fecha_vencimiento?->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 border-b border-slate-100 p-6 md:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Detalle</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Proveedor</dt><dd class="font-medium text-slate-800">{{ $payable->supplier->nombre }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Concepto</dt><dd class="text-right font-medium text-slate-800">{{ $payable->concepto }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Fecha de emisión</dt><dd class="font-medium text-slate-800">{{ $payable->fecha_emision?->format('d/m/Y') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Fecha de vencimiento</dt><dd class="font-medium text-slate-800">{{ $payable->fecha_vencimiento?->format('d/m/Y') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Registrado por</dt><dd class="font-medium text-slate-800">{{ $payable->creator?->name ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Observaciones</h3>
                    <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $payable->observaciones ?: 'Sin observaciones.' }}</p>
                </div>
            </div>

            <div class="border-b border-slate-100 p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Vencimientos programados</h3>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ $payable->frecuencia->label() }} · {{ $payable->plazo }} cuota(s) · recordatorio sin mora ni intereses.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200">
                        {{ $payable->installments->where('vencida', true)->count() }} vencida(s)
                    </span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Vence</th>
                                <th class="px-4 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($payable->installments as $cuota)
                                <tr class="{{ $cuota->vencida ? 'bg-rose-50/40' : '' }}">
                                    <td class="px-4 py-3 font-medium text-slate-700">{{ $cuota->numero }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $cuota->fecha_vencimiento->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        @if ($cuota->pagada)
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">Pagada</span>
                                        @elseif ($cuota->vencida)
                                            <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200">Vencida</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">No hay vencimientos programados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Historial de pagos</h3>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                                <th class="px-4 py-3">Concepto</th>
                                <th class="px-4 py-3">Método</th>
                                <th class="px-4 py-3">Referencia</th>
                                <th class="px-4 py-3">Registrado por</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($payable->payments as $pago)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $pago->fecha_pago?->format('d/m/Y') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-slate-800"><x-money :value="$pago->monto" /></td>
                                    <td class="px-4 py-3 text-slate-600">{{ $pago->concepto ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $pago->metodo->label() }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $pago->referencia ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $pago->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($user?->canWrite())
                                            <form method="POST" action="{{ route('payable-payments.destroy', $pago) }}" onsubmit="return confirm('¿Eliminar este pago?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Eliminar</button>
                                            </form>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">No hay pagos registrados para esta obligación.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @if ($user?->canWrite() && ! $payable->isCancelado() && ! $payable->isPagado())
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true">
                <form method="POST" action="{{ route('payable-payments.store', $payable) }}" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                    @csrf
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Registrar pago</h2>
                            <p class="mt-1 text-sm text-slate-500">Saldo pendiente: <span x-text="money({{ (float) $payable->saldo_pendiente }})"></span></p>
                        </div>
                        <button type="button" @click="close()" class="text-slate-400 hover:text-slate-600" aria-label="Cerrar">✕</button>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="payable-fecha-pago" class="block text-sm font-medium text-slate-700">Fecha de pago</label>
                            <input type="date" id="payable-fecha-pago" name="fecha_pago" x-model="fecha" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            @error('fecha_pago')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="payable-monto-pago" class="block text-sm font-medium text-slate-700">Monto a pagar</label>
                            <input type="number" step="0.01" min="0.01" id="payable-monto-pago" name="monto" x-model.number="monto" x-ref="montoInput" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            @error('monto')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="payable-concepto-pago" class="block text-sm font-medium text-slate-700">Concepto del pago <span class="text-rose-500">*</span></label>
                            <input type="text" id="payable-concepto-pago" name="concepto" x-model="concepto" maxlength="160" required
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                                placeholder="Ej. Pago factura de octubre">
                            @error('concepto')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="payable-metodo-pago" class="block text-sm font-medium text-slate-700">Método</label>
                            <select id="payable-metodo-pago" name="metodo" x-model="metodo" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                @foreach ($metodosPago as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            @error('metodo')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="payable-referencia-pago" class="block text-sm font-medium text-slate-700">Referencia (opcional)</label>
                            <input type="text" id="payable-referencia-pago" name="referencia" x-model="referencia" maxlength="80" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            @error('referencia')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="payable-notas-pago" class="block text-sm font-medium text-slate-700">Notas (opcional)</label>
                            <textarea id="payable-notas-pago" name="notas" x-model="notas" rows="2" maxlength="500" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                            @error('notas')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="close()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar pago</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
