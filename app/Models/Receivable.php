<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Enums\PaymentFrequency;
use App\Enums\ReceivableStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'codigo', 'client_id', 'monto', 'tasa_interes_anual', 'plazo', 'frecuencia',
    'fecha_desembolso', 'fecha_primer_pago', 'cuota', 'interes_total', 'total_a_pagar',
    'tasa_mora_mensual', 'estado', 'observaciones', 'created_by',
])]
class Receivable extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'tasa_interes_anual' => 'decimal:2',
            'plazo' => 'integer',
            'frecuencia' => PaymentFrequency::class,
            'fecha_desembolso' => 'date',
            'fecha_primer_pago' => 'date',
            'cuota' => 'decimal:2',
            'interes_total' => 'decimal:2',
            'total_a_pagar' => 'decimal:2',
            'tasa_mora_mensual' => 'decimal:2',
            'estado' => ReceivableStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isActivo(): bool
    {
        return $this->estado === ReceivableStatus::Activo;
    }

    public function isPagado(): bool
    {
        return $this->estado === ReceivableStatus::Pagado;
    }

    public function isCancelado(): bool
    {
        return $this->estado === ReceivableStatus::Cancelado;
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

    public function getTotalMoraPagadoAttribute(): float
    {
        if (array_key_exists('payments_sum_monto_mora', $this->attributes)) {
            return round((float) $this->attributes['payments_sum_monto_mora'], 2);
        }

        if ($this->relationLoaded('payments')) {
            return round((float) $this->payments->sum('monto_mora'), 2);
        }

        return round((float) $this->payments()->sum('monto_mora'), 2);
    }

    public function getSaldoPendienteAttribute(): float
    {
        return max(round((float) $this->total_a_pagar - $this->total_pagado, 2), 0);
    }

    public function getProgresoAttribute(): float
    {
        $total = (float) $this->total_a_pagar;

        if ($total <= 0) {
            return 0;
        }

        return min(100, round(($this->total_pagado / $total) * 100, 1));
    }

    public function getCuotasPagadasAttribute(): int
    {
        if ($this->relationLoaded('installments')) {
            return $this->installments->filter(fn (Installment $installment) => $installment->esta_pagada)->count();
        }

        return $this->installments()->whereColumn('monto_pagado', '>=', 'monto')->count();
    }

    public function getCuotasVencidasAttribute(): int
    {
        if ($this->relationLoaded('installments')) {
            return $this->installments
                ->filter(fn (Installment $installment) => $installment->estado === InstallmentStatus::Vencida)
                ->count();
        }

        return $this->installments()->overdue()->count();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('estado', ReceivableStatus::Activo->value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $query) use ($term) {
            $query->where('codigo', 'like', "%{$term}%")
                ->orWhereHas('client', function (Builder $query) use ($term) {
                    $query->where('nombre', 'like', "%{$term}%")
                        ->orWhere('dui', 'like', "%{$term}%")
                        ->orWhere('nit', 'like', "%{$term}%");
                });
        });
    }
}
