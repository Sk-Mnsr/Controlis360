<?php

namespace App\Enums;

enum UserProfile: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Controle = 'controle';
    case Metier = 'metier';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrateur',
            self::Admin => 'Administrateur',
            self::Controle => 'Contrôle',
            self::Metier => 'Métier',
        };
    }

    public function workspace(): string
    {
        return match ($this) {
            self::SuperAdmin => 'super_admin',
            self::Admin => 'admin',
            self::Controle => 'controle',
            self::Metier => 'metier',
        };
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
