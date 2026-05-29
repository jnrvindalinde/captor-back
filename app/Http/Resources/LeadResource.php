<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Frozen JSON shape for an admin "Lead" entry.
 *
 * This is the contract the Next.js admin types in
 * `captor-front/src/app/admin/_mock.ts` are built against — keep them aligned.
 */
class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'kind'           => $this->kind,
            'status'         => $this->status,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'source'         => $this->source,
            'scheduled_at'   => optional($this->scheduled_at)->toIso8601String(),
            'tags'           => $this->tags ?? [],
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
            'notes_count'    => $this->whenCounted('notes'),
            'meetings_count' => $this->whenCounted('meetings'),
            'assigned_user'  => $this->whenLoaded('assignedUser', fn () => $this->assignedUser ? [
                'id'    => $this->assignedUser->id,
                'name'  => $this->assignedUser->name,
                'email' => $this->assignedUser->email,
            ] : null),
            'notes'          => $this->whenLoaded('notes', fn () => $this->notes->map(fn ($n) => [
                'id'         => $n->id,
                'body'       => $n->body,
                'kind'       => $n->kind,
                'created_at' => $n->created_at?->toIso8601String(),
                'author'     => $n->author ? [
                    'id'    => $n->author->id,
                    'name'  => $n->author->name,
                    'email' => $n->author->email,
                ] : null,
            ])),
            'meetings'       => $this->whenLoaded('meetings', fn () => $this->meetings->map(fn ($m) => [
                'id'               => $m->id,
                'scheduled_at'     => $m->scheduled_at?->toIso8601String(),
                'status'           => $m->status,
                'notes'            => $m->notes,
                'google_event_id'  => $m->google_event_id,
                'google_meet_link' => $m->google_meet_link,
                'scheduler'        => $m->scheduler ? [
                    'id'    => $m->scheduler->id,
                    'name'  => $m->scheduler->name,
                    'email' => $m->scheduler->email,
                ] : null,
            ])),
            'contact_message' => $this->whenLoaded('contactMessage', fn () => $this->contactMessage ? [
                'topic'   => $this->contactMessage->topic,
                'message' => $this->contactMessage->message,
            ] : null),
            'org_inquiry'    => $this->whenLoaded('orgInquiry', fn () => $this->orgInquiry ? [
                'about'         => $this->orgInquiry->about,
                'role'          => $this->orgInquiry->role,
                'organization'  => $this->orgInquiry->organization,
                'contact_kind'  => $this->orgInquiry->contact_kind,
                'contact_value' => $this->orgInquiry->contact_value,
            ] : null),
            'application'    => $this->whenLoaded('application', fn () => $this->application ? [
                'status_self'   => $this->application->status_self,
                'status_other'  => $this->application->status_other,
                'location'      => $this->application->location,
                'field'         => $this->application->field,
                'goal'          => $this->application->goal,
                'goal_other'    => $this->application->goal_other,
                'targets'       => $this->application->targets ?? [],
                'timeline'      => $this->application->timeline,
                'budget'        => $this->application->budget,
                'story'         => $this->application->story,
                'newsletter'    => (bool) $this->application->newsletter,
                'decision'      => $this->application->decision,
                'decision_note' => $this->application->decision_note,
                'decided_at'    => $this->application->decided_at?->toIso8601String(),
                'files'         => $this->application->relationLoaded('files')
                    ? $this->application->files->map(fn ($f) => [
                        'id'            => $f->id,
                        'original_name' => $f->original_name,
                        'mime'          => $f->mime,
                        'size'          => $f->size,
                        'path'          => $f->path,
                    ])
                    : [],
            ] : null),
        ];
    }
}
