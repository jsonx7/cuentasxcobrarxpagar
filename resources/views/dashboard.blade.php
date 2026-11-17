@extends('layouts.app')

@section('title', 'Panel general')
@section('heading', 'Panel general')
@section('subheading', 'Resumen de cuentas por cobrar, cuentas por pagar y movimientos del mes.')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="mb-6 flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950 p-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-indigo-200">Hola, {{ $user?->name }}</p>
            <h2 class="mt-1 text-xl font-semibold tracking-tight">Así van tus cuentas hoy</h2>
            <p class="mt-1 text-sm text-slate-400">
                {{ $contratosActivos }} contrato(s) activo(s) · {{ $clientesActivos }} cliente(s) y {{ $proveedoresActivos }} proveedor(es) habilitado(s)
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('receivables.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3.5 py-2 text-sm font-medium text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Ver cobros
            </a>
            @if ($user?->canWrite())
                <a href="{{ route('receivables.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nuevo contrato
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total por cobrar" :value="'$'.number_format($totalPorCobrar, 2)" hint="Total de los contratos activos" tone="indigo">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Saldo por cobrar" :value="'$'.number_format($saldoPorCobrar, 2)" hint="Capital + intereses aún pendientes" tone="sky">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Saldo por pagar" :value="'$'.number_format($saldoPorPagar, 2)" hint="Obligaciones pendientes con proveedores" tone="amber">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card label="Mora pendiente" :value="'$'.number_format($moraPendiente, 2)" :hint="$cuotasVencidas.' cuota(s) vencida(s) · $'.number_format($montoVencido, 2)" tone="rose">
            <x-slot:icon>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Cobrado este mes" :value="'$'.number_format($cobradoMes, 2)" hint="{{ now()->translatedFormat('F Y') }}" tone="emerald" />
        <x-stat-card label="Pagado este mes" :value="'$'.number_format($pagadoMes, 2)" hint="{{ now()->translatedFormat('F Y') }}" tone="sky" />
        <x-stat-card label="Clientes activos" :value="$clientesActivos" hint="Personas externas registradas" tone="indigo" />
        <x-stat-card label="Proveedores activos" :value="$proveedoresActivos" hint="Registrados en el catálogo" tone="slate" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Próximas cuotas y pendientes</h2>
                    <p class="text-xs text-slate-500">Ordenadas por fecha de vencimiento.</p>
                </div>
                <a href="{{ route('reports.overdue') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Ver vencidas</a>
            </div>

            @if ($proximasCuotas->isEmpty())
                <div class="p-5">
                    <x-empty-state title="No hay cuotas pendientes" description="Cuando registres contratos, sus cuotas aparecerán aquí." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Cliente</th>
                                <th class="px-5 py-3">Contrato</th>
                                <th class="px-5 py-3">Cuota</th>
                                <th class="px-5 py-3">Vence</th>
                                <th class="px-5 py-3 text-right">Saldo</th>
                                <th class="px-5 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($proximasCuotas as $cuota)
                                <tr class="transition hover:bg-slate-50/70">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('receivables.show', $cuota->receivable) }}" class="font-medium text-slate-800 hover:text-indigo-600">
                                            {{ $cuota->receivable->client->nombre }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500">{{ $cuota->receivable->codigo }}</td>
                                    <td class="px-5 py-3 text-slate-500">#{{ $cuota->numero }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $cuota->fecha_vencimiento->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3 text-right font-medium text-slate-800"><x-money :value="$cuota->saldo" /></td>
                                    <td class="px-5 py-3"><x-status-badge :status="$cuota->estado" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Cartera por cliente</h2>
                <p class="text-xs text-slate-500">Total a pagar de contratos activos.</p>
            </div>

            @if ($porCliente->isEmpty())
                <div class="p-5">
                    <x-empty-state title="Sin datos por cliente" />
                </div>
            @else
                <ul class="space-y-4 p-5">
                    @foreach ($porCliente as $item)
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-slate-700">{{ $item->nombre }}</span>
                                <span class="shrink-0 text-slate-500"><x-money :value="$item->total" /></span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500" style="width: {{ max(4, round(((float) $item->total / $maxCliente) * 100)) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">{{ $item->contratos }} contrato(s)</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Contratos recientes</h2>
                    <p class="text-xs text-slate-500">Últimos contratos activos.</p>
                </div>
                <a href="{{ route('receivables.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Ver todos</a>
            </div>

            @if ($contratosRecientes->isEmpty())
                <div class="p-5">
                    <x-empty-state title="Todavía no hay contratos" description="Crea el primer contrato para comenzar a generar el plan de pagos.">
                        @if ($user?->canWrite())
                            <x-slot:action>
                                <a href="{{ route('receivables.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                    Crear contrato
                                </a>
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
                                <th class="px-5 py-3">Cliente</th>
                                <th class="px-5 py-3">Fecha</th>
                                <th class="px-5 py-3 text-right">Monto</th>
                                <th class="px-5 py-3 text-right">Pagado</th>
                                <th class="px-5 py-3 w-32">Progreso</th>
                                <th class="px-5 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($contratosRecientes as $receivable)
                                <tr class="transition hover:bg-slate-50/70">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('receivables.show', $receivable) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $receivable->codigo }}</a>
                                    </td>
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $receivable->client->nombre }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $receivable->fecha_desembolso->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3 text-right font-medium text-slate-800"><x-money :value="$receivable->monto" /></td>
                                    <td class="px-5 py-3 text-right text-slate-500"><x-money :value="$receivable->total_pagado" /></td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $receivable->progreso }}%"></div>
                                            </div>
                                            <span class="w-10 text-right text-xs text-slate-500">{{ number_format($receivable->progreso, 0) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3"><x-status-badge :status="$receivable->estado" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Obligaciones por vencer</h2>
                    <p class="text-xs text-slate-500">Cuentas por pagar próximas a su vencimiento.</p>
                </div>
                <a href="{{ route('payables.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Ver todas</a>
            </div>

            @if ($obligacionesPorVencer->isEmpty())
                <div class="p-5">
                    <x-empty-state title="Sin obligaciones próximas" description="Cuando registres cuentas por pagar, aparecerán aquí." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Código</th>
                                <th class="px-5 py-3">Proveedor</th>
                                <th class="px-5 py-3">Vence</th>
                                <th class="px-5 py-3 text-right">Monto</th>
                                <th class="px-5 py-3 text-right">Saldo</th>
                                <th class="px-5 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($obligacionesPorVencer as $payable)
                                <tr class="transition hover:bg-slate-50/70">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('payables.show', $payable) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $payable->codigo }}</a>
                                    </td>
                                    <td class="px-5 py-3 font-medium text-slate-800">{{ $payable->supplier->nombre }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $payable->fecha_vencimiento->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3 text-right text-slate-500"><x-money :value="$payable->monto" /></td>
                                    <td class="px-5 py-3 text-right font-medium text-slate-800"><x-money :value="$payable->saldo_pendiente" /></td>
                                    <td class="px-5 py-3"><x-status-badge :status="$payable->estado" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
