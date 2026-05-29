<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MeetingController extends Controller
{
    /**
     * GET /api/admin/meetings?from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * Returns meetings in the given window (default: from = now-7d, to = now+30d)
     * eager-loaded with the lead summary needed by the dashboard calendar widget.
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
}
