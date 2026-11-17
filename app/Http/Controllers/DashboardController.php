<?php

namespace App\Http\Controllers;

use App\Enums\PayableStatus;
use App\Enums\ReceivableStatus;
use App\Models\Client;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Payment;
use App\Models\Receivable;
use App\Models\Supplier;
use App\Services\MoraService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MoraService $mora): View
    {
        $activos = Receivable::query()->where('estado', ReceivableStatus::Activo->value);

        $totalPorCobrar = (float) (clone $activos)->sum('total_a_pagar');
        $totalCobrado = (float) Payment::query()
            ->whereIn('receivable_id', (clone $activos)->select('receivables.id'))
            ->sum('monto');

        $vencidas = Installment::query()
            ->overdue()
            ->whereHas('receivable', fn ($query) => $query->where('estado', ReceivableStatus::Activo->value))
            ->with('receivable')
            ->get();

        $cuotasVencidas = $vencidas->count();
        $montoVencido = round($vencidas->sum(fn (Installment $cuota) => max((float) $cuota->monto - (float) $cuota->monto_pagado, 0)), 2);
        $moraPendiente = round($vencidas->sum(fn (Installment $cuota) => $mora->pendiente($cuota, today())), 2);

        $cobradoMes = (float) Payment::query()
            ->whereBetween('fecha_pago', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('monto');

        $proximasCuotas = Installment::query()
            ->with(['receivable.client'])
            ->whereColumn('monto_pagado', '<', 'monto')
            ->whereHas('receivable', fn ($query) => $query->where('estado', ReceivableStatus::Activo->value))
            ->orderBy('fecha_vencimiento')
            ->limit(8)
            ->get();

        $contratosRecientes = (clone $activos)
            ->with('client')
            ->withSum('payments', 'monto')
            ->latest('fecha_desembolso')
            ->latest('id')
            ->limit(8)
            ->get();

        $porCliente = Receivable::query()
            ->join('clients', 'clients.id', '=', 'receivables.client_id')
            ->where('receivables.estado', ReceivableStatus::Activo->value)
            ->select('clients.id', 'clients.nombre')
            ->selectRaw('COUNT(receivables.id) as contratos, COALESCE(SUM(receivables.total_a_pagar), 0) as total')
            ->groupBy('clients.id', 'clients.nombre')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $obligacionesActivas = Payable::query()
            ->whereNotIn('estado', [PayableStatus::Pagado->value, PayableStatus::Cancelado->value]);

        $totalPorPagar = (float) (clone $obligacionesActivas)->sum('monto');
        $pagadoObligaciones = (float) PayablePayment::query()
            ->whereIn('payable_id', (clone $obligacionesActivas)->select('payables.id'))
            ->sum('monto');

        $porProveedor = Payable::query()
            ->join('suppliers', 'suppliers.id', '=', 'payables.supplier_id')
            ->whereNotIn('payables.estado', [PayableStatus::Pagado->value, PayableStatus::Cancelado->value])
            ->select('suppliers.id', 'suppliers.nombre')
            ->selectRaw('COUNT(payables.id) as obligaciones, COALESCE(SUM(payables.monto), 0) as total')
            ->groupBy('suppliers.id', 'suppliers.nombre')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $obligacionesPorVencer = (clone $obligacionesActivas)
            ->with('supplier')
            ->whereDate('fecha_vencimiento', '>=', today()->toDateString())
            ->orderBy('fecha_vencimiento')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'clientesActivos' => Client::query()->where('activo', true)->count(),
            'proveedoresActivos' => Supplier::query()->where('activo', true)->count(),
            'contratosActivos' => (clone $activos)->count(),
            'totalPorCobrar' => round($totalPorCobrar, 2),
            'totalCobrado' => round($totalCobrado, 2),
            'saldoPorCobrar' => round(max($totalPorCobrar - $totalCobrado, 0), 2),
            'cuotasVencidas' => $cuotasVencidas,
            'montoVencido' => $montoVencido,
            'moraPendiente' => $moraPendiente,
            'cobradoMes' => round($cobradoMes, 2),
            'proximasCuotas' => $proximasCuotas,
            'contratosRecientes' => $contratosRecientes,
            'porCliente' => $porCliente,
            'maxCliente' => max((float) $porCliente->max('total'), 1),
            'totalPorPagar' => round($totalPorPagar, 2),
            'saldoPorPagar' => round(max($totalPorPagar - $pagadoObligaciones, 0), 2),
            'pagadoMes' => round((float) PayablePayment::query()
                ->whereBetween('fecha_pago', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('monto'), 2),
            'porProveedor' => $porProveedor,
            'maxProveedor' => max((float) $porProveedor->max('total'), 1),
            'obligacionesPorVencer' => $obligacionesPorVencer,
        ]);
    }
}
