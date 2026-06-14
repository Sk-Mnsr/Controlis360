<?php

namespace App\Enums;

enum MetierRole: string
{
    case ResponsableEntite = 'responsable_entite';
    case Groupe = 'groupe';
    case Visiteur = 'visiteur';

    public function label(): string
    {
        return match ($this) {
            self::ResponsableEntite => 'Responsable entité',
            self::Groupe => 'Groupe',
            self::Visiteur => 'Visiteur',
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
