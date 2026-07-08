<x-mail::message>
# Réponse reçue sur la mission

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** a transmis une réponse de type **{{ $response->response_type_fr ?? $response->response_type }}** pour la mission **{{ $mission->reference }}**.

**Recommandation :** {{ $mission->recommendations->last()?->recommendation_label ?? $mission->recommendation?->recommendation_label ?? '—' }}

@if($response->response_type === 'passivite')
**Commentaire :** {{ $response->passivity_comment }}
@else
**Responsable :** {{ $response->responsible_name ?? '—' }}

**Taux d'avancement :** {{ $response->progress_rate !== null ? $response->progress_rate.' %' : '—' }}

**Go / No Go :** {{ $response->go_no_go_fr ?? '—' }}
@endif

Connectez-vous à Controlis360 pour consulter le détail.

<x-mail::button :url="$appUrl.'/suivi-reco/missions'">
Voir les missions
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
