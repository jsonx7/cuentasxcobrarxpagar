<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'receivable_id', 'fecha_pago', 'monto', 'monto_mora', 'metodo',
    'referencia', 'notas', 'user_id',
])]
class Payment extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_pago' => 'date',
            'monto' => 'decimal:2',
            'monto_mora' => 'decimal:2',
            'metodo' => PaymentMethod::class,
        ];
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(PaymentMaterial::class);
    }

    public function installments(): BelongsToMany
    {
        return $this->belongsToMany(Installment::class, 'payment_allocations')
            ->withPivot('monto', 'tipo')
            ->wherePivot('tipo', 'cuota')
            ->withTimestamps();
    }

    public function getTotalAttribute(): float
    {
        return round((float) $this->monto + (float) $this->monto_mora, 2);
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $query) => $query->whereDate('fecha_pago', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('fecha_pago', '<=', $to));
    }
}
