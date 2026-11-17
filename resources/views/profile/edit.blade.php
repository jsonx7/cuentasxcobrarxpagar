@extends('layouts.app')

@section('title', 'Mi perfil')
@section('heading', 'Mi perfil')
@section('subheading', 'Actualiza tus datos de acceso y contraseña.')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-800">Datos personales</h2>
                    <p class="text-xs text-slate-500">El correo electrónico es tu nombre de usuario.</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="p-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-slate-700">Nombre <span class="text-rose-500">*</span></label>
                            <input id="name" name="name" type="text" required maxlength="120" value="{{ old('name', $user->name) }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <x-input-error name="name" />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="block text-sm font-medium text-slate-700">Correo electrónico <span class="text-rose-500">*</span></label>
                            <input id="email" name="email" type="email" required maxlength="120" value="{{ old('email', $user->email) }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <x-input-error name="email" />
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                            Guardar datos
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-800">Cambiar contraseña</h2>
                    <p class="text-xs text-slate-500">Usa al menos 8 caracteres.</p>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="p-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="current_password" class="block text-sm font-medium text-slate-700">Contraseña actual <span class="text-rose-500">*</span></label>
                            <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <x-input-error name="current_password" />
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700">Nueva contraseña <span class="text-rose-500">*</span></label>
                            <input id="password" name="password" type="password" required autocomplete="new-password"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <x-input-error name="password" />
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmar nueva contraseña <span class="text-rose-500">*</span></label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            Actualizar contraseña
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-base font-bold text-indigo-600 ring-1 ring-indigo-600/10">
                        {{ \Illuminate\Support\Str::of($user->name)->substr(0, 1)->upper() }}
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->role->label() }}</p>
                    </div>
                </div>

                <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Correo</dt>
                        <dd class="truncate text-right font-medium text-slate-800">{{ $user->email }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Estado</dt>
                        <dd class="text-right">
                            @if ($user->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Activo</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Inactivo</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Último acceso</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800">Permisos</h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    @if ($user->isAdmin())
                        Tienes acceso completo para registrar clientes, contratos, abonos, obligaciones y administrar usuarios.
                    @elseif ($user->canWrite())
                        Puedes registrar clientes, materiales, contratos, abonos y obligaciones, pero no administrar usuarios.
                    @else
                        Tu cuenta es de consulta: puedes revisar la información y descargar reportes.
                    @endif
                </p>
            </section>
        </div>
    </div>
@endsection
