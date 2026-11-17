<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case Pendiente = 'pendiente';
    case Parcial = 'parcial';
    case Pagada = 'pagada';
    case Vencida = 'vencida';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Parcial => 'Parcial',
            self::Pagada => 'Pagada',
            self::Vencida => 'Vencida',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            self::Parcial => 'bg-amber-100 text-amber-700 ring-amber-600/20',
            self::Pagada => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
            self::Vencida => 'bg-rose-100 text-rose-700 ring-rose-600/20',
        };
    }
}
