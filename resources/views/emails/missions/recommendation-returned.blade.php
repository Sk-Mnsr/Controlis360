<x-mail::message>
# Recommandation retournée

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** vous retourne une recommandation qui ne concerne pas son entité.

**Mission :** {{ $mission->reference }}

**Recommandation :** {{ $recommendation->reference }} — {{ $recommendation->recommendation_label }}

**Entité :** {{ $return->entity?->name ?? '—' }}

**Avis :**

{{ $return->avis }}

Réaffectez cette recommandation au bon département dans le module **Suivi des reco**.

<x-mail::button :url="$appUrl.'/suivi-reco/missions/'.$mission->id">
Ouvrir la mission
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
