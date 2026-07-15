<?php

namespace App\Enums;

enum UserProfile: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Superviseur = 'superviseur';
    case Regulateur = 'regulateur';
    case Controle = 'controle';
    case Audit = 'audit';
<<<<<<< HEAD
    case Conformite = 'conformite';
=======
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
    case Metier = 'metier';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrateur',
            self::Admin => 'Administrateur',
            self::Superviseur => 'Superviseur',
            self::Regulateur => 'Régulateur',
            self::Controle => 'Contrôle',
            self::Audit => 'Audit',
<<<<<<< HEAD
            self::Conformite => 'Conformité',
=======
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
            self::Metier => 'Métier',
        };
    }

    public function workspace(): string
    {
        return match ($this) {
            self::SuperAdmin => 'super_admin',
            self::Admin => 'admin',
            self::Superviseur => 'superviseur',
            self::Regulateur => 'regulateur',
            self::Controle => 'controle',
            self::Audit => 'audit',
<<<<<<< HEAD
            self::Conformite => 'conformite',
=======
>>>>>>> bcf451b4361af2c5fd10eee26bde208691bd95ec
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
