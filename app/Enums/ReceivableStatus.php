<?php

namespace App\Enums;

enum ReceivableStatus: string
{
    case Activo = 'activo';
    case Pagado = 'pagado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Pagado => 'Pagado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Activo => 'bg-sky-100 text-sky-700 ring-sky-600/20',
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
