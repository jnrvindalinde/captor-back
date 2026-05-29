<?php

namespace App\Services;

use App\Models\AvailabilityRule;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\FreeBusyRequest;
use Google\Service\Calendar\FreeBusyRequestItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    public function __construct(private GoogleOAuthService $oauth) {}

    public const DEFAULT_DURATION_MINUTES = 30;

    /** Create a meeting + Google Calendar event for a Lead. */
    public function createMeeting(
        Lead $lead,
        User $organizer,
        \DateTimeInterface $scheduledAt,
        ?string $note = null,
        ?int $durationMinutes = null,
        ?string $timezone = null,
    ): Meeting {
        $start = CarbonImmutable::instance($scheduledAt);
        $duration = $durationMinutes ?? self::DEFAULT_DURATION_MINUTES;
        $tz = $timezone ?? config('app.timezone') ?? 'UTC';

        $meeting = Meeting::create([
            'lead_id'          => $lead->id,
            'scheduled_by'     => $organizer->id,
            'scheduled_at'     => $start,
            'duration_minutes' => $duration,
            'timezone'         => $tz,
            'attendee_email'   => $lead->email,
            'attendee_name'    => $lead->name,
            'status'           => 'scheduled',
            'notes'            => $note,
        ]);

        try {
            $this->pushToGoogle($meeting, $organizer);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar create failed; falling back to placeholder', [
                'meeting_id' => $meeting->id,
                'error'      => $e->getMessage(),
            ]);
            $this->fillPlaceholderMeetLink($meeting);
        }

        if (in_array($lead->status, ['new', 'contacted'], true)) {
            $lead->status = 'scheduled';
        }
        $lead->scheduled_at = $start;
        $lead->save();

        return $meeting->fresh();
    }

    public function rescheduleMeeting(Meeting $meeting, \DateTimeInterface $newStart, ?int $durationMinutes = null): Meeting
    {
        $meeting->scheduled_at = CarbonImmutable::instance($newStart);
        if ($durationMinutes) {
            $meeting->duration_minutes = $durationMinutes;
        }
        $meeting->save();

        $organizer = $meeting->scheduler;
        if ($organizer && $organizer->hasGoogleConnected() && $meeting->google_event_id && ! str_starts_with($meeting->google_event_id, 'local_')) {
            try {
                $client = $this->oauth->clientForUser($organizer);
                $calendar = new GoogleCalendar($client);
                $calendarId = $organizer->google_calendar_id ?: 'primary';

                $event = $calendar->events->get($calendarId, $meeting->google_event_id);
                $event->setStart($this->makeEventDateTime($meeting->scheduled_at, $meeting->timezone));
                $event->setEnd($this->makeEventDateTime($this->endsAt($meeting), $meeting->timezone));

                $calendar->events->patch($calendarId, $meeting->google_event_id, $event, ['sendUpdates' => 'all']);
            } catch (\Throwable $e) {
                Log::warning('Google Calendar reschedule failed', ['meeting_id' => $meeting->id, 'error' => $e->getMessage()]);
            }
        }

        if ($meeting->lead) {
            $meeting->lead->scheduled_at = $meeting->scheduled_at;
            $meeting->lead->save();
        }

        return $meeting->fresh();
    }

    public function cancelMeeting(Meeting $meeting): Meeting
    {
        $organizer = $meeting->scheduler;
        if ($organizer && $organizer->hasGoogleConnected() && $meeting->google_event_id && ! str_starts_with($meeting->google_event_id, 'local_')) {
            try {
                $client = $this->oauth->clientForUser($organizer);
                $calendar = new GoogleCalendar($client);
                $calendarId = $organizer->google_calendar_id ?: 'primary';
                $calendar->events->delete($calendarId, $meeting->google_event_id, ['sendUpdates' => 'all']);
            } catch (\Throwable $e) {
                Log::warning('Google Calendar cancel failed', ['meeting_id' => $meeting->id, 'error' => $e->getMessage()]);
            }
        }

        $meeting->status = 'canceled';
        $meeting->save();

        return $meeting->fresh();
    }

    /**
     * @return array<int, array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    public function freeBusy(User $user, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd): array
    {
        if (! $user->hasGoogleConnected()) {
            return [];
        }

        try {
            $client = $this->oauth->clientForUser($user);
            $calendar = new GoogleCalendar($client);
            $calendarId = $user->google_calendar_id ?: 'primary';

            $item = new FreeBusyRequestItem();
            $item->setId($calendarId);

            $req = new FreeBusyRequest();
            $req->setTimeMin(CarbonImmutable::instance($rangeStart)->toRfc3339String());
            $req->setTimeMax(CarbonImmutable::instance($rangeEnd)->toRfc3339String());
            $req->setItems([$item]);

            $response = $calendar->freebusy->query($req);
            $busy = $response->getCalendars()[$calendarId]->getBusy() ?? [];

            $out = [];
            foreach ($busy as $b) {
                $out[] = [
                    'start' => CarbonImmutable::parse($b->getStart()),
                    'end'   => CarbonImmutable::parse($b->getEnd()),
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            Log::warning('Google FreeBusy query failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Compute bookable slots between $rangeStart and $rangeEnd.
     * Falls back to Mon-Fri 09:00-17:00 if no rules exist.
     *
     * @return array<int, array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    public function computeSlots(User $user, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd): array
    {
        $rules = $user->availabilityRules()->where('active', true)->get();

        if ($rules->isEmpty()) {
            $rules = collect(range(1, 5))->map(fn ($wd) => new AvailabilityRule([
                'weekday'        => $wd,
                'start_time'     => '09:00:00',
                'end_time'       => '17:00:00',
                'slot_minutes'   => 30,
                'buffer_minutes' => 10,
                'timezone'       => config('app.timezone') ?? 'UTC',
                'active'         => true,
            ]));
        }

        $start = CarbonImmutable::instance($rangeStart);
        $end   = CarbonImmutable::instance($rangeEnd);
        $minBookable = CarbonImmutable::now()->addHour();

        $slots = [];
        for ($day = $start->startOfDay(); $day->lessThan($end); $day = $day->addDay()) {
            $weekday = (int) $day->dayOfWeek;
            $dayRules = $rules->where('weekday', $weekday);
            foreach ($dayRules as $rule) {
                $tz = $rule->timezone ?: (config('app.timezone') ?? 'UTC');
                $dayInTz = $day->setTimezone($tz);
                $ruleStart = $dayInTz->setTimeFromTimeString($rule->start_time);
                $ruleEnd   = $dayInTz->setTimeFromTimeString($rule->end_time);

                $cursor = $ruleStart;
                while ($cursor->copy()->addMinutes($rule->slot_minutes)->lessThanOrEqualTo($ruleEnd)) {
                    $slotEnd = $cursor->copy()->addMinutes($rule->slot_minutes);
                    if ($slotEnd->greaterThan($minBookable) && $cursor->greaterThanOrEqualTo($start)) {
                        $slots[] = ['start' => $cursor, 'end' => $slotEnd];
                    }
                    $cursor = $slotEnd->addMinutes($rule->buffer_minutes);
                }
            }
        }

        // Subtract Google busy.
        $busy = $this->freeBusy($user, $start, $end);
        if (! empty($busy)) {
            $slots = array_values(array_filter($slots, function ($slot) use ($busy) {
                foreach ($busy as $b) {
                    if ($slot['start']->lessThan($b['end']) && $slot['end']->greaterThan($b['start'])) {
                        return false;
                    }
                }
                return true;
            }));
        }

        // Subtract local meetings (in case Google didn't fire / multiple advisors share).
        $localMeetings = Meeting::query()
            ->where('scheduled_by', $user->id)
            ->where('status', '!=', 'canceled')
            ->whereBetween('scheduled_at', [$start, $end])
            ->get(['scheduled_at', 'duration_minutes']);
        if ($localMeetings->isNotEmpty()) {
            $slots = array_values(array_filter($slots, function ($slot) use ($localMeetings) {
                foreach ($localMeetings as $m) {
                    $mStart = CarbonImmutable::instance($m->scheduled_at);
                    $mEnd   = $mStart->copy()->addMinutes($m->duration_minutes ?: 30);
                    if ($slot['start']->lessThan($mEnd) && $slot['end']->greaterThan($mStart)) {
                        return false;
                    }
                }
                return true;
            }));
        }

        return $slots;
    }

    // -----------------------------------------------------------------

    private function pushToGoogle(Meeting $meeting, User $organizer): void
    {
        if (! $organizer->hasGoogleConnected()) {
            $this->fillPlaceholderMeetLink($meeting);
            return;
        }

        $client = $this->oauth->clientForUser($organizer);
        $calendar = new GoogleCalendar($client);
        $calendarId = $organizer->google_calendar_id ?: 'primary';

        $event = new Event();
        $event->setSummary($this->summaryFor($meeting));
        $event->setDescription($meeting->notes ?: 'Booked via Captor.');
        $event->setStart($this->makeEventDateTime($meeting->scheduled_at, $meeting->timezone));
        $event->setEnd($this->makeEventDateTime($this->endsAt($meeting), $meeting->timezone));

        if ($meeting->attendee_email) {
            $attendee = new EventAttendee();
            $attendee->setEmail($meeting->attendee_email);
            if ($meeting->attendee_name) {
                $attendee->setDisplayName($meeting->attendee_name);
            }
            $event->setAttendees([$attendee]);
        }

        $solutionKey = new ConferenceSolutionKey();
        $solutionKey->setType('hangoutsMeet');
        $createRequest = new CreateConferenceRequest();
        $createRequest->setRequestId('captor-' . Str::uuid());
        $createRequest->setConferenceSolutionKey($solutionKey);
        $conferenceData = new ConferenceData();
        $conferenceData->setCreateRequest($createRequest);
        $event->setConferenceData($conferenceData);

        $created = $calendar->events->insert($calendarId, $event, [
            'conferenceDataVersion' => 1,
            'sendUpdates'           => 'all',
        ]);

        $meeting->google_event_id = $created->getId();
        $meeting->google_meet_link = $created->getHangoutLink() ?: $this->meetLinkFromConference($created);
        $meeting->save();
    }

    private function fillPlaceholderMeetLink(Meeting $meeting): void
    {
        if (! $meeting->google_event_id) {
            $meeting->google_event_id = 'local_' . Str::random(16);
        }
        if (! $meeting->google_meet_link) {
            $code = strtolower(Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3));
            $meeting->google_meet_link = "https://meet.google.com/{$code}";
        }
        $meeting->save();
    }

    private function summaryFor(Meeting $meeting): string
    {
        $name = $meeting->attendee_name ?: ($meeting->lead?->name ?: 'Captor lead');
        return "Captor · {$name}";
    }

    private function makeEventDateTime(\DateTimeInterface $when, ?string $tz): EventDateTime
    {
        $dt = new EventDateTime();
        $dt->setDateTime(CarbonImmutable::instance($when)->toRfc3339String());
        $dt->setTimeZone($tz ?: (config('app.timezone') ?? 'UTC'));
        return $dt;
    }

    private function endsAt(Meeting $meeting): CarbonImmutable
    {
        return CarbonImmutable::instance($meeting->scheduled_at)->addMinutes($meeting->duration_minutes ?: self::DEFAULT_DURATION_MINUTES);
    }

    private function meetLinkFromConference(Event $event): ?string
    {
        $conf = $event->getConferenceData();
        if (! $conf) {
            return null;
        }
        foreach ($conf->getEntryPoints() ?? [] as $ep) {
            if ($ep->getEntryPointType() === 'video') {
                return $ep->getUri();
            }
        }
        return null;
    }
}
