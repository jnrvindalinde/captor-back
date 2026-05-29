<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Resources\LeadResource;
use App\Mail\ApplicationDecisionMail;
use App\Mail\MeetingScheduledMail;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Note;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    /**
     * GET /api/admin/leads
     * Supports filtering by kind, status, assignee, and a full-text search across name/email.
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'kind'     => ['nullable', Rule::in([Lead::KIND_CONTACT, Lead::KIND_ORG, Lead::KIND_APPLICATION])],
            'status'   => ['nullable', Rule::in(Lead::STATUSES)],
            'assignee' => ['nullable', 'integer', 'exists:users,id'],
            'q'        => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Lead::query()
            ->with(['assignedUser:id,name,email'])
            ->withCount(['notes', 'meetings'])
            ->latest();

        if (! empty($params['kind'])) {
            $query->where('kind', $params['kind']);
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['assignee'])) {
            $query->where('assigned_user_id', $params['assignee']);
        }
        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term);
            });
        }

        $paginator = $query->paginate($params['per_page'] ?? 25);

        // Tab counts ignore the active kind filter so the pills always show
        // the canonical partition. Status / assignee / search still apply
        // because they're "secondary" filters.
        $countsBase = Lead::query();
        if (! empty($params['assignee'])) {
            $countsBase->where('assigned_user_id', $params['assignee']);
        }
        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $countsBase->where(function ($q) use ($term) {
                $q->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term);
            });
        }

        $byKind = (clone $countsBase)
            ->where('status', '!=', 'lost')
            ->select('kind', DB::raw('count(*) as total'))
            ->groupBy('kind')
            ->pluck('total', 'kind');

        $counts = [
            'all'         => (int) (clone $countsBase)->where('status', '!=', 'lost')->count(),
            'application' => (int) ($byKind[Lead::KIND_APPLICATION] ?? 0),
            'org'         => (int) ($byKind[Lead::KIND_ORG] ?? 0),
            'contact'     => (int) ($byKind[Lead::KIND_CONTACT] ?? 0),
            'lost'        => (int) (clone $countsBase)->where('status', 'lost')->count(),
        ];

        return LeadResource::collection($paginator)
            ->additional(['counts' => $counts])
            ->response();
    }

    /**
     * GET /api/admin/leads/{lead}
     * Returns the lead with its kind-specific detail eager-loaded.
     */
    public function show(Lead $lead): JsonResponse
    {
        $lead->load([
            'assignedUser:id,name,email',
            'notes.author:id,name,email',
            'meetings.scheduler:id,name,email',
            'contactMessage',
            'orgInquiry',
            'application.files',
        ]);

        return response()->json(['lead' => new LeadResource($lead)]);
    }

    /**
     * PATCH /api/admin/leads/{lead}
     * Updates stage / assignee / tags. Emits a system note on status change.
     */
    public function update(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'status'           => ['nullable', Rule::in(Lead::STATUSES)],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['string', 'max:40'],
        ]);

        $previousStatus = $lead->status;

        DB::transaction(function () use ($lead, $data, $previousStatus, $request) {
            $lead->fill($data)->save();

            if (array_key_exists('status', $data) && $data['status'] !== null && $data['status'] !== $previousStatus) {
                Note::create([
                    'lead_id'   => $lead->id,
                    'author_id' => $request->user()->id,
                    'kind'      => Note::KIND_SYSTEM,
                    'body'      => sprintf(
                        'Status changed %s → %s via Stage panel.',
                        $previousStatus,
                        $data['status'],
                    ),
                ]);
            }
        });

        return response()->json(['lead' => new LeadResource($lead->fresh()->load([
            'assignedUser:id,name,email',
            'notes.author:id,name,email',
            'meetings.scheduler:id,name,email',
        ]))]);
    }

    /**
     * POST /api/admin/leads/{lead}/notes
     * Admin-authored ("manual") notes only — system notes are emitted by the
     * lifecycle mutations themselves.
     */
    public function addNote(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note = Note::create([
            'lead_id'   => $lead->id,
            'author_id' => $request->user()->id,
            'kind'      => Note::KIND_MANUAL,
            'body'      => $data['body'],
        ]);

        return response()->json(['note' => $note->load('author:id,name,email')], 201);
    }

    /**
     * PATCH /api/admin/leads/{lead}/notes/{note}
     * Manual notes only — author-or-admin gate.
     */
    public function updateNote(Request $request, Lead $lead, Note $note): JsonResponse
    {
        abort_unless($note->lead_id === $lead->id, 404);
        abort_if($note->kind === Note::KIND_SYSTEM, 422, 'System notes cannot be edited.');

        $user = $request->user();
        abort_unless(
            $note->author_id === $user->id || in_array($user->role, ['admin', 'super_admin'], true),
            403,
        );

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note->fill($data)->save();

        return response()->json(['note' => $note->load('author:id,name,email')]);
    }

    /**
     * POST /api/admin/leads/{lead}/meetings
     * Schedules a Google Calendar event with an auto-generated Meet link,
     * auto-advances the lead status to `scheduled`, emits a system note, and
     * sends the applicant a confirmation email.
     */
    public function addMeeting(Request $request, Lead $lead, GoogleCalendarService $calendar): JsonResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ]);

        $meeting = DB::transaction(function () use ($lead, $request, $data, $calendar) {
            $meeting = $calendar->createMeeting(
                lead: $lead,
                organizer: $request->user(),
                scheduledAt: new \DateTimeImmutable($data['scheduled_at']),
                note: $data['notes'] ?? null,
            );

            // Auto-advance: a meeting being scheduled implies the lead is past
            // the cold "new" state.
            if ($lead->status === 'new' || $lead->status === 'contacted') {
                $lead->update(['status' => 'scheduled', 'scheduled_at' => $meeting->scheduled_at]);
            } else {
                $lead->update(['scheduled_at' => $meeting->scheduled_at]);
            }

            Note::create([
                'lead_id'   => $lead->id,
                'author_id' => $request->user()->id,
                'kind'      => Note::KIND_SYSTEM,
                'body'      => sprintf(
                    'Meeting scheduled for %s · calendar invite sent to %s.',
                    $meeting->scheduled_at->format('M j, Y g:ia'),
                    $lead->email ?? 'applicant',
                ),
            ]);

            return $meeting;
        });

        if ($lead->email) {
            Mail::to($lead->email)->send(new MeetingScheduledMail($meeting));
        }

        return response()->json([
            'meeting' => $meeting->load('scheduler:id,name,email'),
            'lead'    => new LeadResource($lead->fresh()->load([
                'assignedUser:id,name,email',
                'notes.author:id,name,email',
                'meetings.scheduler:id,name,email',
            ])),
        ], 201);
    }

    /**
     * POST /api/admin/leads/{lead}/decision
     * Approves or declines an application lead, auto-advances the lead status,
     * emits a system note, and emails the applicant.
     *
     * State machine:
     *   approved  → status = contacted   (start of nurture pipeline)
     *   declined  → status = lost        (moves to the Archived tab)
     */
    public function decideApplication(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($lead->kind === Lead::KIND_APPLICATION, 422, 'Lead is not an application.');

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'declined'])],
            'note'     => ['nullable', 'string', 'max:2000'],
        ]);

        $application = $lead->application;
        abort_unless($application, 404, 'Application detail missing.');

        DB::transaction(function () use ($lead, $application, $data, $request) {
            $application->fill([
                'decision'      => $data['decision'],
                'decision_note' => $data['note'] ?? null,
                'decided_at'    => now(),
                'decided_by'    => $request->user()->id,
            ])->save();

            $lead->status = $data['decision'] === 'approved' ? 'contacted' : 'lost';
            $lead->save();

            Note::create([
                'lead_id'   => $lead->id,
                'author_id' => $request->user()->id,
                'kind'      => Note::KIND_SYSTEM,
                'body'      => $data['decision'] === 'approved'
                    ? sprintf(
                        'Application approved · approval email sent to %s. Status moved to Contacted.%s',
                        $lead->email ?? 'applicant',
                        ! empty($data['note']) ? ' Note: '.$data['note'] : '',
                    )
                    : sprintf(
                        'Application declined · decline email sent to %s. Lead archived to Lost.%s',
                        $lead->email ?? 'applicant',
                        ! empty($data['note']) ? ' Note: '.$data['note'] : '',
                    ),
            ]);
        });

        if ($lead->email) {
            Mail::to($lead->email)->send(new ApplicationDecisionMail($lead, $data['decision'], $data['note'] ?? null));
        }

        return response()->json(['lead' => new LeadResource($lead->fresh()->load([
            'application.files',
            'assignedUser:id,name,email',
            'notes.author:id,name,email',
            'meetings.scheduler:id,name,email',
        ]))]);
    }

    /**
     * POST /api/admin/leads/{lead}/convert
     * Promotes a won lead into a Client engagement. Creates a new Client row
     * with `source_lead_id` set, emits a system note on the lead. Idempotent:
     * returns the existing Client if conversion has already happened.
     */
    public function convertToClient(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($lead->status === 'won', 422, 'Only won leads can be converted.');

        $data = $request->validate([
            'program'       => ['required', Rule::in(Client::PROGRAMS)],
            'consultant_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $existing = Client::where('source_lead_id', $lead->id)->first();
        if ($existing) {
            return response()->json([
                'client' => new ClientResource($existing->load('consultant:id,name,email')),
                'lead'   => new LeadResource($lead->fresh()->load([
                    'assignedUser:id,name,email',
                    'notes.author:id,name,email',
                    'meetings.scheduler:id,name,email',
                ])),
                'already_converted' => true,
            ]);
        }

        $client = DB::transaction(function () use ($lead, $data, $request) {
            $client = Client::create([
                'name'           => $lead->name,
                'email'          => $lead->email,
                'phone'          => $lead->phone,
                'program'        => $data['program'],
                'consultant_id'  => $data['consultant_id'] ?? $lead->assigned_user_id,
                'status'         => Client::STATUS_ONBOARDING,
                'start_date'     => now(),
                'source_lead_id' => $lead->id,
            ]);

            Note::create([
                'lead_id'   => $lead->id,
                'author_id' => $request->user()->id,
                'kind'      => Note::KIND_SYSTEM,
                'body'      => sprintf(
                    'Lead converted to client (%s). Engagement started.',
                    $data['program'],
                ),
            ]);

            return $client;
        });

        return response()->json([
            'client' => new ClientResource($client->load('consultant:id,name,email')),
            'lead'   => new LeadResource($lead->fresh()->load([
                'assignedUser:id,name,email',
                'notes.author:id,name,email',
                'meetings.scheduler:id,name,email',
            ])),
        ], 201);
    }
}
