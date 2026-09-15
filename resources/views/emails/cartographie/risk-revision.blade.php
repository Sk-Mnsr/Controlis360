<x-mail::message>
# Révision demandée

Bonjour **{{ $recipient->name }}**,

**{{ $requester->name }}** a renvoyé votre ligne de risque pour correction.

**Sous-processus :** {{ $row->sub_process_name ?: '—' }}  
**Entité :** {{ $row->entity?->name ?: '—' }}

**Commentaire :**  
{{ $comment }}

<x-mail::button :url="$riskUrl">
Corriger la ligne
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
