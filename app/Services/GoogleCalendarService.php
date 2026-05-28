<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Google Calendar + Meet integration.
 *
 * This is currently a stub. When wiring the real Google API:
 *   - Use Google\Client + Google\Service\Calendar
 *   - Authenticate using the advisor's stored refresh token
 *     (User::$google_refresh_token; calendar id in User::$google_calendar_id)
 *   - Create the event with conferenceData to auto-generate a Meet link
 *   - Persist returned event id + hangoutLink on the Meeting row
 *
 * For now we just synthesize plausible identifiers so the UI can render and the
 * data shape is locked in.
 */
class GoogleCalendarService
{
    public function createMeeting(Lead $lead, User $organizer, \DateTimeInterface $scheduledAt, ?string $note = null): Meeting
    {
        // TODO: replace with a real Google Calendar API call.
        $eventId = 'gcal_' . Str::random(16);
        $meetCode = strtolower(Str::random(3) . '-' . Str::random(4) . '-' . Str::random(3));
        $meetLink = "https://meet.google.com/{$meetCode}";

        $meeting = Meeting::create([
            'lead_id' => $lead->id,
            'scheduled_by' => $organizer->id,
            'scheduled_at' => $scheduledAt,
            'google_event_id' => $eventId,
            'google_meet_link' => $meetLink,
            'status' => 'scheduled',
            'notes' => $note,
        ]);

        if ($lead->status === Lead::STATUS_NEW) {
            $lead->status = Lead::STATUS_SCHEDULED;
        }
        $lead->scheduled_at = $scheduledAt;
        $lead->save();

        return $meeting->fresh();
    }
}
