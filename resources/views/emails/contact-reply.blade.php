<x-mail::message>
Bonjour {{ $message->name }},

{!! nl2br(e($body)) !!}

---

<small style="color:#888">Votre message initial{{ $message->subject ? ' (« '.$message->subject.' »)' : '' }} :</small>
<x-mail::panel>
{{ $message->message }}
</x-mail::panel>

Bien cordialement,<br>
L'équipe du Roi du Manioc
</x-mail::message>
