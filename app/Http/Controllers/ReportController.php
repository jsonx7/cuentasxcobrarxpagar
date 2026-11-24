<?php

namespace App\Http\Controllers;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Payment;
use App\Models\Receivable;
use App\Models\Supplier;
use App\Services\MoraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(MoraService $mora): View
    {
        $activos = Receivable::query()->where('estado', ReceivableStatus::Activo->value);

        $vencidas = Installment::query()
            ->overdue()
            ->whereHas('receivable', fn ($query) => $query->where('estado', ReceivableStatus::Activo->value))
            ->get();

        $conMora = Installment::query()
            ->conMoraPendiente()
            ->whereHas('receivable', fn ($query) => $query->whereIn('estado', [
                ReceivableStatus::Activo->value,
                ReceivableStatus::Pagado->value,
            ]))
            ->get();

        return view('reports.index', [
            'contratosActivos' => (clone $activos)->count(),
            'cartera' => round((float) (clone $activos)->sum('total_a_pagar'), 2),
            'cuotasVencidas' => $vencidas->count(),
            'moraPendiente' => round($conMora->sum(fn (Installment $cuota) => $mora->pendiente($cuota, today())), 2),
            'cobradoMes' => round((float) Payment::query()
                ->whereBetween('fecha_pago', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('monto'), 2),
            'pagadoMes' => round((float) PayablePayment::query()
                ->whereBetween('fecha_pago', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('monto'), 2),
            'estados' => ReceivableStatus::options(),
            'frecuencias' => PaymentFrequency::options(),
            'metodos' => PaymentMethod::options(),
        ]);
    }

    public function consolidated(Request $request): View
    {
        $data = $this->consolidatedData($request);

        return view('reports.consolidated', [
            ...$data,
            'filtros' => $request->only(['q', 'estado', 'frecuencia', 'proveedor']),
            'estados' => ReceivableStatus::options(),
            'frecuencias' => PaymentFrequency::options(),
            'suppliers' => Supplier::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function consolidatedPdf(Request $request): Response
    {
        $data = $this->consolidatedData($request);

        $pdf = Pdf::loadView('pdf.consolidated', $data)
            ->setPaper('letter', 'landscape')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download('consolidado-'.now()->format('Y-m-d').'.pdf');
    }

    public function overdue(Request $request, MoraService $mora): View
    {
        $data = $this->overdueData($request, $mora);

        return view('reports.overdue', [
            ...$data,
            'filtros' => $request->only(['q']),
        ]);
    }

    public function overduePdf(Request $request, MoraService $mora): Response
    {
        $data = $this->overdueData($request, $mora);

        $pdf = Pdf::loadView('pdf.overdue', $data)
            ->setPaper('letter', 'landscape')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download('cuotas-vencidas-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array{
     *     receivables: Collection<int, Receivable>,
     *     payables: Collection<int, Payable>,
     *     totales: array<string, mixed>,
     *     totalesCobrar: array<string, float|int>,
     *     totalesPagar: array<string, float|int>
     * }
     */
    private function consolidatedData(Request $request): array
    {
        $filtros = $request->only(['q', 'estado', 'frecuencia', 'proveedor']);

        $receivables = Receivable::query()
            ->with('client')
            ->withSum('payments', 'monto')
            ->search($filtros['q'] ?? null)
            ->when($filtros['estado'] ?? null, fn (Builder $query, $estado) => $query->where('estado', $estado))
            ->when($filtros['frecuencia'] ?? null, fn (Builder $query, $frecuencia) => $query->where('frecuencia', $frecuencia))
            ->orderByDesc('fecha_desembolso')
            ->orderByDesc('id')
            ->get();

        $payables = Payable::query()
            ->with('supplier')
            ->withSum('payments', 'monto')
            ->withCount(['payments as abonos_count' => fn ($query) => $query->where('es_prima', false)])
            ->search($filtros['q'] ?? null)
            ->when($filtros['estado'] ?? null, fn (Builder $query, $estado) => $query->where('estado', $estado))
            ->when($filtros['proveedor'] ?? null, fn (Builder $query, $proveedor) => $query->where('supplier_id', $proveedor))
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id')
            ->get();

        $montoCobrar = $receivables->sum(fn (Receivable $receivable) => (float) $receivable->monto);
        $totalCobrar = $receivables->sum(fn (Receivable $receivable) => (float) $receivable->total_a_pagar);
        $pagadoCobrar = $receivables->sum(fn (Receivable $receivable) => $receivable->total_pagado);

        $montoPagar = $payables->sum(fn (Payable $payable) => (float) $payable->monto);
        $pagadoPagar = $payables->sum(fn (Payable $payable) => $payable->total_pagado);

        $totalesCobrar = [
            'cantidad' => $receivables->count(),
            'monto' => round($montoCobrar, 2),
            'total' => round($totalCobrar, 2),
            'pagado' => round($pagadoCobrar, 2),
            'saldo' => round(max($totalCobrar - $pagadoCobrar, 0), 2),
        ];

        $totalesPagar = [
            'cantidad' => $payables->count(),
            'monto' => round($montoPagar, 2),
            'pagado' => round($pagadoPagar, 2),
            'saldo' => round(max($montoPagar - $pagadoPagar, 0), 2),
        ];

        return [
            'receivables' => $receivables,
            'payables' => $payables,
            'totalesCobrar' => $totalesCobrar,
            'totalesPagar' => $totalesPagar,
            'totales' => [
                ...$totalesCobrar,
                'cobrar' => $totalesCobrar,
                'pagar' => $totalesPagar,
            ],
        ];
    }

    /**
     * @return array{installments: Collection<int, Installment>, totales: array<string, float|int>}
     */
    private function overdueData(Request $request, MoraService $mora): array
    {
        $filtros = $request->only(['q']);

        $installments = Installment::query()
            ->with(['receivable.client'])
            ->conMoraPendiente()
            ->whereHas('receivable', function (Builder $query) use ($filtros) {
                $query->where('estado', '!=', ReceivableStatus::Cancelado->value)
                    ->search($filtros['q'] ?? null);
            })
            ->orderBy('fecha_vencimiento')
            ->orderBy('receivable_id')
            ->orderBy('numero')
            ->get();

        $installments->each(function (Installment $installment) use ($mora) {
            $installment->setAttribute('mora_pendiente', $mora->pendiente($installment, today()));
        });

        $installments = $installments
            ->filter(fn (Installment $installment) => (float) $installment->mora_pendiente > 0.004)
            ->values();

        return [
            'installments' => $installments,
            'totales' => [
                'cantidad' => $installments->count(),
                'contratos' => $installments->pluck('receivable_id')->unique()->count(),
                'monto' => round($installments->sum(fn (Installment $installment) => $installment->saldo), 2),
                'mora' => round($installments->sum(fn (Installment $installment) => (float) $installment->mora_pendiente), 2),
            ],
        ];
    }
}
