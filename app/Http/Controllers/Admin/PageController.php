<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\AppStrings;
use App\Support\Constants;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'title' => AppStrings::NAV_PAGES,
            'pages' => Page::query()->latest()->paginate(Constants::DEFAULT_PAGE_SIZE),
        ]);
    }
}
