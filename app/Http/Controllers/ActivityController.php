<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * GET /api/user/activity
     * Returns the current user's 20 most recent activities.
     */
    public function index(Request $request): JsonResponse
    {
        $activities = ActivityLog::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $activities->map(fn ($a) => [
                'id'          => $a->id,
                'type'        => $a->type,
                'description' => $a->description,
                'link'        => $a->link,
                'occurred_at' => $a->occurred_at?->toISOString(),
            ]),
        ]);
    }
}
