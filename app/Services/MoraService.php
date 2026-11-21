<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Receivable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class MoraService
{
    /**
     * Mora no capitalizada de una cuota vencida:
     * base × (tasa mensual / 100) / 30 × días de atraso.
     *
     * Mientras la cuota tiene saldo, la base se calcula con el saldo vigente.
     * Al saldarla se guarda esa base en `mora_base` para que la mora siga
     * contando días hasta la fecha en que se pague, aunque el préstamo ya
     * esté cancelado.
     */
    public function calculate(Installment $installment, CarbonInterface|string $hasta, ?float $tasaMensual = null): float
    {
        if ((float) $installment->monto <= 0) {
            return 0.0;
        }

        $hasta = Carbon::parse($hasta)->startOfDay();
        $vencimiento = Carbon::parse($installment->fecha_vencimiento)->startOfDay();
        $saldo = $this->saldo($installment);

        if ($saldo <= 0 && $installment->mora_fecha_hasta !== null) {
            $limite = Carbon::parse($installment->mora_fecha_hasta)->startOfDay();

            if ($hasta->gt($limite)) {
                $hasta = $limite;
            }
        }

        if (! $vencimiento->lt($hasta)) {
            return 0.0;
        }

        $base = $this->base($installment);

        if ($base <= 0) {
            return 0.0;
        }

        if ($tasaMensual === null) {
            $contrato = $installment->receivable;
            $tasaMensual = $contrato !== null && $contrato->tasa_mora_mensual !== null
                ? (float) $contrato->tasa_mora_mensual
                : (float) config('cuentas.tasa_mora_default', 5);
        }

        $dias = abs($vencimiento->diffInDays($hasta));
        $diasBase = max(1, (int) config('cuentas.dias_mora_base', 30));

        return max(round($base * ($tasaMensual / 100) / $diasBase * $dias, 2), 0.0);
    }

    /**
     * Base de cálculo: capital × (saldo / monto) mientras hay saldo; una vez
     * saldada la cuota, se usa la base guardada al momento del pago.
     */
    public function base(Installment $installment): float
    {
        $monto = (float) $installment->monto;
        $saldo = $this->saldo($installment);

        if ($monto <= 0) {
            return 0.0;
        }

        if ($saldo > 0) {
            return round((float) $installment->capital * ($saldo / $monto), 4);
        }

        return $installment->mora_base !== null ? (float) $installment->mora_base : 0.0;
    }

    public function pendiente(Installment $installment, CarbonInterface|string $hasta): float
    {
        return max(round($this->calculate($installment, $hasta) - (float) $installment->mora_pagada, 2), 0.0);
    }

    /**
     * Guarda la base vigente cuando una cuota queda saldada con un abono.
     */
    public function guardarBase(Installment $installment, float $base): void
    {
        $installment->mora_base = round($base, 4);
    }

    /**
     * Detiene el conteo de días cuando la mora queda totalmente pagada.
     */
    public function cerrar(Installment $installment, CarbonInterface|string $fechaPago): void
    {
        if ($this->pendiente($installment, $fechaPago) <= 0.004) {
            $installment->mora_fecha_hasta = Carbon::parse($fechaPago)->toDateString();
        }
    }

    /**
     * Restaura el cálculo dinámico al eliminar un abono que había saldado la
     * cuota, cerrado la mora o ambos.
     */
    public function sincronizarAlRevertir(Installment $installment): void
    {
        if ($this->saldo($installment) > 0) {
            $installment->mora_base = null;
            $installment->mora_fecha_hasta = null;
        }

        if ($this->pendiente($installment, today()) > 0.004) {
            $installment->mora_fecha_hasta = null;
        }
    }

    /**
     * @return array{calculada: float, pagada: float, pendiente: float}
     */
    public function totales(Receivable $receivable, CarbonInterface|string $hasta): array
    {
        $installments = $receivable->relationLoaded('installments')
            ? $receivable->installments
            : $receivable->installments()->get();

        $calculada = 0.0;
        $pagada = 0.0;
        $pendiente = 0.0;

        foreach ($installments as $installment) {
            $installment->setRelation('receivable', $receivable);

            $calculada += $this->calculate($installment, $hasta);
            $pagada += (float) $installment->mora_pagada;
            $pendiente += $this->pendiente($installment, $hasta);
        }

        return [
            'calculada' => round($calculada, 2),
            'pagada' => round($pagada, 2),
            'pendiente' => round($pendiente, 2),
        ];
    }

    private function saldo(Installment $installment): float
    {
        return max(round((float) $installment->monto - (float) $installment->monto_pagado, 2), 0);
    }
}
