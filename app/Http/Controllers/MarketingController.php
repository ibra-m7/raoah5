<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (auth()->user()?->role === UserRole::Admin) {
            return redirect()->route('admin.dashboard');
        }

        return view('marketing.home', [
            'title' => AppStrings::APP_NAME,
        ]);
    }
}
