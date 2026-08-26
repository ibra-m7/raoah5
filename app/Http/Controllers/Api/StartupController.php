<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnboardingSlide;
use App\Models\SplashScreen;
use App\Support\ApiResponse;
use App\Support\Media;
use Illuminate\Http\JsonResponse;

class StartupController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $splash = SplashScreen::query()->active()->first();
        $slides = OnboardingSlide::query()
            ->active()
            ->get()
            ->map(fn (OnboardingSlide $slide) => [
                'id' => $slide->id,
                'title' => $slide->title,
                'subtitle' => $slide->subtitle,
                'description' => $slide->description,
                'image_url' => Media::url($slide->image_url) ?? '',
                'sort_order' => $slide->sort_order,
            ])
            ->values()
            ->all();

        return ApiResponse::success('إعدادات البداية', [
            'splash' => $splash?->toStartupPayload(),
            'onboarding' => $slides,
        ]);
    }
}
