<x-mail::message>
# Activité GovStrat à valider

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** a soumis une activité pour validation.

**Titre :** {{ $activity->title ?: '—' }}  
**Module :** {{ $moduleLabel }}  
**Section :** {{ $sectionLabel }}  
**Filiale :** {{ $activity->environment?->name ?: '—' }}  
**Owner :** {{ $activity->owner ?: '—' }}  
**Impact :** {{ $activity->impact ?: '—' }}

Connectez-vous à Controlis360 pour valider cette ligne.

<x-mail::button :url="$activityUrl">
Ouvrir GovStrat
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
