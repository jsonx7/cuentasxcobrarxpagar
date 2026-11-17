<?php

namespace App\Services;

use App\Models\Receivable;

class ReceivableCodeGenerator
{
    public function next(int $year): string
    {
        $prefix = 'CR-'.$year.'-';
        $ultimo = Receivable::query()
            ->where('codigo', 'like', $prefix.'%')
            ->orderByDesc('codigo')
            ->value('codigo');

        $secuencia = $ultimo ? (int) substr($ultimo, -4) + 1 : 1;

        do {
            $codigo = $prefix.str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
            $secuencia++;
        } while (Receivable::query()->where('codigo', $codigo)->exists());

        return $codigo;
    }
}
