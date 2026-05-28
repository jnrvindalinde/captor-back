@php
    /** @var \App\Models\Lead $lead */
    /** @var string $decision */
    /** @var string|null $note */
@endphp
<p>Hi {{ $lead->name }},</p>

@if ($decision === 'approved')
    <p>Great news — your Career 360 Consult application has been approved. We'd love to set up your first advisory session.</p>
    <p>You'll receive a Google Calendar invitation with a Google Meet link shortly.</p>
@else
    <p>Thank you for applying to Career 360 Consult. After reviewing your submission, we won't be able to take you on as a client at this time.</p>
@endif

@if ($note)
    <hr>
    <p>{!! nl2br(e($note)) !!}</p>
@endif

<p>— Career 360 Consult</p>
