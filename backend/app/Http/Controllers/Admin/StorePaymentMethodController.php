<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentMethodRequest;
use App\Models\StorePaymentMethod;
use App\Services\Admin\StorePaymentMethodService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorePaymentMethodController extends Controller
{
    public function __construct(private readonly StorePaymentMethodService $methods) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status']);

        return view('admin.payment-methods.index', [
            'title' => AppStrings::NAV_PAYMENT_METHODS,
            'methods' => $this->methods->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.payment-methods.create', [
            'title' => AppStrings::ADD_PAYMENT_METHOD,
            'method' => new StorePaymentMethod([
                'is_active' => true,
                'sort_order' => 0,
                'icon' => 'bi-credit-card',
            ]),
            'icons' => $this->methods->iconOptions(),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $this->methods->create($request->validated());

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', AppStrings::PAYMENT_METHOD_CREATED);
    }

    public function edit(StorePaymentMethod $payment_method): View
    {
        return view('admin.payment-methods.edit', [
            'title' => AppStrings::EDIT_PAYMENT_METHOD,
            'method' => $payment_method,
            'icons' => $this->methods->iconOptions(),
        ]);
    }

    public function update(StorePaymentMethodRequest $request, StorePaymentMethod $payment_method): RedirectResponse
    {
        $this->methods->update($payment_method, $request->validated());

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', AppStrings::PAYMENT_METHOD_UPDATED);
    }

    public function destroy(StorePaymentMethod $payment_method): RedirectResponse
    {
        try {
            $this->methods->delete($payment_method);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('admin.payment-methods.index')
                ->with('error', collect($e->errors())->flatten()->first() ?: 'تعذر الحذف.');
        }

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', AppStrings::PAYMENT_METHOD_DELETED);
    }
}
