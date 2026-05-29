<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'program'        => $this->program,
            'status'         => $this->status,
            'start_date'     => optional($this->start_date)->toIso8601String(),
            'satisfaction'   => $this->satisfaction,
            'source_lead_id' => $this->source_lead_id,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),

            'consultant' => $this->whenLoaded('consultant', fn () => $this->consultant ? [
                'id'    => $this->consultant->id,
                'name'  => $this->consultant->name,
                'email' => $this->consultant->email,
            ] : null, default: null),

            'next_milestone' => $this->next_milestone_label ? [
                'label'  => $this->next_milestone_label,
                'due_at' => optional($this->next_milestone_due_at)->toIso8601String(),
            ] : null,
        ];
    }
}
