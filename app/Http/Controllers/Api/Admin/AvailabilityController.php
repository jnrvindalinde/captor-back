<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Per-advisor weekly availability rules.
 *
 * GET  /api/admin/availability        list current user's rules
 * PUT  /api/admin/availability        replace ALL rules in one shot (idempotent)
 * DELETE /api/admin/availability      clear all rules
 */
class AvailabilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rules = $request->user()
            ->availabilityRules()
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data'    => $rules,
            'default' => [
                // What the public booking page will show if user has no rules.
                'description' => 'Mon–Fri 09:00–17:00, 30-minute slots',
                'timezone'    => config('app.timezone') ?? 'UTC',
            ],
        ]);
    }

    public function replace(Request $request): JsonResponse
    {
        $data = $request->validate([
            'timezone'                => ['required', 'string', 'max:64'],
            'rules'                   => ['present', 'array'],
            'rules.*.weekday'         => ['required', 'integer', 'between:0,6'],
            'rules.*.start_time'      => ['required', 'date_format:H:i'],
            'rules.*.end_time'        => ['required', 'date_format:H:i', 'after:rules.*.start_time'],
            'rules.*.slot_minutes'    => ['required', Rule::in([15, 20, 30, 45, 60, 90])],
            'rules.*.buffer_minutes'  => ['required', 'integer', 'min:0', 'max:60'],
            'rules.*.active'          => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $data) {
            $user->availabilityRules()->delete();
            foreach ($data['rules'] as $rule) {
                AvailabilityRule::create([
                    'user_id'        => $user->id,
                    'weekday'        => $rule['weekday'],
                    'start_time'     => $rule['start_time'] . ':00',
                    'end_time'       => $rule['end_time'] . ':00',
                    'slot_minutes'   => $rule['slot_minutes'],
                    'buffer_minutes' => $rule['buffer_minutes'],
                    'timezone'       => $data['timezone'],
                    'active'         => $rule['active'] ?? true,
                ]);
            }
        });

        return $this->index($request);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->user()->availabilityRules()->delete();
        return response()->json(['data' => [], 'cleared' => true]);
    }
}
