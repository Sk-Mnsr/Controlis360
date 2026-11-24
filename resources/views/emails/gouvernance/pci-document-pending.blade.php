<x-mail::message>
@if ($action === 'deletion')
# Retrait à valider

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** demande le retrait d’un document de Doc Gouvernance. La suppression n’est effective qu’après votre validation.
@else
# Document à valider

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** a ajouté ou modifié un document de Doc Gouvernance. Il attend votre validation.
@endif

**Référence :** {{ $document->reference }}  
**Document :** {{ $document->document_name }}  
**Filiale :** {{ $document->environment?->name ?: '—' }}

<x-mail::button :url="$appUrl.'/gouvernance-it/suivi-pci'">
Ouvrir Doc Gouvernance
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
