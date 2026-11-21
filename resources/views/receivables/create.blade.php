@extends('layouts.app')

@section('title', 'Nuevo contrato por cobrar')
@section('heading', 'Nuevo contrato por cobrar')
@section('subheading', 'Define el cliente, el monto y el plazo en meses para generar el plan de pagos.')

@section('content')
    @php
        $clienteInicial = old('client_id', request('client_id'));
        $tasaMoraInicial = old('tasa_mora_mensual', $tasaMoraDefault ?? config('cuentas.tasa_mora_default', 5));
    @endphp

    @if ($clients->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-6 w-6 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <div>
                    <h2 class="text-sm font-semibold text-amber-900">No hay clientes activos</h2>
                    <p class="mt-1 text-sm text-amber-800">Antes de crear un contrato debes registrar al menos un cliente activo.</p>
                    <a href="{{ route('clients.create') }}" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-amber-500">
                        Registrar cliente
                    </a>
                </div>
            </div>
        </div>
    @else
        <form
            method="POST"
            action="{{ route('receivables.store') }}"
            x-data="receivableForm(@js([
                'monto' => old('monto'),
                'tasa' => old('tasa_interes_anual', $tasaDefault ?? config('cuentas.tasa_interes_default', 8)),
                'tasaMora' => $tasaMoraInicial,
                'plazo' => old('plazo', 30),
                'frecuencia' => old('frecuencia', $frecuenciaDefault?->value ?? 'mensual'),
                'fechaDesembolso' => old('fecha_desembolso', $fechaDesembolso),
                'fechaPrimerPago' => old('fecha_primer_pago', $fechaPrimerPago),
                'clienteId' => $clienteInicial,
            ]))"
        >
            @csrf

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-800">Datos del contrato</h2>
                            <p class="text-xs text-slate-500">El interés se calcula por mes: saldo × tasa anual ÷ 12.</p>
                        </div>

                        <div class="grid gap-5 p-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="client_id" class="block text-sm font-medium text-slate-700">Cliente <span class="text-rose-500">*</span></label>
                                <select id="client_id" name="client_id" x-model="clienteId" required
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    <option value="">Selecciona un cliente…</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->nombre }} — {{ $client->dui ?: ($client->nit ?: 'Sin documento') }}</option>
                                    @endforeach
                                </select>
                                <x-input-error name="client_id" />
                            </div>

                            <div>
                                <label for="monto" class="block text-sm font-medium text-slate-700">Monto del contrato <span class="text-rose-500">*</span></label>
                                <div class="relative mt-1.5">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-400">$</span>
                                    <input id="monto" name="monto" type="number" step="0.01" min="1" x-model="monto" required
                                        class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-8 pr-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        placeholder="1500.00">
                                </div>
                                <x-input-error name="monto" />
                            </div>

                            <div>
                                <label for="tasa_interes_anual" class="block text-sm font-medium text-slate-700">Tasa de interés anual <span class="text-rose-500">*</span></label>
                                <div class="relative mt-1.5">
                                    <input id="tasa_interes_anual" name="tasa_interes_anual" type="number" step="0.01" min="0" max="100" x-model="tasa" required
                                        class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm font-medium text-slate-400">%</span>
                                </div>
                                <x-input-error name="tasa_interes_anual" />
                            </div>

                            <div>
                                <label for="tasa_mora_mensual" class="block text-sm font-medium text-slate-700">Tasa de mora mensual <span class="text-rose-500">*</span></label>
                                <div class="relative mt-1.5">
                                    <input id="tasa_mora_mensual" name="tasa_mora_mensual" type="number" step="0.01" min="0" max="100" x-model="tasaMora" value="{{ $tasaMoraInicial }}" required
                                        class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm font-medium text-slate-400">%</span>
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-slate-400">
                                    Mora = saldo capital de la cuota vencida × tasa ÷ 30 × días de atraso. No se capitaliza ni se suma al contrato.
                                </p>
                                <x-input-error name="tasa_mora_mensual" />
                            </div>

                            <div>
                                <label for="frecuencia" class="block text-sm font-medium text-slate-700">Frecuencia de pago <span class="text-rose-500">*</span></label>
                                <select id="frecuencia" name="frecuencia" x-model="frecuencia" required
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    @foreach ($frecuencias ?? \App\Enums\PaymentFrequency::createOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-slate-400">El plan se genera con vencimientos mensuales; el pago único crea una sola cuota.</p>
                                <x-input-error name="frecuencia" />
                            </div>

                            <div>
                                <label for="plazo" class="block text-sm font-medium text-slate-700">
                                    Plazo en meses <span class="text-rose-500" x-show="!isSingle">*</span>
                                </label>
                                <input id="plazo" name="plazo" type="number" min="1" max="520" x-model="plazo" x-bind:disabled="isSingle" x-bind:readonly="isSingle"
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                                <p class="mt-1 text-xs text-slate-400" x-show="isSingle">Con pago único el plazo siempre es 1 mes.</p>
                                <x-input-error name="plazo" />
                            </div>

                            <div>
                                <label for="fecha_desembolso" class="block text-sm font-medium text-slate-700">Fecha de desembolso <span class="text-rose-500">*</span></label>
                                <input id="fecha_desembolso" name="fecha_desembolso" type="date" x-model="fechaDesembolso" required
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <x-input-error name="fecha_desembolso" />
                            </div>

                            <div>
                                <label for="fecha_primer_pago" class="block text-sm font-medium text-slate-700">Primera fecha de pago <span class="text-rose-500">*</span></label>
                                <input id="fecha_primer_pago" name="fecha_primer_pago" type="date" x-model="fechaPrimerPago" required
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <p class="mt-1 text-xs text-slate-400">Se sugiere automáticamente un mes después del desembolso.</p>
                                <x-input-error name="fecha_primer_pago" />
                            </div>

                            <div class="sm:col-span-2">
                                <label for="observaciones" class="block text-sm font-medium text-slate-700">Observaciones</label>
                                <textarea id="observaciones" name="observaciones" rows="3" maxlength="2000"
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    placeholder="Acuerdos especiales, referencias del contrato…">{{ old('observaciones') }}</textarea>
                                <x-input-error name="observaciones" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Vista previa del plan de pagos</h2>
                                <p class="text-xs text-slate-500">Cálculo referencial; el plan definitivo se genera al guardar.</p>
                            </div>
                            <span class="inline-flex w-fit items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">
                                <span x-text="summary.plazo"></span> cuota(s)
                            </span>
                        </div>

                        <div class="max-h-96 overflow-auto">
                            <template x-if="schedule.length === 0">
                                <div class="px-5 py-10 text-center text-sm text-slate-400">
                                    Completa el monto, la tasa y el plazo para ver el plan de pagos.
                                </div>
                            </template>

                            <table x-show="schedule.length > 0" class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="sticky top-0 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3">#</th>
                                        <th class="px-5 py-3">Vencimiento</th>
                                        <th class="px-5 py-3 text-right">Cuota</th>
                                        <th class="px-5 py-3 text-right">Interés</th>
                                        <th class="px-5 py-3 text-right">Capital</th>
                                        <th class="px-5 py-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="row in schedule" :key="row.numero">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-5 py-2.5 text-slate-500" x-text="row.numero"></td>
                                            <td class="px-5 py-2.5 text-slate-600" x-text="row.fecha"></td>
                                            <td class="px-5 py-2.5 text-right font-medium text-slate-800" x-text="money(row.cuota)"></td>
                                            <td class="px-5 py-2.5 text-right text-slate-500" x-text="money(row.interes)"></td>
                                            <td class="px-5 py-2.5 text-right text-slate-500" x-text="money(row.capital)"></td>
                                            <td class="px-5 py-2.5 text-right text-slate-500" x-text="money(row.saldo)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="bg-gradient-to-br from-indigo-600 to-violet-700 px-5 py-4 text-white">
                            <p class="text-xs font-medium uppercase tracking-wide text-indigo-200">Cuota estimada</p>
                            <p class="mt-1 text-2xl font-semibold" x-text="money(summary.cuota)"></p>
                            <p class="mt-1 text-xs text-indigo-200">
                                <span x-text="frecuencia === 'unico' ? 'Pago único' : 'Cada mes'"></span>
                            </p>
                        </div>
                        <dl class="divide-y divide-slate-100 text-sm">
                            <div class="flex items-center justify-between px-5 py-3">
                                <dt class="text-slate-500">Interés total</dt>
                                <dd class="font-medium text-slate-800" x-text="money(summary.interes)"></dd>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <dt class="text-slate-500">Total a pagar</dt>
                                <dd class="font-semibold text-slate-900" x-text="money(summary.total)"></dd>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <dt class="text-slate-500">Plazo</dt>
                                <dd class="font-medium text-slate-800"><span x-text="summary.plazo"></span> cuota(s)</dd>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <dt class="text-slate-500">Tasa aplicada</dt>
                                <dd class="font-medium text-slate-800"><span x-text="number(tasa)"></span>% anual</dd>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <dt class="text-slate-500">Mora mensual</dt>
                                <dd class="font-medium text-slate-800"><span x-text="number(tasaMora)"></span>%</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-800">Cómo se calcula</h2>
                        <ul class="mt-3 space-y-2 text-xs leading-relaxed text-slate-500">
                            <li class="flex gap-2"><span class="mt-0.5 text-indigo-500">•</span> La cuota se obtiene con PMT usando la tasa mensual (tasa anual ÷ 12).</li>
                            <li class="flex gap-2"><span class="mt-0.5 text-indigo-500">•</span> El interés de cada cuota es el saldo pendiente × tasa anual ÷ 12.</li>
                            <li class="flex gap-2"><span class="mt-0.5 text-indigo-500">•</span> Los vencimientos son mensuales y conservan el día del primer pago.</li>
                            <li class="flex gap-2"><span class="mt-0.5 text-indigo-500">•</span> La última cuota ajusta el saldo restante para cerrar el contrato.</li>
                            <li class="flex gap-2"><span class="mt-0.5 text-indigo-500">•</span> La mora se calcula aparte sobre el capital vencido, a 30 días por mes, y puede pagarse aunque se salde el préstamo.</li>
                        </ul>
                    </section>

                    <div class="flex flex-col gap-2">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            Generar contrato
                        </button>
                        <a href="{{ route('receivables.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    @endif
@endsection
