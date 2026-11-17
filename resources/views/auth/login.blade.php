@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
<div class="relative flex min-h-full items-center justify-center overflow-hidden px-4 py-12">
    <div class="pointer-events-none absolute -left-32 top-1/4 h-96 w-96 rounded-full bg-indigo-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-violet-600/20 blur-3xl"></div>

    <div class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 shadow-2xl shadow-black/40 backdrop-blur lg:grid-cols-2">
        <div class="hidden flex-col justify-between bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-10 lg:flex">
            <div>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-lg font-bold text-white ring-1 ring-white/20">CP</span>
                <h2 class="mt-8 text-3xl font-semibold leading-tight text-white">Cuentas por Cobrar y Pagar</h2>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-indigo-100">
                    Administra clientes, contratos, planes de pago, abonos y cuentas por pagar desde un solo lugar.
                    Consulta la cartera, detecta cuotas vencidas y descarga reportes en PDF.
                </p>
            </div>

            <dl class="mt-10 grid grid-cols-3 gap-4 border-t border-white/15 pt-6">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-indigo-200">Planes</dt>
                    <dd class="mt-1 text-sm font-medium text-white">Amortización exacta</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-indigo-200">Pagos</dt>
                    <dd class="mt-1 text-sm font-medium text-white">Abonos trazables</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-indigo-200">Reportes</dt>
                    <dd class="mt-1 text-sm font-medium text-white">Consolidados</dd>
                </div>
            </dl>
        </div>

        <div class="p-6 sm:p-10">
            <div class="mb-8 lg:hidden">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">CP</span>
                <h2 class="mt-4 text-xl font-semibold text-white">Cuentas por Cobrar y Pagar</h2>
            </div>

            <h1 class="text-xl font-semibold text-white">Bienvenido de nuevo</h1>
            <p class="mt-1 text-sm text-slate-400">Ingresa tus credenciales para acceder al panel.</p>

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5" x-data="{ showPassword: false }">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300">Correo electrónico</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="mt-1.5 block w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                        placeholder="usuario@empresa.com"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300">Contraseña</label>
                    <div class="relative mt-1.5">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            class="block w-full rounded-xl border border-slate-700 bg-slate-950/60 px-3.5 py-2.5 pr-12 text-sm text-white placeholder-slate-500 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
                            placeholder="••••••••"
                        >
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-200"
                            @click="showPassword = !showPassword"
                            aria-label="Mostrar u ocultar contraseña"
                        >
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-400">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500/40">
                    Mantener sesión iniciada
                </label>

                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-950/50 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
                >
                    Iniciar sesión
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </button>
            </form>

            @if (app()->environment('local'))
                <div class="mt-8 rounded-xl border border-slate-800 bg-slate-950/50 p-4 text-xs text-slate-400">
                    <p class="font-semibold text-slate-300">Credenciales de prueba</p>
                    <div class="mt-2 grid gap-1 sm:grid-cols-2">
                        <p>Admin: <span class="text-slate-200">admin@cuentascyp.test</span></p>
                        <p>Clave: <span class="text-slate-200">Admin123!</span></p>
                        <p>Consulta: <span class="text-slate-200">consulta@cuentascyp.test</span></p>
                        <p>Clave: <span class="text-slate-200">Consulta123!</span></p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
