<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Note;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MeetingController extends Controller
{
    public function __construct(private GoogleCalendarService $calendar) {}

    /**
     * GET /api/admin/meetings?from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->subWeek();
        $to   = isset($data['to'])   ? Carbon::parse($data['to'])->endOfDay()   : now()->addMonth();

        $meetings = Meeting::query()
            ->with([
                'scheduler:id,name,email',
                'lead:id,uuid,kind,name,email,status',
            ])
            ->where('status', '!=', 'canceled')
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'data' => $meetings,
            'meta' => [
                'from' => $from->toIso8601String(),
                'to'   => $to->toIso8601String(),
                'count'=> $meetings->count(),
            ],
        ]);
    }

    /**
     * GET /api/admin/meetings/slots?from=...&to=...&user_id=...
     * Returns the slots bookable for the given advisor (defaults to current user)
     * in the given window.
     */
    public function slots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from'    => ['nullable', 'date'],
            'to'      => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfDay();
        $to   = isset($data['to'])   ? Carbon::parse($data['to'])->endOfDay()   : now()->addDays(14)->endOfDay();

        $user = $request->user();
        if (! empty($data['user_id'])) {
            $user = \App\Models\User::findOrFail($data['user_id']);
        }

        $slots = $this->calendar->computeSlots($user, $from, $to);

        return response()->json([
            'data' => collect($slots)->map(fn ($s) => [
                'start' => $s['start']->toIso8601String(),
                'end'   => $s['end']->toIso8601String(),
            ])->values(),
            'meta' => [
                'advisor_id'        => $user->id,
                'advisor_name'      => $user->name,
                'google_connected'  => $user->hasGoogleConnected(),
                'from'              => $from->toIso8601String(),
                'to'                => $to->toIso8601String(),
            ],
        ]);
    }

    /**
     * PATCH /api/admin/meetings/{meeting}/reschedule
     */
    public function reschedule(Request $request, Meeting $meeting): JsonResponse
    {
        $data = $request->validate([
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $updated = DB::transaction(function () use ($meeting, $data, $request) {
            $oldTime = $meeting->scheduled_at;
            $fresh = $this->calendar->rescheduleMeeting(
                $meeting,
                new \DateTimeImmutable($data['scheduled_at']),
                $data['duration_minutes'] ?? null,
            );

            if ($fresh->lead) {
                Note::create([
                    'lead_id'   => $fresh->lead_id,
                    'author_id' => $request->user()->id,
                    'kind'      => Note::KIND_SYSTEM,
                    'body'      => sprintf(
                        'Meeting rescheduled %s → %s.',
                        $oldTime->format('M j, Y g:ia'),
                        $fresh->scheduled_at->format('M j, Y g:ia'),
                    ),
                ]);
            }

            return $fresh;
        });

        return response()->json(['meeting' => $updated->load('scheduler:id,name,email', 'lead:id,name,email')]);
    }

    /**
     * DELETE /api/admin/meetings/{meeting}
     */
    public function cancel(Request $request, Meeting $meeting): JsonResponse
    {
        DB::transaction(function () use ($meeting, $request) {
            $this->calendar->cancelMeeting($meeting);

            if ($meeting->lead) {
                Note::create([
                    'lead_id'   => $meeting->lead_id,
                    'author_id' => $request->user()->id,
                    'kind'      => Note::KIND_SYSTEM,
                    'body'      => sprintf('Meeting canceled (was %s).', $meeting->scheduled_at->format('M j, Y g:ia')),
                ]);
            }
        });

        return response()->json(['meeting' => $meeting->fresh()]);
    }
}
