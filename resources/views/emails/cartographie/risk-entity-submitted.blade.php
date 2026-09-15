<x-mail::message>
# Complétion entité reçue

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** a soumis la complétion (phase 2) d'une ligne de risque.

**Sous-processus :** {{ $row->sub_process_name ?: '—' }}  
**Entité affectée :** {{ $row->assignedEntity?->name ?: '—' }}  
**Efficacité du contrôle :** {{ $row->control_effectiveness ?? '—' }}

Vous pouvez valider la clôture ou demander une révision.

<x-mail::button :url="$riskUrl">
Examiner la ligne
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
