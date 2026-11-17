<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\RegisterPayablePaymentRequest;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Services\PayableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayablePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'desde', 'hasta', 'metodo']);

        $query = PayablePayment::query()
            ->with(['payable.supplier', 'user'])
            ->betweenDates($filtros['desde'] ?? null, $filtros['hasta'] ?? null)
            ->when($filtros['metodo'] ?? null, fn (Builder $query, $metodo) => $query->where('metodo', $metodo))
            ->when($filtros['q'] ?? null, function (Builder $query, $term) {
                $term = trim($term);

                $query->where(function (Builder $query) use ($term) {
                    $query->where('referencia', 'like', "%{$term}%")
                        ->orWhere('notas', 'like', "%{$term}%")
                        ->orWhereHas('payable', function (Builder $query) use ($term) {
                            $query->where('codigo', 'like', "%{$term}%")
                                ->orWhere('concepto', 'like', "%{$term}%")
                                ->orWhereHas('supplier', function (Builder $query) use ($term) {
                                    $query->where('nombre', 'like', "%{$term}%")
                                        ->orWhere('nit', 'like', "%{$term}%");
                                });
                        });
                });
            });

        $totales = (clone $query)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(monto), 0) as total')
            ->first();

        $payablePayments = $query
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('payable-payments.index', [
            'payablePayments' => $payablePayments,
            'filtros' => $filtros,
            'metodos' => PaymentMethod::options(),
            'totales' => [
                'cantidad' => (int) ($totales->cantidad ?? 0),
                'total' => (float) ($totales->total ?? 0),
            ],
        ]);
    }

    public function store(RegisterPayablePaymentRequest $request, Payable $payable, PayableService $service): RedirectResponse
    {
        $service->registerPayment($payable, $request->validated(), $request->user());

        return redirect()
            ->route('payables.show', $payable)
            ->with('success', 'Pago registrado correctamente.');
    }

    public function destroy(PayablePayment $payablePayment, PayableService $service): RedirectResponse
    {
        $payable = $payablePayment->payable;
        $service->deletePayment($payablePayment);

        return redirect()
            ->route('payables.show', $payable)
            ->with('success', 'Pago eliminado y obligación actualizada.');
    }
}
