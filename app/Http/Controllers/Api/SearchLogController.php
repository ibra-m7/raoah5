<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSearchLogRequest;
use App\Models\User;
use App\Services\Admin\SearchLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SearchLogController extends Controller
{
    public function __construct(private readonly SearchLogService $logs) {}

    public function store(StoreSearchLogRequest $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        $log = $this->logs->record(
            $request->validated(),
            $user instanceof User ? $user : null,
        );

        return response()->json([
            'ok' => true,
            'id' => $log?->id,
        ]);
    }
}
