<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OnboardingSlideRequest;
use App\Models\OnboardingSlide;
use App\Services\Admin\OnboardingSlideService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private readonly OnboardingSlideService $slides) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status']);

        return view('admin.onboarding.index', [
            'title' => AppStrings::NAV_ONBOARDING,
            'slides' => $this->slides->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.onboarding.create', [
            'title' => AppStrings::ADD_ONBOARDING,
            'slide' => new OnboardingSlide([
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function store(OnboardingSlideRequest $request): RedirectResponse
    {
        $this->slides->create($request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.onboarding.index')
            ->with('success', AppStrings::ONBOARDING_CREATED);
    }

    public function edit(OnboardingSlide $onboarding): View
    {
        return view('admin.onboarding.edit', [
            'title' => AppStrings::EDIT_ONBOARDING,
            'slide' => $onboarding,
        ]);
    }

    public function update(OnboardingSlideRequest $request, OnboardingSlide $onboarding): RedirectResponse
    {
        $this->slides->update($onboarding, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.onboarding.index')
            ->with('success', AppStrings::ONBOARDING_UPDATED);
    }

    public function destroy(OnboardingSlide $onboarding): RedirectResponse
    {
        $this->slides->delete($onboarding);

        return redirect()
            ->route('admin.onboarding.index')
            ->with('success', AppStrings::ONBOARDING_DELETED);
    }
}
