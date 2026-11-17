@extends('layouts.app')

@section('title', 'Nueva obligación por pagar')
@section('heading', 'Nueva obligación por pagar')
@section('subheading', 'Registra una deuda con un proveedor. No requiere plan de amortización.')

@section('content')
    <div class="mx-auto max-w-3xl">
        @if ($suppliers->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <h2 class="text-sm font-semibold text-amber-900">No hay proveedores activos</h2>
                <p class="mt-1 text-sm text-amber-800">Antes de registrar una obligación debes dar de alta al menos un proveedor activo.</p>
                <a href="{{ route('suppliers.create') }}" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-amber-500">
                    Registrar proveedor
                </a>
            </div>
        @else
            <form method="POST" action="{{ route('payables.store') }}" class="space-y-6">
                @csrf

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Datos de la obligación</h2>
                        <p class="text-xs text-slate-500">Los pagos se registrarán directamente sobre el saldo de esta obligación.</p>
                    </div>

                    <div class="grid gap-5 p-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="supplier_id" class="block text-sm font-medium text-slate-700">Proveedor <span class="text-rose-500">*</span></label>
                            <select id="supplier_id" name="supplier_id" required
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">Selecciona un proveedor…</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') === (string) $supplier->id)>{{ $supplier->nombre }} — {{ $supplier->nit ?: 'Sin NIT' }}</option>
                                @endforeach
                            </select>
                            <x-input-error name="supplier_id" />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="concepto" class="block text-sm font-medium text-slate-700">Concepto <span class="text-rose-500">*</span></label>
                            <input id="concepto" name="concepto" type="text" maxlength="160" required value="{{ old('concepto') }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                placeholder="Ej. Factura de materiales de octubre">
                            <x-input-error name="concepto" />
                        </div>

                        <div>
                            <label for="monto" class="block text-sm font-medium text-slate-700">Monto de la obligación <span class="text-rose-500">*</span></label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-400">$</span>
                                <input id="monto" name="monto" type="number" step="0.01" min="1" required value="{{ old('monto') }}"
                                    class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-8 pr-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder="500.00">
                            </div>
                            <x-input-error name="monto" />
                        </div>

                        <div>
                            <label for="fecha_emision" class="block text-sm font-medium text-slate-700">Fecha de emisión <span class="text-rose-500">*</span></label>
                            <input id="fecha_emision" name="fecha_emision" type="date" required value="{{ old('fecha_emision', now()->toDateString()) }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <x-input-error name="fecha_emision" />
                        </div>

                        <div>
                            <label for="fecha_vencimiento" class="block text-sm font-medium text-slate-700">Fecha de vencimiento <span class="text-rose-500">*</span></label>
                            <input id="fecha_vencimiento" name="fecha_vencimiento" type="date" required value="{{ old('fecha_vencimiento', now()->addDays(15)->toDateString()) }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <x-input-error name="fecha_vencimiento" />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="observaciones" class="block text-sm font-medium text-slate-700">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" rows="3" maxlength="2000"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                placeholder="Referencias, condiciones del pago…">{{ old('observaciones') }}</textarea>
                            <x-input-error name="observaciones" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('payables.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        Registrar obligación
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection
