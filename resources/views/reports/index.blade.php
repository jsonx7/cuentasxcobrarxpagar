@extends('layouts.app')

@section('title', 'Reportes')
@section('heading', 'Reportes')
@section('subheading', 'Resumen general de cartera, cobros y pagos del sistema.')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat-card label="Contratos activos" :value="$contratosActivos" hint="Cuentas por cobrar en estado activo" tone="indigo" />
        <x-stat-card label="Cartera total" :value="'$'.number_format($cartera, 2)" hint="Total a pagar de los contratos activos" tone="sky" />
        <x-stat-card label="Cuotas vencidas" :value="$cuotasVencidas" hint="Cuotas pendientes con fecha pasada" tone="rose" />
        <x-stat-card label="Mora pendiente" :value="'$'.number_format($moraPendiente, 2)" hint="Calculada sobre cuotas vencidas o ya pagadas" tone="amber" />
        <x-stat-card label="Cobrado este mes" :value="'$'.number_format($cobradoMes, 2)" hint="Abonos registrados en el mes actual" tone="emerald" />
        <x-stat-card label="Pagado este mes" :value="'$'.number_format($pagadoMes, 2)" hint="Pagos a proveedores registrados en el mes actual" tone="slate" />
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('reports.consolidated') }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-inset ring-indigo-600/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h12A2.25 2.25 0 0020.25 14.25V3m-16.5 0h16.5M3.75 3a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 003.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3m-16.5 0h16.5M8.25 12h7.5m-7.5 3.75h7.5"/></svg>
            </div>
            <h2 class="mt-4 text-lg font-semibold text-slate-900">Consolidado general</h2>
            <p class="mt-1 text-sm text-slate-500">Contratos por cobrar y obligaciones por pagar con sus saldos, totales y filtros.</p>
            <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 group-hover:text-indigo-700">Ver reporte <span aria-hidden="true">→</span></span>
        </a>

        <a href="{{ route('reports.overdue') }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-rose-300 hover:shadow-md">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-inset ring-rose-600/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <h2 class="mt-4 text-lg font-semibold text-slate-900">Mora pendiente</h2>
            <p class="mt-1 text-sm text-slate-500">Detalle de cuotas con saldo o mora pendiente, incluso si el préstamo ya está pagado.</p>
            <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-rose-600 group-hover:text-rose-700">Ver reporte <span aria-hidden="true">→</span></span>
        </a>
    </div>
@endsection
