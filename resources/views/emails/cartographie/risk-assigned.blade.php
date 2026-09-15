<x-mail::message>
# Risque affecté à votre entité

Bonjour **{{ $recipient->name }}**,

**{{ $validator->name }}** a validé et affecté une ligne de risque à votre entité. Merci de compléter la phase 2 (dispositifs de contrôle).

**Sous-processus :** {{ $row->sub_process_name ?: '—' }}  
**Entité affectée :** {{ $row->assignedEntity?->name ?: '—' }}  
**Échéance :** {{ optional($row->deadline)->format('d/m/Y') ?: '—' }}  
**Exceptions majeures :** {{ \Illuminate\Support\Str::limit($row->major_exceptions ?: '—', 200) }}

<x-mail::button :url="$riskUrl">
Compléter la ligne
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
