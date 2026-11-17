@php
    $isEdit = $material->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Datos del material</h2>
                <p class="text-xs text-slate-500">El precio se usa como referencia al registrar abonos con materiales.</p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="nombre" class="block text-sm font-medium text-slate-700">Nombre <span class="text-rose-500">*</span></label>
                    <input id="nombre" name="nombre" type="text" required maxlength="120" value="{{ old('nombre', $material->nombre) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="Ej. Cemento Portland 50 kg">
                    <x-input-error name="nombre" />
                </div>

                <div>
                    <label for="codigo" class="block text-sm font-medium text-slate-700">Código</label>
                    <input id="codigo" name="codigo" type="text" maxlength="40" value="{{ old('codigo', $material->codigo) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="MAT-001">
                    <x-input-error name="codigo" />
                </div>

                <div>
                    <label for="unidad" class="block text-sm font-medium text-slate-700">Unidad</label>
                    <input id="unidad" name="unidad" type="text" maxlength="30" value="{{ old('unidad', $material->unidad ?? config('cuentas.unidad_material_default', 'unidad')) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="unidad">
                    <x-input-error name="unidad" />
                </div>

                <div>
                    <label for="precio" class="block text-sm font-medium text-slate-700">Precio unitario <span class="text-rose-500">*</span></label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-400">$</span>
                        <input id="precio" name="precio" type="number" step="0.01" min="0" max="10000000" value="{{ old('precio', $material->precio) }}"
                            class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-8 pr-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            placeholder="0.00">
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Se sugiere automáticamente en el modal de abonos.</p>
                    <x-input-error name="precio" />
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Notas internas</h2>
                <p class="text-xs text-slate-500">Opcional.</p>
            </div>
            <div class="p-5">
                <label for="notas" class="sr-only">Notas</label>
                <textarea id="notas" name="notas" rows="4" maxlength="2000"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                    placeholder="Observaciones del material…">{{ old('notas', $material->notas) }}</textarea>
                <x-input-error name="notas" />
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Estado</h2>
                <p class="text-xs text-slate-500">Los materiales inactivos no se ofrecen en nuevos abonos.</p>
            </div>
            <div class="p-5">
                <input type="hidden" name="activo" value="0">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', $material->activo ?? true))
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30">
                    <span>
                        <span class="block text-sm font-medium text-slate-800">Material activo</span>
                        <span class="block text-xs text-slate-500">Disponible para registrar en abonos.</span>
                    </span>
                </label>
                <x-input-error name="activo" />
            </div>
        </section>

        <div class="flex flex-col gap-2">
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                {{ $isEdit ? 'Guardar cambios' : 'Registrar material' }}
            </button>
            <a href="{{ $isEdit ? route('materials.show', $material) : route('materials.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Cancelar
            </a>
        </div>
    </div>
</div>
