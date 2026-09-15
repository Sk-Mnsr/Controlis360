<x-mail::message>
# Complétion à revoir

Bonjour **{{ $recipient->name }}**,

**{{ $requester->name }}** a renvoyé la complétion de votre entité pour correction.

**Sous-processus :** {{ $row->sub_process_name ?: '—' }}  
**Entité affectée :** {{ $row->assignedEntity?->name ?: '—' }}

**Commentaire :**  
{{ $comment }}

<x-mail::button :url="$riskUrl">
Corriger la complétion
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
