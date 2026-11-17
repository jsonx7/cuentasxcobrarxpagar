<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de cuotas vencidas</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1e293b; margin: 0; }
        h1 { font-size: 18px; margin: 0; color: #0f172a; }
        p { margin: 0; }
        .muted { color: #64748b; }
        .header { border-bottom: 3px solid #e11d48; padding-bottom: 10px; margin-bottom: 12px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .brand { display: inline-block; background: #e11d48; color: #fff; padding: 6px 9px; border-radius: 5px; font-weight: bold; font-size: 12px; }
        .code { text-align: right; font-size: 11px; font-weight: bold; color: #e11d48; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary td { border: 1px solid #fecdd3; padding: 6px 9px; background: #fff1f2; }
        .summary .title { display: block; font-size: 7.5px; text-transform: uppercase; color: #9f1239; margin-bottom: 2px; }
        .summary .amount { font-size: 11px; font-weight: bold; color: #881337; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead th { background: #0f172a; color: #fff; font-size: 7.5px; text-transform: uppercase; padding: 5px 4px; text-align: left; }
        table.items tbody td { border-bottom: 1px solid #e2e8f0; padding: 4px 4px; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #fff7f8; }
        table.items tfoot td { border-top: 2px solid #cbd5e1; padding: 5px 4px; font-weight: bold; background: #f1f5f9; font-size: 8.5px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .late { color: #be123c; font-weight: bold; }
        .footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 8px; color: #94a3b8; font-size: 7.5px; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <span class="brand">CR</span>
                    <h1 style="margin-top: 6px;">Reporte de cuotas vencidas</h1>
                    <p class="muted">{{ config('app.name') }}</p>
                </td>
                <td class="code">
                    {{ $totales['cantidad'] }} cuota(s) vencida(s)<br>
                    <span class="muted" style="font-weight: normal;">Generado el {{ now()->format('d/m/Y H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="title">Saldo vencido</span>
                <span class="amount">${{ number_format($totales['monto'], 2) }}</span>
            </td>
            <td>
                <span class="title">Cuotas vencidas</span>
                <span class="amount">{{ $totales['cantidad'] }}</span>
            </td>
            <td>
                <span class="title">Contratos afectados</span>
                <span class="amount">{{ $totales['contratos'] }}</span>
            </td>
            <td>
                <span class="title">Mora pendiente</span>
                <span class="amount">${{ number_format($totales['mora'], 2) }}</span>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 11%;">Contrato</th>
                <th style="width: 20%;">Cliente</th>
                <th style="width: 6%;" class="center">Cuota</th>
                <th style="width: 11%;">Vence</th>
                <th style="width: 7%;" class="center">Atraso</th>
                <th style="width: 9%;" class="right">Cuota</th>
                <th style="width: 9%;" class="right">Pagado</th>
                <th style="width: 9%;" class="right">Saldo</th>
                <th style="width: 9%;" class="right">Mora</th>
                <th style="width: 9%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($installments as $installment)
                <tr>
                    <td>{{ $installment->receivable->codigo }}</td>
                    <td>{{ $installment->receivable->client->nombre }}</td>
                    <td class="center">{{ $installment->numero }}</td>
                    <td>{{ $installment->fecha_vencimiento?->format('d/m/Y') }}</td>
                    <td class="center late">{{ $installment->dias_atraso }} d</td>
                    <td class="right">${{ number_format((float) $installment->monto, 2) }}</td>
                    <td class="right">${{ number_format((float) $installment->monto_pagado, 2) }}</td>
                    <td class="right">${{ number_format($installment->saldo, 2) }}</td>
                    <td class="right">${{ number_format((float) $installment->mora_pendiente, 2) }}</td>
                    <td>{{ $installment->estado->label() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="center muted" style="padding: 16px;">No hay cuotas vencidas para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">Total en mora ({{ $totales['cantidad'] }} cuota(s))</td>
                <td class="right">${{ number_format($totales['monto'], 2) }}</td>
                <td class="right">${{ number_format($totales['mora'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>{{ config('app.name') }} · Reporte generado por {{ auth()->user()?->name ?? 'Sistema' }} · Montos en USD.</p>
    </div>
</body>
</html>
