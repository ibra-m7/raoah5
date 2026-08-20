<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Support\AppStrings;
use App\Support\Constants;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->with([
                'addresses' => fn ($query) => $query
                    ->withCount('orders')
                    ->orderByDesc('is_default')
                    ->latest('id'),
            ])
            ->withCount(['orders', 'addresses'])
            ->withSum('orders', 'total')
            ->withMax('orders', 'created_at')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();

        $recentOrders = Order::query()
            ->whereIn('user_id', $customers->pluck('id'))
            ->latest()
            ->get()
            ->groupBy('user_id')
            ->map(fn ($group) => $group->take(5));

        return view('admin.customers.index', [
            'title' => AppStrings::NAV_CUSTOMERS,
            'customers' => $customers,
            'recentOrders' => $recentOrders,
            'filters' => ['q' => $q],
        ]);
    }

    public function destroy(User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        DB::transaction(function () use ($customer) {
            $customer->tokens()->delete();
            $customer->deviceTokens()->delete();
            $customer->forceFill([
                'phone' => null,
                'email' => null,
                'google_id' => null,
                'phone_verified_at' => null,
            ])->save();
            $customer->delete();
        });

        return back()->with('success', AppStrings::CUSTOMER_DELETED);
    }

    public static function phoneDisplay(?string $phone): string
    {
        if (! filled($phone)) {
            return '—';
        }

        $normalized = Phone::normalize($phone);

        return $normalized ? Phone::display($normalized) : $phone;
    }

    public static function lastOrderLabel(mixed $value): string
    {
        if (! filled($value)) {
            return 'لا طلبات بعد';
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d H:i');
    }
}
