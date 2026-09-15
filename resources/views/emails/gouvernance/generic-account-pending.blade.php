<x-mail::message>
# Compte générique à valider

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** a soumis une ligne du registre de comptes génériques pour validation.

**USER_ID :** {{ $account->user_id }}  
**USER_NAME :** {{ $account->user_name ?: '—' }}  
**Système / Application :** {{ $account->system_application ?: '—' }}  
**Filiale :** {{ $account->environment?->name ?: '—' }}  
**Risque :** {{ $account->risk ?: '—' }}

Connectez-vous à Controlis360 pour valider ou demander une correction.

<x-mail::button :url="$appUrl.'/gouvernance-it/registre-comptes-generiques'">
Ouvrir le registre
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
