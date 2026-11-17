@php
    $isEdit = $user->exists;
    $isSelf = auth()->id() === $user->id;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Datos de la cuenta</h2>
                <p class="text-xs text-slate-500">El correo se usa para iniciar sesión.</p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700">Nombre <span class="text-rose-500">*</span></label>
                    <input id="name" name="name" type="text" required maxlength="120" value="{{ old('name', $user->name) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="Nombre completo">
                    <x-input-error name="name" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Correo electrónico <span class="text-rose-500">*</span></label>
                    <input id="email" name="email" type="email" required maxlength="120" value="{{ old('email', $user->email) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        placeholder="usuario@empresa.com">
                    <x-input-error name="email" />
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700">Rol <span class="text-rose-500">*</span></label>
                    <select id="role" name="role" required @disabled($isSelf)
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $user->role?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($isSelf)
                        <input type="hidden" name="role" value="{{ \App\Enums\UserRole::Admin->value }}">
                        <p class="mt-1 text-xs text-slate-400">No puedes cambiar tu propio rol.</p>
                    @endif
                    <x-input-error name="role" />
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">{{ $isEdit ? 'Cambiar contraseña' : 'Contraseña' }}</h2>
                <p class="text-xs text-slate-500">{{ $isEdit ? 'Déjala vacía para conservar la contraseña actual.' : 'Mínimo 8 caracteres.' }}</p>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Contraseña @if (! $isEdit)<span class="text-rose-500">*</span>@endif</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" @required(! $isEdit)
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <x-input-error name="password" />
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmar contraseña @if (! $isEdit)<span class="text-rose-500">*</span>@endif</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $isEdit)
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Estado de la cuenta</h2>
                <p class="text-xs text-slate-500">Los usuarios inactivos no pueden iniciar sesión.</p>
            </div>
            <div class="p-5">
                <input type="hidden" name="is_active" value="0">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) @disabled($isSelf)
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 disabled:cursor-not-allowed">
                    <span>
                        <span class="block text-sm font-medium text-slate-800">Cuenta activa</span>
                        <span class="block text-xs text-slate-500">Permitir el acceso al sistema.</span>
                    </span>
                </label>
                @if ($isSelf)
                    <input type="hidden" name="is_active" value="1">
                    <p class="mt-2 text-xs text-slate-400">No puedes desactivar tu propia cuenta.</p>
                @endif
                <x-input-error name="is_active" />
            </div>
        </section>

        @unless ($isEdit)
            <section class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-5">
                <h2 class="text-sm font-semibold text-indigo-900">Sobre los roles</h2>
                <p class="mt-1 text-xs leading-relaxed text-indigo-800">Los administradores pueden crear y modificar registros y administrar usuarios. Los operadores pueden crear y modificar clientes, materiales, proveedores, contratos, abonos y obligaciones, pero no administrar usuarios. Los usuarios de consulta solo visualizan información y descargan reportes.</p>
            </section>
        @endunless

        <div class="flex flex-col gap-2">
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                {{ $isEdit ? 'Guardar cambios' : 'Crear usuario' }}
            </button>
            <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Cancelar
            </a>
        </div>
    </div>
</div>
