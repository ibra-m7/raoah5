<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OnboardingSlide;
use App\Support\AppStrings;
use App\Support\Constants;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function index(): View
    {
        return view('admin.onboarding.index', [
            'title' => AppStrings::NAV_ONBOARDING,
            'slides' => OnboardingSlide::query()
                ->orderBy('sort_order')
                ->paginate(Constants::DEFAULT_PAGE_SIZE),
        ]);
    }
}
