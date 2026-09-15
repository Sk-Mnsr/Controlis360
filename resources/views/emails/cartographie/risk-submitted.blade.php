<x-mail::message>
# Risque opérationnel à valider

Bonjour **{{ $recipient->name }}**,

**{{ $sender->name }}** a soumis une ligne de cartographie des risques pour validation.

**Sous-processus :** {{ $row->sub_process_name ?: '—' }}  
**Processus :** {{ $row->process_name ?: '—' }}  
**Entité :** {{ $row->entity?->name ?: '—' }}  
**Exceptions majeures :** {{ \Illuminate\Support\Str::limit($row->major_exceptions ?: '—', 200) }}

<x-mail::button :url="$riskUrl">
Ouvrir la cartographie
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
