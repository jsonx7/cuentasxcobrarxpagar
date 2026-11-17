<?php

namespace App\Enums;

enum PayableStatus: string
{
    case Pendiente = 'pendiente';
    case Parcial = 'parcial';
    case Pagado = 'pagado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Parcial => 'Parcial',
            self::Pagado => 'Pagado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-sky-100 text-sky-700 ring-sky-600/20',
            self::Parcial => 'bg-amber-100 text-amber-700 ring-amber-600/20',
            self::Pagado => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
            self::Cancelado => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
