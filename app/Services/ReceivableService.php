<?php

namespace App\Services;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableService
{
    public function __construct(
        private readonly AmortizationService $amortization,
        private readonly ReceivableCodeGenerator $codes,
        private readonly MoraService $mora,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $user = null): Receivable
    {
        return DB::transaction(function () use ($data, $user) {
            $frecuencia = $data['frecuencia'] instanceof PaymentFrequency
                ? $data['frecuencia']
                : PaymentFrequency::from($data['frecuencia']);

            $fechaDesembolso = Carbon::parse($data['fecha_desembolso'] ?? today());
            $plan = $this->amortization->build(
                (float) $data['monto'],
                (float) ($data['tasa_interes_anual'] ?? config('cuentas.tasa_interes_default', 8)),
                (int) ($data['plazo'] ?? 1),
                $frecuencia,
                $data['fecha_primer_pago'] ?? today(),
            );

            $receivable = Receivable::create([
                'codigo' => $this->codes->next($fechaDesembolso->year),
                'client_id' => $data['client_id'],
                'monto' => round((float) $data['monto'], 2),
                'tasa_interes_anual' => $plan['tasa_interes_anual'],
                'plazo' => $plan['plazo'],
                'frecuencia' => $frecuencia->value,
                'fecha_desembolso' => $fechaDesembolso->toDateString(),
                'fecha_primer_pago' => Carbon::parse($data['fecha_primer_pago'] ?? today())->toDateString(),
                'cuota' => $plan['cuota'],
                'interes_total' => $plan['interes_total'],
                'total_a_pagar' => $plan['total_a_pagar'],
                'tasa_mora_mensual' => round(
                    (float) ($data['tasa_mora_mensual'] ?? config('cuentas.tasa_mora_default', 5)), 2
                ),
                'estado' => ReceivableStatus::Activo->value,
                'observaciones' => $data['observaciones'] ?? null,
                'created_by' => $user?->id,
            ]);

            foreach ($plan['rows'] as $row) {
                $receivable->installments()->create($row);
            }

            return $receivable->load('installments');
        });
    }

    /**
     * Registra un abono en dinero y/o materiales, aplicando primero las cuotas
     * más antiguas y luego la mora pendiente de las cuotas vencidas.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerPayment(Receivable $receivable, array $data, ?User $user = null): Payment
    {
        return DB::transaction(function () use ($receivable, $data, $user) {
            /** @var Receivable $receivable */
            $receivable = Receivable::query()->whereKey($receivable->getKey())->lockForUpdate()->firstOrFail();

            if ($receivable->isCancelado()) {
                throw ValidationException::withMessages([
                    'monto' => 'No se pueden registrar abonos en un contrato cancelado.',
                ]);
            }

            $monto = round((float) ($data['monto'] ?? 0), 2);

            if ($monto < 0) {
                throw ValidationException::withMessages([
                    'monto' => 'El monto del abono no puede ser negativo.',
                ]);
            }

            $materiales = collect($data['materiales'] ?? [])
                ->filter(fn ($material) => is_array($material)
                    && ! blank($material['material_id'] ?? null)
                    && (float) ($material['cantidad'] ?? 0) > 0)
                ->map(function (array $material): array {
                    $cantidad = round((float) $material['cantidad'], 3);
                    $precioUnitario = round((float) ($material['precio_unitario'] ?? 0), 2);

                    return [
                        'material_id' => (int) $material['material_id'],
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'monto' => round($cantidad * $precioUnitario, 2),
                    ];
                })
                ->values();

            if ($monto <= 0 && $materiales->isEmpty()) {
                throw ValidationException::withMessages([
                    'monto' => 'Debe ingresar un monto o agregar al menos un material.',
                ]);
            }

            $montoMateriales = round((float) $materiales->sum('monto'), 2);
            $total = round($monto + $montoMateriales, 2);
            $fechaPago = Carbon::parse($data['fecha_pago'] ?? today())->toDateString();

            $montoMora = round((float) ($data['monto_mora'] ?? 0), 2);

            if ($montoMora < 0) {
                throw ValidationException::withMessages([
                    'monto_mora' => 'El monto para mora no puede ser negativo.',
                ]);
            }

            if ($montoMora > $total) {
                throw ValidationException::withMessages([
                    'monto_mora' => 'El monto para mora no puede superar el total del abono.',
                ]);
            }

            $cuotasMora = $receivable->installments()
                ->whereDate('fecha_vencimiento', '<', $fechaPago)
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero')
                ->lockForUpdate()
                ->get();
            $cuotasMora->each(fn (Installment $installment) => $installment->setRelation('receivable', $receivable));

            // Se calcula antes de distribuir el abono a capital: la mora se
            // determina con el saldo vigente al momento del pago, no después.
            $moraPorCuota = [];
            foreach ($cuotasMora as $cuotaMora) {
                $pendienteCuota = round($this->mora->pendiente($cuotaMora, $fechaPago), 2);

                if ($pendienteCuota > 0) {
                    $moraPorCuota[$cuotaMora->id] = $pendienteCuota;
                }
            }
            $moraPendiente = round(array_sum($moraPorCuota), 2);

            if ($montoMora > $moraPendiente + 0.009) {
                throw ValidationException::withMessages([
                    'monto_mora' => 'El monto para mora no puede superar la mora pendiente de $'
                        .number_format($moraPendiente, 2).'.',
                ]);
            }

            $montoMora = min($montoMora, $moraPendiente);
            $montoCuotas = round($total - $montoMora, 2);
            $saldo = $receivable->saldo_pendiente;

            if ($montoCuotas > $saldo + 0.009) {
                throw ValidationException::withMessages([
                    'monto' => 'El abono no puede superar el saldo pendiente de $'.number_format($saldo, 2).'.',
                ]);
            }

            $montoCuotas = min($montoCuotas, $saldo);

            $metodo = $data['metodo'] ?? PaymentMethod::Efectivo;

            if (! $metodo instanceof PaymentMethod) {
                $metodo = PaymentMethod::tryFrom((string) $metodo) ?? PaymentMethod::Efectivo;
            }

            if ($materiales->isNotEmpty()) {
                $metodo = $monto > 0 ? PaymentMethod::Mixto : PaymentMethod::Material;
            }

            $payment = Payment::create([
                'receivable_id' => $receivable->id,
                'fecha_pago' => $fechaPago,
                'monto' => $montoCuotas,
                'monto_mora' => $montoMora,
                'metodo' => $metodo->value,
                'referencia' => $data['referencia'] ?? null,
                'notas' => $data['notas'] ?? null,
                'user_id' => $user?->id,
            ]);

            foreach ($materiales as $material) {
                $payment->materials()->create($material);
            }

            $installments = $receivable->installments()
                ->whereColumn('monto_pagado', '<', 'monto')
                ->orderBy('numero')
                ->lockForUpdate()
                ->get();

            $targetId = isset($data['installment_id']) ? (int) $data['installment_id'] : null;

            if ($targetId !== null) {
                $installments = $installments
                    ->sortBy(fn (Installment $installment) => $installment->id === $targetId ? 0 : 1)
                    ->values();
            }

            $restante = $montoCuotas;

            foreach ($installments as $installment) {
                if ($restante <= 0.004) {
                    break;
                }

                $saldoCuota = round((float) $installment->monto - (float) $installment->monto_pagado, 2);

                if ($saldoCuota <= 0) {
                    continue;
                }

                $aplicado = round(min($restante, $saldoCuota), 2);

                if ($aplicado <= 0) {
                    continue;
                }

                // La base se toma antes de aplicar el abono. Si la cuota queda
                // saldada, se guarda para que la mora siga contando días.
                $baseCuota = $this->mora->base($installment);
                $installment->mora_fecha_hasta = null;

                $payment->allocations()->create([
                    'installment_id' => $installment->id,
                    'monto' => $aplicado,
                    'tipo' => 'cuota',
                ]);

                $installment->monto_pagado = round((float) $installment->monto_pagado + $aplicado, 2);

                if ($installment->saldo <= 0 && $baseCuota > 0) {
                    $this->mora->guardarBase($installment, $baseCuota);
                }

                $installment->save();

                $restante = round($restante - $aplicado, 2);
            }

            if ($montoMora > 0.004) {
                $moraRestante = $montoMora;

                foreach ($cuotasMora as $installment) {
                    if ($moraRestante <= 0.004) {
                        break;
                    }

                    $pendienteCuota = $moraPorCuota[$installment->id] ?? 0.0;

                    if ($pendienteCuota <= 0.004) {
                        continue;
                    }

                    $aplicado = round(min($moraRestante, $pendienteCuota), 2);

                    if ($aplicado <= 0) {
                        continue;
                    }

                    $installment->refresh()->setRelation('receivable', $receivable);

                    $payment->allocations()->create([
                        'installment_id' => $installment->id,
                        'monto' => $aplicado,
                        'tipo' => 'mora',
                    ]);

                    $installment->mora_pagada = round((float) $installment->mora_pagada + $aplicado, 2);
                    $this->mora->cerrar($installment, $fechaPago);
                    $installment->save();

                    $moraRestante = round($moraRestante - $aplicado, 2);
                }
            }

            $receivable->refresh();
            $this->syncStatus($receivable);

            return $payment->load(['installments', 'user', 'materials.material']);
        });
    }

    public function deletePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->load('allocations.installment');
            $receivable = $payment->receivable;

            foreach ($payment->allocations as $allocation) {
                $installment = $allocation->installment;

                if (! $installment) {
                    continue;
                }

                if ($allocation->tipo === 'mora') {
                    $installment->mora_pagada = max(
                        0,
                        round((float) $installment->mora_pagada - (float) $allocation->monto, 2)
                    );
                } else {
                    $installment->monto_pagado = max(
                        0,
                        round((float) $installment->monto_pagado - (float) $allocation->monto, 2)
                    );
                }

                $this->mora->sincronizarAlRevertir($installment);
                $installment->save();
            }

            $payment->allocations()->delete();
            $payment->materials()->delete();
            $payment->delete();

            if ($receivable) {
                $receivable->refresh();
                $this->syncStatus($receivable);
            }
        });
    }

    public function cancel(Receivable $receivable, ?string $motivo = null): void
    {
        if ($receivable->isPagado()) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cancelar un contrato que ya está pagado.',
            ]);
        }

        $observaciones = trim(collect([
            $receivable->observaciones,
            $motivo ? 'Cancelado: '.$motivo : null,
        ])->filter()->implode("\n"));

        $receivable->update([
            'estado' => ReceivableStatus::Cancelado->value,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ]);
    }

    public function delete(Receivable $receivable): void
    {
        if ($receivable->payments()->exists()) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede eliminar un contrato con abonos registrados. Cámbialo a cancelado.',
            ]);
        }

        $receivable->delete();
    }

    public function syncStatus(Receivable $receivable): void
    {
        if ($receivable->isCancelado()) {
            return;
        }

        $estado = $receivable->saldo_pendiente <= 0.009
            ? ReceivableStatus::Pagado
            : ReceivableStatus::Activo;

        if ($receivable->estado !== $estado) {
            $receivable->update(['estado' => $estado->value]);
        }
    }
}
