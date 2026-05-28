<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Summary counters for the admin home screen.
     */
    public function index(): JsonResponse
    {
        $byStatus = Lead::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byKind = Lead::query()
            ->select('kind', DB::raw('count(*) as total'))
            ->groupBy('kind')
            ->pluck('total', 'kind');

        $recent = Lead::query()
            ->with(['assignedUser:id,name,email'])
            ->latest()
            ->limit(10)
            ->get();

        $upcomingMeetings = Lead::query()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get(['id', 'kind', 'name', 'email', 'scheduled_at']);

        return response()->json([
            'totals' => [
                'leads'         => Lead::count(),
                'new'           => (int) ($byStatus['new'] ?? 0),
                'scheduled'     => (int) ($byStatus['scheduled'] ?? 0),
                'won'           => (int) ($byStatus['won'] ?? 0),
                'lost'          => (int) ($byStatus['lost'] ?? 0),
                'applications'  => (int) ($byKind[Lead::KIND_APPLICATION] ?? 0),
                'org_inquiries' => (int) ($byKind[Lead::KIND_ORG] ?? 0),
                'contacts'      => (int) ($byKind[Lead::KIND_CONTACT] ?? 0),
            ],
            'recent'            => $recent,
            'upcoming_meetings' => $upcomingMeetings,
        ]);
    }
}
