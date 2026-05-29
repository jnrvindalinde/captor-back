<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'status'     => ['nullable', Rule::in(Client::STATUSES)],
            'program'    => ['nullable', Rule::in(Client::PROGRAMS)],
            'consultant' => ['nullable', Rule::in(['assigned', 'unassigned'])],
            'q'          => ['nullable', 'string', 'max:120'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Client::query()->with('consultant:id,name,email')->latest('updated_at');

        if (! empty($params['status']))  $query->where('status', $params['status']);
        if (! empty($params['program'])) $query->where('program', $params['program']);
        if (($params['consultant'] ?? null) === 'assigned')   $query->whereNotNull('consultant_id');
        if (($params['consultant'] ?? null) === 'unassigned') $query->whereNull('consultant_id');
        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term));
        }

        $paginator = $query->paginate($params['per_page'] ?? 50);

        $counts = [
            'all'        => Client::count(),
            'active'     => Client::where('status', Client::STATUS_ACTIVE)->count(),
            'onboarding' => Client::where('status', Client::STATUS_ONBOARDING)->count(),
            'on_hold'    => Client::where('status', Client::STATUS_ON_HOLD)->count(),
            'completed'  => Client::where('status', Client::STATUS_COMPLETED)->count(),
            'churned'    => Client::where('status', Client::STATUS_CHURNED)->count(),
        ];

        return ClientResource::collection($paginator)
            ->additional(['counts' => $counts])
            ->response();
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json(['client' => new ClientResource($client->load('consultant:id,name,email'))]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $data = $request->validate([
            'name'                  => ['sometimes', 'string', 'max:200'],
            'email'                 => ['nullable', 'email', 'max:255'],
            'phone'                 => ['nullable', 'string', 'max:50'],
            'program'               => ['sometimes', Rule::in(Client::PROGRAMS)],
            'consultant_id'         => ['nullable', 'integer', 'exists:users,id'],
            'status'                => ['sometimes', Rule::in(Client::STATUSES)],
            'start_date'            => ['sometimes', 'date'],
            'next_milestone_label'  => ['nullable', 'string', 'max:200'],
            'next_milestone_due_at' => ['nullable', 'date'],
            'satisfaction'          => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $client->fill($data)->save();

        return response()->json(['client' => new ClientResource($client->fresh()->load('consultant:id,name,email'))]);
    }
}
