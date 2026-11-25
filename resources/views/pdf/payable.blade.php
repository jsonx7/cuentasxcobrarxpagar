<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta {{ $payable->codigo }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1e293b; margin: 0; }
        h1 { font-size: 19px; margin: 0; color: #0f172a; }
        h2 { font-size: 12px; margin: 0 0 6px; color: #0f172a; }
        p { margin: 0; }
        .muted { color: #64748b; }
        .small { font-size: 8px; }
        .header { border-bottom: 3px solid #0d9488; padding-bottom: 10px; margin-bottom: 12px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .brand { display: inline-block; background: #0d9488; color: #ffffff; padding: 6px 9px; border-radius: 5px; font-weight: bold; font-size: 12px; }
        .code { text-align: right; font-size: 13px; font-weight: bold; color: #0d9488; }
        .section { margin-bottom: 12px; }
        .box { border: 1px solid #e2e8f0; border-radius: 5px; padding: 8px 10px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 3px 5px; vertical-align: top; }
        .label { color: #64748b; font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; }
        .value { font-size: 9px; font-weight: bold; color: #0f172a; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary td { border: 1px solid #e2e8f0; padding: 6px 8px; width: 25%; background: #f8fafc; }
        .summary .title { font-size: 7.5px; text-transform: uppercase; color: #64748b; display: block; margin-bottom: 2px; }
        .summary .amount { font-size: 11px; font-weight: bold; color: #0f172a; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead th { background: #0f172a; color: #ffffff; font-size: 7.5px; text-transform: uppercase; padding: 5px 4px; text-align: left; }
        table.items tbody td { border-bottom: 1px solid #e2e8f0; padding: 4px; font-size: 8px; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; }
        .center { text-align: center; }
        table.items tfoot td { border-top: 2px solid #cbd5e1; padding: 5px 4px; font-weight: bold; font-size: 8.5px; background: #f1f5f9; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 7px; font-size: 7px; font-weight: bold; }
        .badge-pendiente { background: #e0f2fe; color: #0369a1; }
        .badge-parcial { background: #fef3c7; color: #b45309; }
        .badge-pagado { background: #d1fae5; color: #047857; }
        .badge-cancelado { background: #e2e8f0; color: #475569; }
        .badge-prima { background: #ede9fe; color: #6d28d9; }
        .footer { margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 8px; color: #94a3b8; font-size: 7.5px; }
    </style>
</head>
<body>
    @php
        $saldoPosterior = [];
        $acumulado = 0;
        foreach ($payable->payments->sortBy(['fecha_pago', 'id']) as $pagoHistorial) {
            $acumulado += (float) $pagoHistorial->monto;
            $saldoPosterior[$pagoHistorial->id] = max(0, round((float) $payable->monto - $acumulado, 2));
        }
    @endphp

    <div class="header">
        <table>
            <tr>
                <td>
                    <span class="brand">CP</span>
                    <h1 style="margin-top: 6px;">Estado de cuenta</h1>
                    <p class="muted">{{ config('app.name') }}</p>
                </td>
                <td class="code">
                    {{ $payable->codigo }}<br>
                    <span class="badge badge-{{ $payable->estado->value }}">{{ $payable->estado->label() }}</span><br>
                    <span class="small muted">Generado el {{ now()->format('d/m/Y H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section box">
        <table class="grid">
            <tr>
                <td width="24%">
                    <span class="label">Proveedor</span>
                    <span class="value">{{ $payable->supplier->nombre }}</span>
                </td>
                <td width="14%">
                    <span class="label">NIT</span>
                    <span class="value">{{ $payable->supplier->nit ?: '—' }}</span>
                </td>
                <td width="18%">
                    <span class="label">Concepto</span>
                    <span class="value">{{ $payable->concepto }}</span>
                </td>
                <td width="14%">
                    <span class="label">Emisión</span>
                    <span class="value">{{ $payable->fecha_emision?->format('d/m/Y') }}</span>
                </td>
                <td width="15%">
                    <span class="label">Vencimiento</span>
                    <span class="value">{{ $payable->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</span>
                </td>
                <td width="15%">
                    <span class="label">Primer pago</span>
                    <span class="value">{{ $payable->fecha_primer_pago?->format('d/m/Y') ?? '—' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="title">Total de la deuda</span>
                <span class="amount">${{ number_format((float) $payable->monto, 2) }}</span>
            </td>
            <td>
                <span class="title">Total pagado</span>
                <span class="amount">${{ number_format($payable->total_pagado, 2) }}</span>
            </td>
            <td>
                <span class="title">Saldo pendiente</span>
                <span class="amount">${{ number_format($payable->saldo_pendiente, 2) }}</span>
            </td>
            <td>
                <span class="title">Próxima fecha de pago</span>
                <span class="amount">{{ $payable->fecha_proxima_pago?->format('d/m/Y') ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="title">Abonos registrados</span>
                <span class="value">{{ $payable->abonos_registrados }}</span>
            </td>
            <td>
                <span class="title">Prima descontada</span>
                <span class="value">${{ number_format((float) ($payable->payments->firstWhere('es_prima', true)?->monto ?? 0), 2) }}</span>
            </td>
            <td>
                <span class="title">Progreso</span>
                <span class="value">{{ number_format($payable->progreso, 1) }}%</span>
            </td>
            <td>
                <span class="title">Registrada por</span>
                <span class="value">{{ $payable->creator?->name ?? 'Sistema' }}</span>
            </td>
        </tr>
    </table>

    <h2>Historial de pagos</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 10%;">Fecha</th>
                <th style="width: 20%;">Concepto</th>
                <th style="width: 12%;">Método</th>
                <th style="width: 14%;">Referencia</th>
                <th style="width: 12%;" class="right">Monto</th>
                <th style="width: 13%;" class="right">Saldo posterior</th>
                <th style="width: 14%;">Registrado por</th>
                <th style="width: 5%;">Tipo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payable->payments as $pago)
                <tr>
                    <td>{{ $pago->fecha_pago?->format('d/m/Y') }}</td>
                    <td>{{ $pago->concepto ?: '—' }}</td>
                    <td>{{ $pago->metodo->label() }}</td>
                    <td>{{ $pago->referencia ?: ($pago->notas ?: '—') }}</td>
                    <td class="right">${{ number_format((float) $pago->monto, 2) }}</td>
                    <td class="right">${{ number_format($saldoPosterior[$pago->id] ?? 0, 2) }}</td>
                    <td>{{ $pago->user?->name ?? '—' }}</td>
                    <td>@if ($pago->es_prima)<span class="badge badge-prima">Prima</span>@else Normal @endif</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center muted" style="padding: 14px;">No hay pagos registrados para esta obligación.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Totales ({{ $payable->payments->count() }} pago(s))</td>
                <td class="right">${{ number_format($payable->total_pagado, 2) }}</td>
                <td class="right">${{ number_format($payable->saldo_pendiente, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    @if ($payable->observaciones)
        <div class="section" style="margin-top: 12px;">
            <h2>Observaciones</h2>
            <div class="box">{{ $payable->observaciones }}</div>
        </div>
    @endif

    <div class="footer">
        <p>{{ config('app.name') }} · Estado generado por {{ auth()->user()?->name ?? 'Sistema' }} el {{ now()->format('d/m/Y H:i') }} · Montos en USD.</p>
        <p>La prima se registra como pago anticipado, descuenta el saldo pero no cuenta como abono ni mueve la próxima fecha. Las obligaciones por pagar no generan mora ni intereses.</p>
    </div>
</body>
</html>
