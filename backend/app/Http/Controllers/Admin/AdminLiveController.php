<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLiveController extends Controller
{
    public function __construct(private readonly AdminEventService $events) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->events->snapshot((int) $request->query('after', 0)));
    }

    public function markRead(): JsonResponse
    {
        $this->events->markAllRead();

        return response()->json(['ok' => true, 'unread' => 0]);
    }
}
