@extends('layouts.app')

@section('title', $material->nombre)
@section('heading', 'Detalle del material')
@section('subheading', $material->nombre)

@section('content')
    @php
        $user = auth()->user();
        $usos = $material->paymentMaterials()->count();
    @endphp

    <div class="mb-6">
        <a href="{{ route('materials.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Volver a materiales
        </a>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-6 text-white">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white ring-1 ring-white/20">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold">{{ $material->nombre }}</h2>
                            @if ($material->activo)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/15 px-2 py-0.5 text-xs font-medium text-emerald-300 ring-1 ring-inset ring-emerald-300/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-400/15 px-2 py-0.5 text-xs font-medium text-slate-300 ring-1 ring-inset ring-slate-400/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inactivo
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-400">
                            {{ $material->codigo ? 'Código '.$material->codigo.' · ' : '' }}{{ $material->unidad }}
                        </p>
                    </div>
                </div>

                @if ($user?->canWrite())
                    <a href="{{ route('materials.edit', $material) }}" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3.5 py-2 text-sm font-medium text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15">
                        Editar
                    </a>
                @endif
            </div>
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Precio unitario</p>
                <p class="mt-1 text-xl font-semibold text-slate-900"><x-money :value="$material->precio" /></p>
                <p class="text-xs text-slate-400">Por {{ $material->unidad }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Usos en abonos</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ $usos }}</p>
                <p class="text-xs text-slate-400">Detalles registrados</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Registro</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ $material->created_at?->format('d/m/Y') ?? '—' }}</p>
                <p class="text-xs text-slate-400">Fecha de alta</p>
            </div>
        </div>

        <div class="border-t border-slate-100 p-5">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notas</h3>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $material->notas ?: 'Sin notas registradas.' }}</p>
        </div>
    </section>

    @if ($user?->canWrite() && $usos === 0)
        <div class="mt-6 flex justify-end" x-data="confirmAction()">
            <form method="POST" action="{{ route('materials.destroy', $material) }}" x-ref="deleteForm">
                @csrf
                @method('DELETE')
                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-3.5 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50" @click="ask('¿Eliminar definitivamente este material? Esta acción no se puede deshacer.', () => $refs.deleteForm.submit())">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    Eliminar material
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
