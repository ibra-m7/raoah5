<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\AppStrings;
use App\Support\Constants;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('admin.reviews.index', [
            'title' => AppStrings::NAV_REVIEWS,
            'reviews' => Review::query()
                ->with(['user', 'product'])
                ->latest()
                ->paginate(Constants::DEFAULT_PAGE_SIZE),
        ]);
    }
}
