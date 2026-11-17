<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'nit', 'contacto', 'telefono', 'email', 'direccion', 'notas', 'activo'])]
class Supplier extends Model
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

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
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
                ->orWhere('nit', 'like', "%{$term}%")
                ->orWhere('contacto', 'like', "%{$term}%");
        });
    }
}
