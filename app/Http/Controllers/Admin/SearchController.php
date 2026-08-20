<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Enums\UserRole;
use App\Support\AppStrings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $products = collect();
        $categories = collect();
        $orders = collect();
        $customers = collect();
        $banners = collect();
        $homeSections = collect();

        if ($q !== '') {
            $products = Product::query()->with('category')->search($q)->limit(8)->get();
            $categories = Category::query()->with('parent')->where('name', 'like', '%'.$q.'%')->limit(8)->get();
            $banners = Banner::query()
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', '%'.$q.'%')
                        ->orWhere('subtitle', 'like', '%'.$q.'%');
                })
                ->limit(6)
                ->get();
            $homeSections = HomeSection::query()
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', '%'.$q.'%')
                        ->orWhere('key', 'like', '%'.$q.'%');
                })
                ->limit(6)
                ->get();
            $orders = Order::query()
                ->with('user')
                ->where(function ($query) use ($q) {
                    $query->where('order_number', 'like', '%'.$q.'%')
                        ->orWhere('shipping_name', 'like', '%'.$q.'%')
                        ->orWhere('shipping_phone', 'like', '%'.$q.'%');
                })
                ->latest()
                ->limit(8)
                ->get();
            $customers = User::query()
                ->where('role', UserRole::Customer)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', '%'.$q.'%')
                        ->orWhere('phone', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                })
                ->limit(8)
                ->get();
        }

        return view('admin.search.index', [
            'title' => AppStrings::SEARCH_RESULTS,
            'q' => $q,
            'products' => $products,
            'categories' => $categories,
            'orders' => $orders,
            'customers' => $customers,
            'banners' => $banners,
            'homeSections' => $homeSections,
        ]);
    }
}
