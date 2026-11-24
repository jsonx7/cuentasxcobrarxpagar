<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payable_id', 'numero', 'fecha_vencimiento', 'pagada',
])]
class PayableInstallment extends Model
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
            'pagada' => 'boolean',
        ];
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function getVencidaAttribute(): bool
    {
        return ! $this->pagada && $this->fecha_vencimiento->lt(today());
    }
}
