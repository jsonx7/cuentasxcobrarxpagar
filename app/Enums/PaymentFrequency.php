<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum PaymentFrequency: string
{
    case Unico = 'unico';
    case Semanal = 'semanal';
    case Bisemanal = 'bisemanal';
    case Quincenal = 'quincenal';
    case Mensual = 'mensual';
    case Personalizada = 'personalizada';

    public function label(): string
    {
        return match ($this) {
            self::Unico => 'Un solo pago',
            self::Semanal => 'Semanal',
            self::Bisemanal => 'Bisemanal',
            self::Quincenal => 'Quincenal',
            self::Mensual => 'Mensual',
            self::Personalizada => 'Personalizada',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Unico => 'Único',
            self::Semanal => 'Semanal',
            self::Bisemanal => 'Bisemanal',
            self::Quincenal => 'Quincenal',
            self::Mensual => 'Mensual',
            self::Personalizada => 'Personalizada',
        };
    }

    public function daysBetween(): ?int
    {
        return match ($this) {
            self::Semanal => 7,
            self::Bisemanal => 14,
            self::Quincenal => 15,
            default => null,
        };
    }

    public function isSinglePayment(): bool
    {
        return $this === self::Unico;
    }

    public function addTo(CarbonInterface $date): CarbonInterface
    {
        return match ($this) {
            self::Semanal => $date->copy()->addDays(7),
            self::Bisemanal => $date->copy()->addDays(14),
            self::Quincenal => $date->copy()->addDays(15),
            self::Mensual => $date->copy()->addMonthNoOverflow(),
            self::Unico, self::Personalizada => $date->copy(),
        };
    }

    /**
     * Opciones disponibles al crear un préstamo. La frecuencia personalizada
     * solo se usa al importar planes existentes.
     *
     * @return array<string, string>
     */
    public static function createOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $frequency) => $frequency === self::Personalizada)
            ->mapWithKeys(fn (self $frequency) => [$frequency->value => $frequency->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $frequency) => [$frequency->value => $frequency->label()])
            ->all();
    }
}
