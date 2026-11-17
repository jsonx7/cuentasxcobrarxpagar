<?php

namespace App\Services;

use App\Enums\PaymentFrequency;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AmortizationService
{
    /**
     * Genera el plan de pagos con la misma lógica del archivo plandepago.xlsx:
     * interés de cada cuota = saldo pendiente * (tasa anual / 365).
     *
     * El valor de la cuota se obtiene con la función PMT.
     *
     * @return array{
     *     cuota: float,
     *     interes_total: float,
     *     total_a_pagar: float,
     *     plazo: int,
     *     tasa_interes_anual: float,
     *     rows: array<int, array<string, mixed>>
     * }
     */
    public function build(
        float $monto,
        float $tasaInteresAnual,
        int $plazo,
        PaymentFrequency $frecuencia,
        CarbonInterface|string $fechaPrimerPago,
    ): array {
        $monto = round($monto, 2);
        $tasaInteresAnual = round($tasaInteresAnual, 2);
        $plazo = max(1, $frecuencia->isSinglePayment() ? 1 : $plazo);

        $tasaPeriodo = $tasaInteresAnual / 100 / 365;

        $pago = $tasaPeriodo > 0
            ? ($monto * $tasaPeriodo) / (1 - pow(1 + $tasaPeriodo, -$plazo))
            : $monto / $plazo;

        $saldo = $monto;
        $fecha = Carbon::parse($fechaPrimerPago)->startOfDay();
        $fechaAnterior = null;
        $interesTotal = 0.0;
        $totalPagar = 0.0;
        $rows = [];

        for ($numero = 1; $numero <= $plazo; $numero++) {
            $fechaVencimiento = match (true) {
                $numero === 1 => $fecha->copy(),
                // Los pagos mensuales conservan el día del primer vencimiento
                // y se ajustan al último día del mes cuando no existe.
                $frecuencia === PaymentFrequency::Mensual => $fecha->copy()->addMonthsNoOverflow($numero - 1),
                default => $frecuencia->addTo($fechaAnterior),
            };

            $interes = round($saldo * $tasaPeriodo, 2);
            $capital = $numero === $plazo
                ? $saldo
                : round($pago - $interes, 2);

            if ($capital > $saldo) {
                $capital = $saldo;
            }

            if ($capital < 0) {
                $capital = 0.0;
            }

            $montoCuota = round($capital + $interes, 2);
            $saldo = round($saldo - $capital, 2);

            $rows[] = [
                'numero' => $numero,
                'fecha_vencimiento' => $fechaVencimiento->toDateString(),
                'monto' => $montoCuota,
                'interes' => $interes,
                'capital' => round($capital, 2),
                'saldo_pendiente' => max($saldo, 0),
                'abono_extra' => 0,
                'monto_pagado' => 0,
            ];

            $interesTotal += $interes;
            $totalPagar += $montoCuota;
            $fechaAnterior = $fechaVencimiento;
        }

        return [
            'cuota' => round($pago, 2),
            'interes_total' => round($interesTotal, 2),
            'total_a_pagar' => round($totalPagar, 2),
            'plazo' => $plazo,
            'tasa_interes_anual' => $tasaInteresAnual,
            'rows' => $rows,
        ];
    }
}
