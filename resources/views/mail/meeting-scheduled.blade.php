@php /** @var \App\Models\Meeting $meeting */ @endphp
<p>Your Career 360 Consult session is confirmed.</p>
<p><strong>When:</strong> {{ $meeting->scheduled_at->format('l, j F Y · g:ia') }}</p>
@if ($meeting->google_meet_link)
    <p><strong>Where:</strong> <a href="{{ $meeting->google_meet_link }}">{{ $meeting->google_meet_link }}</a></p>
@endif
@if ($meeting->notes)
    <p>{{ $meeting->notes }}</p>
@endif
<p>— Career 360 Consult</p>
