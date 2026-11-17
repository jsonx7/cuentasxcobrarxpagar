<?php

namespace App\Models;

use App\Enums\PayableStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'codigo', 'supplier_id', 'concepto', 'monto', 'fecha_emision',
    'fecha_vencimiento', 'estado', 'observaciones', 'created_by',
])]
class Payable extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'estado' => PayableStatus::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayablePayment::class);
    }

    public function isPagado(): bool
    {
        return $this->estado === PayableStatus::Pagado;
    }

    public function isCancelado(): bool
    {
        return $this->estado === PayableStatus::Cancelado;
    }

    public function isParcial(): bool
    {
        return $this->estado === PayableStatus::Parcial;
    }

    public function getTotalPagadoAttribute(): float
    {
        if (array_key_exists('payments_sum_monto', $this->attributes)) {
            return round((float) $this->attributes['payments_sum_monto'], 2);
        }

        if ($this->relationLoaded('payments')) {
            return round((float) $this->payments->sum('monto'), 2);
        }

        return round((float) $this->payments()->sum('monto'), 2);
    }

    public function getSaldoPendienteAttribute(): float
    {
        return max(round((float) $this->monto - $this->total_pagado, 2), 0);
    }

    public function getProgresoAttribute(): float
    {
        $total = (float) $this->monto;

        if ($total <= 0) {
            return 0;
        }

        return min(100, round(($this->total_pagado / $total) * 100, 1));
    }

    /**
     * Obligaciones aún abiertas: pendientes o con pago parcial.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            PayableStatus::Pendiente->value,
            PayableStatus::Parcial->value,
        ]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()->whereDate('fecha_vencimiento', '<', today());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $query) use ($term) {
            $query->where('codigo', 'like', "%{$term}%")
                ->orWhere('concepto', 'like', "%{$term}%")
                ->orWhereHas('supplier', function (Builder $query) use ($term) {
                    $query->where('nombre', 'like', "%{$term}%")
                        ->orWhere('nit', 'like', "%{$term}%");
                });
        });
    }
}
