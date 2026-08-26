<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SplashScreenRequest;
use App\Models\SplashScreen;
use App\Services\Admin\SplashScreenService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SplashScreenController extends Controller
{
    public function __construct(private readonly SplashScreenService $splashes) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status']);

        return view('admin.splash-screens.index', [
            'title' => AppStrings::NAV_SPLASH,
            'splashes' => $this->splashes->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.splash-screens.create', [
            'title' => AppStrings::ADD_SPLASH,
            'splash' => new SplashScreen([
                'is_active' => false,
                'sort_order' => 0,
                'media_type' => 'image',
                'duration_ms' => 2500,
            ]),
        ]);
    }

    public function store(SplashScreenRequest $request): RedirectResponse
    {
        $this->splashes->create($request->validated(), $request->file('media_file'));

        return redirect()
            ->route('admin.splash-screens.index')
            ->with('success', AppStrings::SPLASH_CREATED);
    }

    public function edit(SplashScreen $splash_screen): View
    {
        return view('admin.splash-screens.edit', [
            'title' => AppStrings::EDIT_SPLASH,
            'splash' => $splash_screen,
        ]);
    }

    public function update(SplashScreenRequest $request, SplashScreen $splash_screen): RedirectResponse
    {
        $this->splashes->update($splash_screen, $request->validated(), $request->file('media_file'));

        return redirect()
            ->route('admin.splash-screens.index')
            ->with('success', AppStrings::SPLASH_UPDATED);
    }

    public function destroy(SplashScreen $splash_screen): RedirectResponse
    {
        $this->splashes->delete($splash_screen);

        return redirect()
            ->route('admin.splash-screens.index')
            ->with('success', AppStrings::SPLASH_DELETED);
    }
}
