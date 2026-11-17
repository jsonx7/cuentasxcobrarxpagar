<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Plan de pagos {{ $receivable->codigo }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; margin: 0; }
        h1 { font-size: 20px; margin: 0; color: #0f172a; }
        h2 { font-size: 13px; margin: 0 0 6px; color: #0f172a; }
        p { margin: 0; }
        .muted { color: #64748b; }
        .small { font-size: 8.5px; }
        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 12px; margin-bottom: 16px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .brand { display: inline-block; background: #4f46e5; color: #ffffff; padding: 7px 10px; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .code { text-align: right; font-size: 14px; font-weight: bold; color: #4f46e5; }
        .section { margin-bottom: 14px; }
        .box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 4px 6px; vertical-align: top; }
        .label { color: #64748b; font-size: 8.5px; text-transform: uppercase; letter-spacing: .4px; }
        .value { font-size: 10px; font-weight: bold; color: #0f172a; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td { border: 1px solid #e2e8f0; padding: 8px 10px; width: 25%; background: #f8fafc; }
        .summary .title { font-size: 8.5px; text-transform: uppercase; color: #64748b; display: block; margin-bottom: 2px; }
        .summary .amount { font-size: 12px; font-weight: bold; color: #0f172a; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead th { background: #0f172a; color: #ffffff; font-size: 8px; text-transform: uppercase; padding: 6px 5px; text-align: left; }
        table.items tbody td { border-bottom: 1px solid #e2e8f0; padding: 5px; font-size: 8.5px; }
        table.items tbody tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; }
        .center { text-align: center; }
        table.items tfoot td { border-top: 2px solid #cbd5e1; padding: 6px 5px; font-weight: bold; font-size: 9px; background: #f1f5f9; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 7.5px; font-weight: bold; }
        .badge-activo { background: #e0f2fe; color: #0369a1; }
        .badge-pagado { background: #d1fae5; color: #047857; }
        .badge-cancelado { background: #e2e8f0; color: #475569; }
        .badge-pendiente { background: #f1f5f9; color: #475569; }
        .badge-parcial { background: #fef3c7; color: #b45309; }
        .badge-vencida { background: #ffe4e6; color: #be123c; }
        .footer { margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 10px; color: #94a3b8; font-size: 8px; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 36px; }
        .signatures td { width: 50%; text-align: center; padding: 0 20px; }
        .signature-line { border-top: 1px solid #94a3b8; padding-top: 4px; font-size: 8.5px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <span class="brand">CR</span>
                    <h1 style="margin-top: 8px;">Plan de pagos</h1>
                    <p class="muted">{{ config('app.name') }}</p>
                </td>
                <td class="code">
                    {{ $receivable->codigo }}<br>
                    <span class="badge badge-{{ $receivable->estado->value }}">{{ $receivable->estado->label() }}</span><br>
                    <span class="small muted">Emitido el {{ now()->format('d/m/Y H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section box">
        <table class="grid">
            <tr>
                <td width="40%">
                    <span class="label">Cliente</span>
                    <span class="value">{{ $receivable->client->nombre }}</span>
                </td>
                <td width="20%">
                    <span class="label">DUI</span>
                    <span class="value">{{ $receivable->client->dui ?: '—' }}</span>
                </td>
                <td width="20%">
                    <span class="label">NIT</span>
                    <span class="value">{{ $receivable->client->nit ?: '—' }}</span>
                </td>
                <td width="20%">
                    <span class="label">Teléfono</span>
                    <span class="value">{{ $receivable->client->telefono ?: '—' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="title">Monto del contrato</span>
                <span class="amount">${{ number_format((float) $receivable->monto, 2) }}</span>
            </td>
            <td>
                <span class="title">Cuota</span>
                <span class="amount">${{ number_format((float) $receivable->cuota, 2) }}</span>
            </td>
            <td>
                <span class="title">Total a pagar</span>
                <span class="amount">${{ number_format((float) $receivable->total_a_pagar, 2) }}</span>
            </td>
            <td>
                <span class="title">Saldo pendiente</span>
                <span class="amount">${{ number_format($receivable->saldo_pendiente, 2) }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="title">Fecha del contrato</span>
                <span class="value">{{ $receivable->fecha_desembolso?->format('d/m/Y') }}</span>
            </td>
            <td>
                <span class="title">Primer pago</span>
                <span class="value">{{ $receivable->fecha_primer_pago?->format('d/m/Y') }}</span>
            </td>
            <td>
                <span class="title">Plazo / frecuencia</span>
                <span class="value">{{ $receivable->plazo }} cuota(s) · {{ $receivable->frecuencia->shortLabel() }}</span>
            </td>
            <td>
                <span class="title">Tasa anual</span>
                <span class="value">{{ number_format((float) $receivable->tasa_interes_anual, 2) }}%</span>
            </td>
        </tr>
    </table>

    <h2>Detalle de cuotas</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 16%;">Vencimiento</th>
                <th style="width: 12%;" class="right">Cuota</th>
                <th style="width: 12%;" class="right">Interés</th>
                <th style="width: 12%;" class="right">Capital</th>
                <th style="width: 12%;" class="right">Pagado</th>
                <th style="width: 13%;" class="right">Saldo</th>
                <th style="width: 18%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receivable->installments->sortBy('numero') as $installment)
                <tr>
                    <td>{{ $installment->numero }}</td>
                    <td>{{ $installment->fecha_vencimiento?->format('d/m/Y') }}</td>
                    <td class="right">${{ number_format((float) $installment->monto, 2) }}</td>
                    <td class="right">${{ number_format((float) $installment->interes, 2) }}</td>
                    <td class="right">${{ number_format((float) $installment->capital, 2) }}</td>
                    <td class="right">${{ number_format((float) $installment->monto_pagado, 2) }}</td>
                    <td class="right">${{ number_format($installment->saldo, 2) }}</td>
                    <td><span class="badge badge-{{ $installment->estado->value }}">{{ $installment->estado->label() }}</span></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Totales</td>
                <td class="right">${{ number_format((float) $receivable->total_a_pagar, 2) }}</td>
                <td class="right">${{ number_format((float) $receivable->interes_total, 2) }}</td>
                <td></td>
                <td class="right">${{ number_format($receivable->total_pagado, 2) }}</td>
                <td class="right">${{ number_format($receivable->saldo_pendiente, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if ($receivable->observaciones)
        <div class="section" style="margin-top: 14px;">
            <h2>Observaciones</h2>
            <div class="box">{{ $receivable->observaciones }}</div>
        </div>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div class="signature-line">Firma del cliente</div>
            </td>
            <td>
                <div class="signature-line">Firma de la empresa</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>{{ config('app.name') }} · Plan generado por {{ $receivable->creator?->name ?? 'Sistema' }} el {{ now()->format('d/m/Y H:i') }}.</p>
        <p>Este documento es un comprobante informativo del plan de pagos. La mora se calcula y se paga aparte; no se capitaliza. Montos en USD.</p>
    </div>
</body>
</html>
