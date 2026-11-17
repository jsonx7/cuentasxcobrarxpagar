<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte consolidado</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1e293b; margin: 0; }
        h1 { font-size: 18px; margin: 0; color: #0f172a; }
        h2 { font-size: 12px; margin: 0 0 6px; color: #0f172a; }
        p { margin: 0; }
        .muted { color: #64748b; }
        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 10px; margin-bottom: 12px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .brand { display: inline-block; background: #4f46e5; color: #fff; padding: 6px 9px; border-radius: 5px; font-weight: bold; font-size: 12px; }
        .code { text-align: right; font-size: 11px; font-weight: bold; color: #4f46e5; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary td { border: 1px solid #e2e8f0; padding: 6px 9px; background: #f8fafc; }
        .summary .title { display: block; font-size: 7.5px; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .summary .amount { font-size: 11px; font-weight: bold; color: #0f172a; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items thead th { background: #0f172a; color: #fff; font-size: 7.5px; text-transform: uppercase; padding: 5px 4px; text-align: left; }
        table.items tbody td { border-bottom: 1px solid #e2e8f0; padding: 4px 4px; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #f8fafc; }
        table.items tfoot td { border-top: 2px solid #cbd5e1; padding: 5px 4px; font-weight: bold; background: #f1f5f9; font-size: 8.5px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 7px; font-size: 7px; font-weight: bold; }
        .badge-activo, .badge-pendiente { background: #e0f2fe; color: #0369a1; }
        .badge-pagado { background: #d1fae5; color: #047857; }
        .badge-parcial { background: #fef3c7; color: #b45309; }
        .badge-cancelado { background: #e2e8f0; color: #475569; }
        .footer { margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 8px; color: #94a3b8; font-size: 7.5px; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <span class="brand">CYP</span>
                    <h1 style="margin-top: 6px;">Reporte consolidado</h1>
                    <p class="muted">{{ config('app.name') }}</p>
                </td>
                <td class="code">
                    {{ $totalesCobrar['cantidad'] }} cobrar · {{ $totalesPagar['cantidad'] }} pagar<br>
                    <span class="muted" style="font-weight: normal;">Generado el {{ now()->format('d/m/Y H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="title">Total por cobrar</span>
                <span class="amount">${{ number_format($totalesCobrar['total'], 2) }}</span>
            </td>
            <td>
                <span class="title">Pagado (cobrar)</span>
                <span class="amount">${{ number_format($totalesCobrar['pagado'], 2) }}</span>
            </td>
            <td>
                <span class="title">Saldo por cobrar</span>
                <span class="amount">${{ number_format($totalesCobrar['saldo'], 2) }}</span>
            </td>
            <td>
                <span class="title">Saldo por pagar</span>
                <span class="amount">${{ number_format($totalesPagar['saldo'], 2) }}</span>
            </td>
        </tr>
    </table>

    <h2>Cuentas por cobrar</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 10%;">Código</th>
                <th style="width: 20%;">Cliente</th>
                <th style="width: 12%;">Frecuencia</th>
                <th style="width: 10%;" class="right">Monto</th>
                <th style="width: 11%;" class="right">Total</th>
                <th style="width: 11%;" class="right">Pagado</th>
                <th style="width: 11%;" class="right">Saldo</th>
                <th style="width: 15%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($receivables as $receivable)
                <tr>
                    <td>{{ $receivable->codigo }}</td>
                    <td>{{ $receivable->client->nombre }}</td>
                    <td>{{ $receivable->frecuencia->label() }}</td>
                    <td class="right">${{ number_format((float) $receivable->monto, 2) }}</td>
                    <td class="right">${{ number_format((float) $receivable->total_a_pagar, 2) }}</td>
                    <td class="right">${{ number_format($receivable->total_pagado, 2) }}</td>
                    <td class="right">${{ number_format($receivable->saldo_pendiente, 2) }}</td>
                    <td><span class="badge badge-{{ $receivable->estado->value }}">{{ $receivable->estado->label() }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center muted" style="padding: 14px;">No hay contratos para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Totales ({{ $totalesCobrar['cantidad'] }} contrato(s))</td>
                <td class="right">${{ number_format($totalesCobrar['monto'], 2) }}</td>
                <td class="right">${{ number_format($totalesCobrar['total'], 2) }}</td>
                <td class="right">${{ number_format($totalesCobrar['pagado'], 2) }}</td>
                <td class="right">${{ number_format($totalesCobrar['saldo'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <h2>Cuentas por pagar</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 10%;">Código</th>
                <th style="width: 18%;">Proveedor</th>
                <th style="width: 22%;">Concepto</th>
                <th style="width: 11%;">Vencimiento</th>
                <th style="width: 10%;" class="right">Monto</th>
                <th style="width: 10%;" class="right">Pagado</th>
                <th style="width: 10%;" class="right">Saldo</th>
                <th style="width: 9%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payables as $payable)
                <tr>
                    <td>{{ $payable->codigo }}</td>
                    <td>{{ $payable->supplier->nombre }}</td>
                    <td>{{ $payable->concepto }}</td>
                    <td>{{ $payable->fecha_vencimiento?->format('d/m/Y') }}</td>
                    <td class="right">${{ number_format((float) $payable->monto, 2) }}</td>
                    <td class="right">${{ number_format($payable->total_pagado, 2) }}</td>
                    <td class="right">${{ number_format($payable->saldo_pendiente, 2) }}</td>
                    <td><span class="badge badge-{{ $payable->estado->value }}">{{ $payable->estado->label() }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center muted" style="padding: 14px;">No hay obligaciones para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Totales ({{ $totalesPagar['cantidad'] }} obligación(es))</td>
                <td class="right">${{ number_format($totalesPagar['monto'], 2) }}</td>
                <td class="right">${{ number_format($totalesPagar['pagado'], 2) }}</td>
                <td class="right">${{ number_format($totalesPagar['saldo'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>{{ config('app.name') }} · Reporte generado por {{ auth()->user()?->name ?? 'Sistema' }} · Montos en USD.</p>
    </div>
</body>
</html>
