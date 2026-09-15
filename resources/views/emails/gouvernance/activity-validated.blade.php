<x-mail::message>
# Activité GovStrat validée

Bonjour **{{ $recipient->name }}**,

**{{ $validator->name }}** a validé l'activité suivante. Vous pouvez désormais l'envoyer au Responsable Régional.

**Titre :** {{ $activity->title ?: '—' }}  
**Module :** {{ $moduleLabel }}  
**Section :** {{ $sectionLabel }}  
**Filiale :** {{ $activity->environment?->name ?: '—' }}  
**Validé le :** {{ optional($activity->validated_at)->format('d/m/Y H:i') ?: '—' }}

<x-mail::button :url="$activityUrl">
Ouvrir GovStrat
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
