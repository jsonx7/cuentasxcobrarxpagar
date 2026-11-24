<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payable_id', 'fecha_pago', 'monto', 'concepto', 'metodo',
    'referencia', 'notas', 'user_id',
])]
class PayablePayment extends Model
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
            'metodo' => PaymentMethod::class,
        ];
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $query) => $query->whereDate('fecha_pago', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('fecha_pago', '<=', $to));
    }
}
