<?php

namespace App\Http\Controllers;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Http\Requests\StoreReceivableRequest;
use App\Models\Client;
use App\Models\Material;
use App\Models\Payment;
use App\Models\Receivable;
use App\Services\MoraService;
use App\Services\ReceivableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'estado', 'frecuencia']);
        $base = $this->filteredQuery($request);

        $totales = (clone $base)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(monto), 0) as monto, COALESCE(SUM(total_a_pagar), 0) as total')
            ->first();

        $pagado = (float) Payment::query()
            ->whereIn('receivable_id', (clone $base)->select('receivables.id'))
            ->sum('monto');

        $receivables = $base
            ->with('client')
            ->withSum('payments', 'monto')
            ->latest('fecha_desembolso')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('receivables.index', [
            'receivables' => $receivables,
            'filtros' => $filtros,
            'totales' => [
                'cantidad' => (int) ($totales->cantidad ?? 0),
                'monto' => (float) ($totales->monto ?? 0),
                'total' => (float) ($totales->total ?? 0),
                'pagado' => $pagado,
                'saldo' => max((float) ($totales->total ?? 0) - $pagado, 0),
            ],
            'frecuencias' => PaymentFrequency::options(),
            'estados' => ReceivableStatus::options(),
        ]);
    }

    public function create(): View
    {
        $frecuencia = PaymentFrequency::Semanal;

        return view('receivables.create', [
            'clients' => Client::query()->active()->orderBy('nombre')->get(),
            'frecuenciaDefault' => $frecuencia,
            'fechaDesembolso' => now()->toDateString(),
            'fechaPrimerPago' => $frecuencia->addTo(now())->toDateString(),
            'tasaDefault' => (float) config('cuentas.tasa_interes_default', 8),
            'tasaMoraDefault' => (float) config('cuentas.tasa_mora_default', 5),
        ]);
    }

    public function store(StoreReceivableRequest $request, ReceivableService $service): RedirectResponse
    {
        $receivable = $service->create($request->validated(), $request->user());

        return redirect()
            ->route('receivables.show', $receivable)
            ->with('success', 'Contrato '.$receivable->codigo.' creado y plan de pagos generado.');
    }

    public function show(Receivable $receivable, MoraService $mora): View
    {
        $receivable->load([
            'client',
            'creator',
            'installments',
            'payments' => fn ($query) => $query
                ->with(['user', 'materials.material'])
                ->latest('fecha_pago')
                ->latest('id'),
        ]);

        $siguiente = $receivable->installments->first(fn ($installment) => ! $installment->esta_pagada);

        return view('receivables.show', [
            'receivable' => $receivable,
            'siguiente' => $siguiente,
            'metodosPago' => PaymentMethod::options(),
            'catalogoMateriales' => Material::query()->active()->orderBy('nombre')->get(),
            'mora' => $mora->totales($receivable, today()),
        ]);
    }

    public function pdf(Receivable $receivable): Response
    {
        $receivable->load(['client', 'creator', 'installments']);

        $pdf = Pdf::loadView('pdf.receivable', ['receivable' => $receivable])
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download('plan-pagos-'.$receivable->codigo.'.pdf');
    }

    public function cancel(Request $request, Receivable $receivable, ReceivableService $service): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ], [
            'motivo.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $service->cancel($receivable, $data['motivo'] ?? null);

        return redirect()
            ->route('receivables.show', $receivable)
            ->with('success', 'El contrato fue marcado como cancelado.');
    }

    public function destroy(Receivable $receivable, ReceivableService $service): RedirectResponse
    {
        $service->delete($receivable);

        return redirect()
            ->route('receivables.index')
            ->with('success', 'Contrato eliminado definitivamente.');
    }

    private function filteredQuery(Request $request): Builder
    {
        $filtros = $request->only(['q', 'estado', 'frecuencia']);

        return Receivable::query()
            ->search($filtros['q'] ?? null)
            ->when($filtros['estado'] ?? null, fn (Builder $query, $estado) => $query->where('estado', $estado))
            ->when($filtros['frecuencia'] ?? null, fn (Builder $query, $frecuencia) => $query->where('frecuencia', $frecuencia));
    }
}
