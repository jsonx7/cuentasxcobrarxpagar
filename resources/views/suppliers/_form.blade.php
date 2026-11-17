@php
    $isEdit = $supplier->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Datos del proveedor</h2>
                <p class="text-xs text-slate-500">La información se usa para identificar las obligaciones por pagar.</p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="nombre" class="block text-sm font-medium text-slate-700">Nombre o razón social <span class="text-rose-500">*</span></label>
                    <input id="nombre" name="nombre" type="text" required maxlength="160" value="{{ old('nombre', $supplier->nombre) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="Ej. Distribuidora El Salvador, S.A. de C.V.">
                    <x-input-error name="nombre" />
                </div>

                <div>
                    <label for="nit" class="block text-sm font-medium text-slate-700">NIT</label>
                    <input id="nit" name="nit" type="text" maxlength="20" value="{{ old('nit', $supplier->nit) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 font-mono text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="00000000-0">
                    <x-input-error name="nit" />
                </div>

                <div>
                    <label for="contacto" class="block text-sm font-medium text-slate-700">Persona de contacto</label>
                    <input id="contacto" name="contacto" type="text" maxlength="120" value="{{ old('contacto', $supplier->contacto) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="Nombre del contacto">
                    <x-input-error name="contacto" />
                </div>

                <div>
                    <label for="telefono" class="block text-sm font-medium text-slate-700">Teléfono</label>
                    <input id="telefono" name="telefono" type="text" maxlength="25" value="{{ old('telefono', $supplier->telefono) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="7000-0000">
                    <x-input-error name="telefono" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Correo electrónico</label>
                    <input id="email" name="email" type="email" maxlength="120" value="{{ old('email', $supplier->email) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="proveedor@empresa.com">
                    <x-input-error name="email" />
                </div>

                <div class="sm:col-span-2">
                    <label for="direccion" class="block text-sm font-medium text-slate-700">Dirección</label>
                    <input id="direccion" name="direccion" type="text" maxlength="255" value="{{ old('direccion', $supplier->direccion) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="Dirección completa del proveedor">
                    <x-input-error name="direccion" />
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
                    placeholder="Información adicional del proveedor…">{{ old('notas', $supplier->notas) }}</textarea>
                <x-input-error name="notas" />
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Estado</h2>
                <p class="text-xs text-slate-500">Los proveedores inactivos no pueden recibir nuevas obligaciones.</p>
            </div>
            <div class="p-5">
                <input type="hidden" name="activo" value="0">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', $supplier->activo ?? true))
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30">
                    <span>
                        <span class="block text-sm font-medium text-slate-800">Proveedor activo</span>
                        <span class="block text-xs text-slate-500">Permitir nuevas obligaciones y mostrarlo en los filtros.</span>
                    </span>
                </label>
                <x-input-error name="activo" />
            </div>
        </section>

        <div class="flex flex-col gap-2">
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                {{ $isEdit ? 'Guardar cambios' : 'Registrar proveedor' }}
            </button>
            <a href="{{ $isEdit ? route('suppliers.show', $supplier) : route('suppliers.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Cancelar
            </a>
        </div>
    </div>
</div>
