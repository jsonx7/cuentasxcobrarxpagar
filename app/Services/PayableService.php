<?php

namespace App\Services;

use App\Enums\PayableStatus;
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
                'fecha_primer_pago' => $fechaPrimerPago->toDateString(),
                'estado' => PayableStatus::Pendiente->value,
                'observaciones' => $data['observaciones'] ?? null,
                'created_by' => $user?->id,
            ]);

            if (! empty($data['tiene_prima']) && (float) ($data['monto_prima'] ?? 0) > 0) {
                $this->registerPayment($payable, [
                    'monto' => $data['monto_prima'],
                    'fecha_pago' => $data['fecha_prima'] ?? $fechaEmision->toDateString(),
                    'concepto' => 'Prima',
                    'es_prima' => true,
                    'metodo' => PaymentMethod::Efectivo,
                    'notas' => 'Prima abonada al registrar la obligación.',
                ], $user);

                $payable->refresh();
            }

            return $payable;
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
                'es_prima' => (bool) ($data['es_prima'] ?? false),
                'metodo' => $metodo->value,
                'referencia' => $data['referencia'] ?? null,
                'notas' => $data['notas'] ?? null,
                'user_id' => $user?->id,
            ]);

            $payable->refresh();
            $this->syncStatus($payable);

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
