<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'dui', 'nit', 'telefono', 'email', 'direccion', 'notas', 'activo'])]
class Client extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function getInicialesAttribute(): string
    {
        return collect(explode(' ', trim($this->nombre)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $query) use ($term) {
            $query->where('nombre', 'like', "%{$term}%")
                ->orWhere('dui', 'like', "%{$term}%")
                ->orWhere('nit', 'like', "%{$term}%");
        });
    }
}
