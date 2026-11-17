<?php

namespace App\Services;

use App\Models\Payable;

class PayableCodeGenerator
{
    public function next(int $year): string
    {
        $prefix = 'CP-'.$year.'-';
        $ultimo = Payable::query()
            ->where('codigo', 'like', $prefix.'%')
            ->orderByDesc('codigo')
            ->value('codigo');

        $secuencia = $ultimo ? (int) substr($ultimo, -4) + 1 : 1;

        do {
            $codigo = $prefix.str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
            $secuencia++;
        } while (Payable::query()->where('codigo', $codigo)->exists());

        return $codigo;
    }
}
