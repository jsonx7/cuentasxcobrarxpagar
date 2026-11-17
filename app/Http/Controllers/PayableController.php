<?php

namespace App\Http\Controllers;

use App\Enums\PayableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\StorePayableRequest;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Supplier;
use App\Services\PayableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayableController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'estado', 'proveedor', 'desde', 'hasta']);
        $base = $this->filteredQuery($request);

        $totales = (clone $base)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(monto), 0) as monto')
            ->first();

        $pagado = (float) PayablePayment::query()
            ->whereIn('payable_id', (clone $base)->select('payables.id'))
            ->sum('monto');

        $payables = $base
            ->with('supplier')
            ->withSum('payments', 'monto')
            ->latest('fecha_emision')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('payables.index', [
            'payables' => $payables,
            'filtros' => $filtros,
            'totales' => [
                'cantidad' => (int) ($totales->cantidad ?? 0),
                'monto' => (float) ($totales->monto ?? 0),
                'pagado' => $pagado,
                'saldo' => max((float) ($totales->monto ?? 0) - $pagado, 0),
            ],
            'estados' => PayableStatus::options(),
            'suppliers' => Supplier::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function create(): View
    {
        return view('payables.create', [
            'suppliers' => Supplier::query()->active()->orderBy('nombre')->get(),
        ]);
    }

    public function store(StorePayableRequest $request, PayableService $service): RedirectResponse
    {
        $payable = $service->create($request->validated(), $request->user());

        return redirect()
            ->route('payables.show', $payable)
            ->with('success', 'Obligación '.$payable->codigo.' registrada correctamente.');
    }

    public function show(Payable $payable): View
    {
        $payable->load([
            'supplier',
            'creator',
            'payments' => fn ($query) => $query->with('user')->latest('fecha_pago')->latest('id'),
        ]);

        return view('payables.show', [
            'payable' => $payable,
            'metodosPago' => PaymentMethod::options(),
        ]);
    }

    public function cancel(Request $request, Payable $payable, PayableService $service): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ], [
            'motivo.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        $service->cancel($payable, $data['motivo'] ?? null);

        return redirect()
            ->route('payables.show', $payable)
            ->with('success', 'La obligación fue marcada como cancelada.');
    }

    public function destroy(Payable $payable, PayableService $service): RedirectResponse
    {
        $service->delete($payable);

        return redirect()
            ->route('payables.index')
            ->with('success', 'Obligación eliminada definitivamente.');
    }

    private function filteredQuery(Request $request): Builder
    {
        $filtros = $request->only(['q', 'estado', 'proveedor', 'desde', 'hasta']);

        return Payable::query()
            ->search($filtros['q'] ?? null)
            ->when($filtros['estado'] ?? null, fn (Builder $query, $estado) => $query->where('estado', $estado))
            ->when($filtros['proveedor'] ?? null, fn (Builder $query, $proveedor) => $query->where('supplier_id', $proveedor))
            ->when($filtros['desde'] ?? null, fn (Builder $query, $desde) => $query->whereDate('fecha_vencimiento', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn (Builder $query, $hasta) => $query->whereDate('fecha_vencimiento', '<=', $hasta));
    }
}
