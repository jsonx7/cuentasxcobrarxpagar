@extends('layouts.app')

@section('title', $client->nombre)
@section('heading', 'Detalle del cliente')
@section('subheading', $client->nombre)

@section('content')
    @php
        $user = auth()->user();
        $contratos = $client->receivables;
        $activos = $contratos->filter(fn ($receivable) => $receivable->isActivo())->count();
        $totalPagado = $contratos->sum(fn ($receivable) => $receivable->total_pagado);
        $saldoPendiente = $contratos->sum(fn ($receivable) => $receivable->saldo_pendiente);
    @endphp

    <div class="mb-6">
        <a href="{{ route('clients.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Volver a clientes
        </a>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-6 text-white">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-lg font-bold ring-1 ring-white/20">
                        {{ $client->iniciales }}
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold">{{ $client->nombre }}</h2>
                            <span class="inline-flex items-center gap-1 rounded-full bg-{{ $client->activo ? 'emerald-400/15 px-2 py-0.5 text-xs font-medium text-emerald-300 ring-1 ring-inset ring-emerald-300/30' : 'slate-400/15 px-2 py-0.5 text-xs font-medium text-slate-300 ring-1 ring-inset ring-slate-400/30' }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-{{ $client->activo ? 'emerald-400' : 'slate-400' }}"></span>
                                {{ $client->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-400">
                            @if ($client->dui)DUI <span class="font-mono">{{ $client->dui }}</span>@endif
                            @if ($client->dui && $client->nit) · @endif
                            @if ($client->nit)NIT <span class="font-mono">{{ $client->nit }}</span>@endif
                        </p>
                    </div>
                </div>

                @if ($user?->canWrite())
                    <div class="flex flex-wrap gap-2">
                        @if ($client->activo)
                            <a href="{{ route('receivables.create', ['client_id' => $client->id]) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Nuevo contrato
                            </a>
                        @endif
                        <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3.5 py-2 text-sm font-medium text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15">
                            Editar
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contratos</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ $contratos->count() }}</p>
                <p class="text-xs text-slate-400">{{ $activos }} activo(s)</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Cartera histórica</p>
                <p class="mt-1 text-xl font-semibold text-slate-900"><x-money :value="$contratos->sum('total_a_pagar')" /></p>
                <p class="text-xs text-slate-400">Total a pagar</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total cobrado</p>
                <p class="mt-1 text-xl font-semibold text-emerald-600"><x-money :value="$totalPagado" /></p>
                <p class="text-xs text-slate-400">Abonos a cuotas</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Saldo pendiente</p>
                <p class="mt-1 text-xl font-semibold text-rose-600"><x-money :value="$saldoPendiente" /></p>
                <p class="text-xs text-slate-400">Por cobrar</p>
            </div>
        </div>

        <div class="grid gap-6 border-t border-slate-100 p-5 sm:grid-cols-2">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contacto</h3>
                <dl class="mt-2 space-y-1.5 text-sm">
                    <div class="flex gap-2">
                        <dt class="w-24 text-slate-400">Teléfono</dt>
                        <dd class="text-slate-700">{{ $client->telefono ?: '—' }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="w-24 text-slate-400">Correo</dt>
                        <dd class="break-all text-slate-700">{{ $client->email ?: '—' }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="w-24 text-slate-400">Dirección</dt>
                        <dd class="text-slate-700">{{ $client->direccion ?: '—' }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="w-24 text-slate-400">Registro</dt>
                        <dd class="text-slate-700">{{ $client->created_at?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notas</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $client->notas ?: 'Sin notas registradas.' }}</p>
            </div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Historial de contratos</h2>
                <p class="text-xs text-slate-500">Todos los contratos asociados a este cliente.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $contratos->count() }}</span>
        </div>

        @if ($contratos->isEmpty())
            <div class="p-5">
                <x-empty-state title="Sin contratos registrados" description="Este cliente todavía no tiene contratos en el sistema.">
                    @if ($user?->canWrite() && $client->activo)
                        <x-slot:action>
                            <a href="{{ route('receivables.create', ['client_id' => $client->id]) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Crear primer contrato</a>
                        </x-slot:action>
                    @endif
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Código</th>
                            <th class="px-5 py-3">Desembolso</th>
                            <th class="px-5 py-3">Plan</th>
                            <th class="px-5 py-3 text-right">Monto</th>
                            <th class="px-5 py-3 text-right">Total a pagar</th>
                            <th class="px-5 py-3 text-right">Cobrado</th>
                            <th class="px-5 py-3 text-right">Saldo</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($contratos as $receivable)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <a href="{{ route('receivables.show', $receivable) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $receivable->codigo }}</a>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ $receivable->fecha_desembolso?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $receivable->plazo }} cuota(s) · {{ $receivable->frecuencia->shortLabel() }}</td>
                                <td class="px-5 py-4 text-right text-slate-700"><x-money :value="$receivable->monto" /></td>
                                <td class="px-5 py-4 text-right text-slate-700"><x-money :value="$receivable->total_a_pagar" /></td>
                                <td class="px-5 py-4 text-right text-emerald-600"><x-money :value="$receivable->total_pagado" /></td>
                                <td class="px-5 py-4 text-right font-medium text-slate-800"><x-money :value="$receivable->saldo_pendiente" /></td>
                                <td class="px-5 py-4"><x-status-badge :status="$receivable->estado" /></td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('receivables.show', $receivable) }}" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Ver contrato">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($user?->canWrite() && $contratos->isEmpty())
        <div class="mt-6 flex justify-end" x-data="confirmAction()">
            <form method="POST" action="{{ route('clients.destroy', $client) }}" x-ref="deleteForm">
                @csrf
                @method('DELETE')
                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-3.5 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50" @click="ask('¿Eliminar definitivamente a este cliente? Esta acción no se puede deshacer.', () => $refs.deleteForm.submit())">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    Eliminar cliente
                </button>
            </form>

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
    @endif
@endsection
