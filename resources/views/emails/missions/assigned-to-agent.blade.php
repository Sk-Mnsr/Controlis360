<x-mail::message>
# Mission à compléter

Bonjour **{{ $agent->name }}**,

**{{ $responsable->name }}** vous a affecté la mission **{{ $mission->reference }}** à traiter.

**Recommandation :** {{ $mission->recommendations->last()?->recommendation_label ?? $mission->recommendation?->recommendation_label ?? '—' }}

**Entité(s) :** {{ $mission->entities->pluck('name')->join(', ') ?: '—' }}

Connectez-vous à Controlis360 pour remplir le formulaire d'action.

<x-mail::button :url="$appUrl.'/suivi-reco/missions'">
Ouvrir la mission
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
