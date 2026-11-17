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
     * capital × (saldo / monto) × (tasa mensual / 100) / 30 × días de atraso.
     */
    public function calculate(Installment $installment, CarbonInterface|string $hasta, ?float $tasaMensual = null): float
    {
        $monto = (float) $installment->monto;
        $saldo = (float) $installment->saldo;

        if ($monto <= 0 || $saldo <= 0) {
            return 0.0;
        }

        $hasta = Carbon::parse($hasta)->startOfDay();
        $vencimiento = Carbon::parse($installment->fecha_vencimiento)->startOfDay();

        if (! $vencimiento->lt($hasta)) {
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
        $base = (float) $installment->capital * ($saldo / $monto);

        return max(round($base * ($tasaMensual / 100) / $diasBase * $dias, 2), 0.0);
    }

    public function pendiente(Installment $installment, CarbonInterface|string $hasta): float
    {
        return max(round($this->calculate($installment, $hasta) - (float) $installment->mora_pagada, 2), 0.0);
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
}
