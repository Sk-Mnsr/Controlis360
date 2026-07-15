<x-mail::message>
# Nouvelle mission assignée

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** vous a adressé une nouvelle mission de suivi de recommandation.

**Référence :** {{ $mission->reference }}

@if($mission->recommendations->count() > 1)
**Recommandations ({{ $mission->recommendations->count() }}) :**
@foreach($mission->recommendations as $reco)
- {{ $reco->reference }} : {{ $reco->recommendation_label }}
@endforeach
@else
**Recommandation :** {{ $mission->recommendations->last()?->recommendation_label ?? $mission->recommendation?->recommendation_label ?? '—' }}
@endif

**Entité(s) concernée(s) :** {{ $mission->entities->pluck('name')->join(', ') ?: '—' }}

**Période :** {{ $mission->period }}

**Statut :** {{ $mission->status_fr ?? $mission->status }}

Connectez-vous à Controlis360 pour consulter le détail de cette mission dans le module **Suivi des reco**.

<x-mail::button :url="$appUrl.'/suivi-reco/missions'">
Voir mes missions
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
