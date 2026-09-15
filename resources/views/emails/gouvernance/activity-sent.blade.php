<x-mail::message>
# Activité GovStrat envoyée

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** vous a envoyé une activité GovStrat.

**Titre :** {{ $activity->title ?: '—' }}  
**Module :** {{ $moduleLabel }}  
**Section :** {{ $sectionLabel }}  
**Filiale :** {{ $activity->environment?->name ?: '—' }}  
**Owner :** {{ $activity->owner ?: '—' }}  
**Priorité :** {{ $activity->priorite ?: '—' }}  
**Impact :** {{ $activity->impact ?: '—' }}

<x-mail::button :url="$activityUrl">
Consulter l'activité
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
