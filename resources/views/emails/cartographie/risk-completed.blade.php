<x-mail::message>
# Risque clôturé

Bonjour **{{ $recipient->name }}**,

**{{ $validator->name }}** a validé et clôturé la ligne de risque suivante.

**Sous-processus :** {{ $row->sub_process_name ?: '—' }}  
**Entité :** {{ $row->assignedEntity?->name ?: ($row->entity?->name ?: '—') }}

<x-mail::button :url="$riskUrl">
Voir la ligne
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
