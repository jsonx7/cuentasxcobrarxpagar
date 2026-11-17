<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'receivable_id', 'numero', 'fecha_vencimiento', 'monto', 'interes', 'capital',
    'saldo_pendiente', 'abono_extra', 'monto_pagado', 'mora_pagada',
])]
class Installment extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'fecha_vencimiento' => 'date',
            'monto' => 'decimal:2',
            'interes' => 'decimal:2',
            'capital' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
            'abono_extra' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
            'mora_pagada' => 'decimal:2',
        ];
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getEstadoAttribute(): InstallmentStatus
    {
        if ((float) $this->monto_pagado + 0.005 >= (float) $this->monto) {
            return InstallmentStatus::Pagada;
        }

        if ((float) $this->monto_pagado > 0) {
            return InstallmentStatus::Parcial;
        }

        if ($this->fecha_vencimiento->lt(today())) {
            return InstallmentStatus::Vencida;
        }

        return InstallmentStatus::Pendiente;
    }

    public function getEstaPagadaAttribute(): bool
    {
        return $this->estado === InstallmentStatus::Pagada;
    }

    public function getSaldoAttribute(): float
    {
        return max(round((float) $this->monto - (float) $this->monto_pagado, 2), 0);
    }

    public function getDiasAtrasoAttribute(): int
    {
        if ($this->esta_pagada || ! $this->fecha_vencimiento->lt(today())) {
            return 0;
        }

        return (int) $this->fecha_vencimiento->diffInDays(today());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereColumn('monto_pagado', '<', 'monto');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->pending()->whereDate('fecha_vencimiento', '<', today());
    }
}
