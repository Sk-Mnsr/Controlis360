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
    case Conformite = 'conformite';
    case AgentIt = 'agent_it';
    case ResponsableIt = 'responsable_it';
    case ResponsableRegional = 'responsable_regional';
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
            self::Conformite => 'Conformité',
            self::AgentIt => 'Agent IT',
            self::ResponsableIt => 'Responsable IT',
            self::ResponsableRegional => 'Responsable Régional',
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
            self::Conformite => 'conformite',
            self::AgentIt, self::ResponsableIt, self::ResponsableRegional => 'gouvernance_it',
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
