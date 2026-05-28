<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationDecisionMail;
use App\Mail\MeetingScheduledMail;
use App\Models\Lead;
use App\Models\Note;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return response()->json($query->paginate($params['per_page'] ?? 25));
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

        return response()->json(['lead' => $lead]);
    }

    /**
     * PATCH /api/admin/leads/{lead}
     */
    public function update(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'status'           => ['nullable', Rule::in(Lead::STATUSES)],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['string', 'max:40'],
        ]);

        $lead->fill($data)->save();

        return response()->json(['lead' => $lead->fresh()]);
    }

    /**
     * POST /api/admin/leads/{lead}/notes
     */
    public function addNote(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note = Note::create([
            'lead_id'   => $lead->id,
            'author_id' => $request->user()->id,
            'body'      => $data['body'],
        ]);

        return response()->json(['note' => $note->load('author:id,name,email')], 201);
    }

    /**
     * POST /api/admin/leads/{lead}/meetings
     * Schedules a Google Calendar event with an auto-generated Meet link and
     * sends the applicant a confirmation email.
     */
    public function addMeeting(Request $request, Lead $lead, GoogleCalendarService $calendar): JsonResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ]);

        $meeting = $calendar->createMeeting(
            lead: $lead,
            organizer: $request->user(),
            scheduledAt: new \DateTimeImmutable($data['scheduled_at']),
            note: $data['notes'] ?? null,
        );

        if ($lead->email) {
            Mail::to($lead->email)->send(new MeetingScheduledMail($meeting));
        }

        return response()->json(['meeting' => $meeting->load('scheduler:id,name,email')], 201);
    }

    /**
     * POST /api/admin/leads/{lead}/decision
     * Approves or declines an application lead and emails the applicant.
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

        $application->fill([
            'decision'      => $data['decision'],
            'decision_note' => $data['note'] ?? null,
            'decided_at'    => now(),
            'decided_by'    => $request->user()->id,
        ])->save();

        $lead->status = $data['decision'] === 'approved' ? 'qualified' : 'lost';
        $lead->save();

        if ($lead->email) {
            Mail::to($lead->email)->send(new ApplicationDecisionMail($lead, $data['decision'], $data['note'] ?? null));
        }

        return response()->json(['lead' => $lead->fresh()->load('application')]);
    }
}
