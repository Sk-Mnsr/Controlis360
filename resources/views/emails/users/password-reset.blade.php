<x-mail::message>
# Réinitialisation de votre mot de passe

Bonjour **{{ $recipient->name }}**,

@if($sender)
**{{ $sender->name }}** a réinitialisé votre mot de passe Controlis360.
@else
Votre mot de passe Controlis360 a été réinitialisé.
@endif

**Mot de passe temporaire :** {{ $plainPassword }}

À la prochaine connexion, vous devrez choisir un nouveau mot de passe avant d'accéder à l'application.

<x-mail::button :url="$loginUrl">
Se connecter
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
