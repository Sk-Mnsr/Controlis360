<?php

namespace App\Enums;

enum ControleRole: string
{
    case AgentControleInterne = 'agent_controle_interne';
    case ResponsableControlePermanent = 'responsable_controle_permanent';

    public function label(): string
    {
        return match ($this) {
            self::AgentControleInterne => 'Agent du contrôle interne',
            self::ResponsableControlePermanent => 'Responsable Contrôle permanent & risques opérationnels',
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
