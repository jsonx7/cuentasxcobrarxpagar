<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Operador = 'operador';
    case Consulta = 'consulta';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Operador => 'Operador',
            self::Consulta => 'Consulta',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Acceso completo: registra empleados, préstamos y pagos, y administra usuarios.',
            self::Operador => 'Registra empleados, préstamos y pagos. No puede administrar usuarios.',
            self::Consulta => 'Solo puede consultar la información y descargar reportes.',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Roles que pueden crear y modificar registros operativos
     * (empleados, préstamos y pagos), pero no administrar usuarios.
     */
    public function canWrite(): bool
    {
        return $this === self::Admin || $this === self::Operador;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
