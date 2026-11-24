<?php

namespace App\Services;

use App\Enums\PayableStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethod;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayableService
{
    public function __construct(
        private readonly PayableCodeGenerator $codes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $user = null): Payable
    {
        return DB::transaction(function () use ($data, $user) {
            $fechaEmision = Carbon::parse($data['fecha_emision'] ?? today());
            $frecuencia = $data['frecuencia'] instanceof PaymentFrequency
                ? $data['frecuencia']
                : PaymentFrequency::from($data['frecuencia'] ?? PaymentFrequency::Unico->value);
            $plazo = max(1, (int) ($data['plazo'] ?? 1));
            $fechaPrimerPago = Carbon::parse($data['fecha_primer_pago'] ?? $data['fecha_vencimiento']);
            $codigo = filled($data['codigo'] ?? null)
                ? strtoupper(trim((string) $data['codigo']))
                : $this->codes->next($fechaEmision->year);

            $payable = Payable::create([
                'codigo' => $codigo,
                'supplier_id' => $data['supplier_id'],
                'concepto' => $data['concepto'],
                'monto' => round((float) $data['monto'], 2),
                'fecha_emision' => $fechaEmision->toDateString(),
                'fecha_vencimiento' => $fechaPrimerPago->toDateString(),
                'frecuencia' => $frecuencia->value,
                'plazo' => $plazo,
                'fecha_primer_pago' => $fechaPrimerPago->toDateString(),
                'estado' => PayableStatus::Pendiente->value,
                'observaciones' => $data['observaciones'] ?? null,
                'created_by' => $user?->id,
            ]);

            for ($numero = 1; $numero <= $plazo; $numero++) {
                $payable->installments()->create([
                    'numero' => $numero,
                    'fecha_vencimiento' => $this->fechaCuota($fechaPrimerPago, $frecuencia, $numero - 1)->toDateString(),
                ]);
            }

            if (! empty($data['tiene_prima']) && (float) ($data['monto_prima'] ?? 0) > 0) {
                $this->registerPayment($payable, [
                    'monto' => $data['monto_prima'],
                    'fecha_pago' => $data['fecha_prima'] ?? $fechaEmision->toDateString(),
                    'concepto' => 'Prima',
                    'metodo' => PaymentMethod::Efectivo,
                    'notas' => 'Prima abonada al registrar la obligación.',
                ], $user);

                $payable->refresh();
            }

            return $payable->load('installments');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerPayment(Payable $payable, array $data, ?User $user = null): PayablePayment
    {
        return DB::transaction(function () use ($payable, $data, $user) {
            /** @var Payable $payable */
            $payable = Payable::query()->whereKey($payable->getKey())->lockForUpdate()->firstOrFail();

            if ($payable->isCancelado()) {
                throw ValidationException::withMessages([
                    'monto' => 'No se pueden registrar pagos en una obligación cancelada.',
                ]);
            }

            $monto = round((float) ($data['monto'] ?? 0), 2);

            if ($monto <= 0) {
                throw ValidationException::withMessages([
                    'monto' => 'El monto del pago debe ser mayor que cero.',
                ]);
            }

            $saldo = $payable->saldo_pendiente;

            if ($monto > $saldo + 0.009) {
                throw ValidationException::withMessages([
                    'monto' => 'El pago no puede superar el saldo pendiente de $'.number_format($saldo, 2).'.',
                ]);
            }

            $monto = min($monto, $saldo);

            $metodo = $data['metodo'] ?? PaymentMethod::Efectivo;

            if (! $metodo instanceof PaymentMethod) {
                $metodo = PaymentMethod::tryFrom((string) $metodo) ?? PaymentMethod::Efectivo;
            }

            $payment = $payable->payments()->create([
                'fecha_pago' => Carbon::parse($data['fecha_pago'] ?? today())->toDateString(),
                'monto' => $monto,
                'concepto' => $data['concepto'] ?? $payable->concepto,
                'metodo' => $metodo->value,
                'referencia' => $data['referencia'] ?? null,
                'notas' => $data['notas'] ?? null,
                'user_id' => $user?->id,
            ]);

            $payable->refresh();
            $this->syncStatus($payable);
            $this->syncInstallments($payable);

            return $payment;
        });
    }

    public function deletePayment(PayablePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payable = $payment->payable;
            $payment->delete();

            if ($payable) {
                $payable->refresh();
                $this->syncStatus($payable);
                $this->syncInstallments($payable);
            }
        });
    }

    public function cancel(Payable $payable, ?string $motivo = null): void
    {
        if ($payable->isPagado()) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cancelar una obligación que ya está pagada.',
            ]);
        }

        $observaciones = trim(collect([
            $payable->observaciones,
            $motivo ? 'Cancelado: '.$motivo : null,
        ])->filter()->implode("\n"));

        $payable->update([
            'estado' => PayableStatus::Cancelado->value,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ]);
    }

    public function delete(Payable $payable): void
    {
        if ($payable->payments()->exists()) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede eliminar una obligación con pagos registrados. Cámbiala a cancelada.',
            ]);
        }

        $payable->delete();
    }

    /**
     * Calcula cada vencimiento desde la fecha base, no desde la cuota anterior,
     * para conservar el día anclado del primer pago (equivalente a EDATE).
     */
    private function fechaCuota(Carbon $base, PaymentFrequency $frecuencia, int $offset): Carbon
    {
        if ($frecuencia === PaymentFrequency::Mensual) {
            return $base->copy()->addMonthsNoOverflow($offset);
        }

        $dias = $frecuencia->daysBetween();

        return $dias !== null
            ? $base->copy()->addDays($offset * $dias)
            : $base->copy();
    }

    /**
     * Marca como pagadas las cuotas programadas. No hay montos por cuota:
     * cada pago registrado cubre la siguiente cuota en orden, y si la deuda
     * queda saldada se marcan todas.
     */
    public function syncInstallments(Payable $payable): void
    {
        $installments = $payable->installments()->get();

        if ($installments->isEmpty()) {
            return;
        }

        $todoPagado = $payable->saldo_pendiente <= 0.009;
        $pagadas = $payable->payments()->count();

        foreach ($installments as $index => $installment) {
            $installment->pagada = $todoPagado || $index < $pagadas;
            $installment->save();
        }
    }

    public function syncStatus(Payable $payable): void
    {
        if ($payable->isCancelado()) {
            return;
        }

        if (! $payable->payments()->exists()) {
            $estado = PayableStatus::Pendiente;
        } else {
            $estado = $payable->saldo_pendiente <= 0.009
                ? PayableStatus::Pagado
                : PayableStatus::Parcial;
        }

        if ($payable->estado !== $estado) {
            $payable->update(['estado' => $estado->value]);
        }
    }
}
