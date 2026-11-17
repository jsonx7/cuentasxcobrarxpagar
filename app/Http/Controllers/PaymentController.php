<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\RegisterPaymentRequest;
use App\Models\Payment;
use App\Models\PaymentMaterial;
use App\Models\Receivable;
use App\Services\ReceivableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'desde', 'hasta', 'metodo']);

        $query = Payment::query()
            ->with(['receivable.client', 'user', 'materials.material'])
            ->betweenDates($filtros['desde'] ?? null, $filtros['hasta'] ?? null)
            ->when($filtros['metodo'] ?? null, fn (Builder $query, $metodo) => $query->where('metodo', $metodo))
            ->when($filtros['q'] ?? null, function (Builder $query, $term) {
                $term = trim($term);

                $query->where(function (Builder $query) use ($term) {
                    $query->where('referencia', 'like', "%{$term}%")
                        ->orWhere('notas', 'like', "%{$term}%")
                        ->orWhereHas('receivable', function (Builder $query) use ($term) {
                            $query->where('codigo', 'like', "%{$term}%")
                                ->orWhereHas('client', function (Builder $query) use ($term) {
                                    $query->where('nombre', 'like', "%{$term}%")
                                        ->orWhere('dui', 'like', "%{$term}%")
                                        ->orWhere('nit', 'like', "%{$term}%");
                                });
                        });
                });
            });

        $totales = (clone $query)
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(monto), 0) as monto, COALESCE(SUM(monto_mora), 0) as mora')
            ->first();

        $valorMateriales = (float) PaymentMaterial::query()
            ->whereIn('payment_id', (clone $query)->select('payments.id'))
            ->sum('monto');

        $payments = $query
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'filtros' => $filtros,
            'metodos' => PaymentMethod::options(),
            'totales' => [
                'cantidad' => (int) ($totales->cantidad ?? 0),
                'monto' => (float) ($totales->monto ?? 0),
                'mora' => (float) ($totales->mora ?? 0),
                'materiales' => $valorMateriales,
                'total' => round((float) ($totales->monto ?? 0) + (float) ($totales->mora ?? 0) + $valorMateriales, 2),
            ],
        ]);
    }

    public function store(RegisterPaymentRequest $request, Receivable $receivable, ReceivableService $service): RedirectResponse
    {
        $service->registerPayment($receivable, $request->validated(), $request->user());

        return redirect()
            ->route('receivables.show', $receivable)
            ->with('success', 'Abono registrado correctamente.');
    }

    public function destroy(Payment $payment, ReceivableService $service): RedirectResponse
    {
        $receivable = $payment->receivable;
        $service->deletePayment($payment);

        return redirect()
            ->route('receivables.show', $receivable)
            ->with('success', 'Abono eliminado y cuotas actualizadas.');
    }
}
