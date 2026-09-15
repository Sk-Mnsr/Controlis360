<x-mail::message>
# Compte générique validé

Bonjour **{{ $recipient->name }}**,

**{{ $validator->name }}** a validé la ligne du registre de comptes génériques suivante.

**USER_ID :** {{ $account->user_id }}  
**USER_NAME :** {{ $account->user_name ?: '—' }}  
**Système / Application :** {{ $account->system_application ?: '—' }}  
**Filiale :** {{ $account->environment?->name ?: '—' }}  
**Validé le :** {{ optional($account->validated_at)->format('d/m/Y H:i') ?: '—' }}

<x-mail::button :url="$appUrl.'/gouvernance-it/registre-comptes-generiques'">
Voir le registre
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
