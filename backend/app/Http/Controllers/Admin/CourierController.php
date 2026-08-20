<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourierRequest;
use App\Models\Courier;
use App\Services\Admin\AdminEventService;
use App\Services\Couriers\CourierLedgerService;
use App\Support\AppStrings;
use App\Support\Constants;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function __construct(
        private readonly CourierLedgerService $ledger,
        private readonly AdminEventService $events,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', '');

        $couriers = Courier::query()
            ->withCount('deliveredOrders')
            ->withSum(['ledgerEntries as collected_sum' => fn ($query) => $query->where('direction', 'debit')], 'amount')
            ->withSum(['ledgerEntries as settled_sum' => fn ($query) => $query->where('direction', 'credit')], 'amount')
            ->when($q !== '', function ($query) use ($q) {
                $normalized = \App\Support\Phone::normalize($q);
                $digits = \App\Support\Phone::digits($q);
                $query->where(function ($inner) use ($q, $normalized, $digits) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('phone', 'like', '%'.$q.'%');
                    if ($normalized) {
                        $inner->orWhere('phone', $normalized);
                    }
                    if (strlen($digits) >= 7) {
                        $inner->orWhere('phone', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();

        $couriers->getCollection()->transform(function (Courier $courier) {
            $collected = (float) ($courier->collected_sum ?? 0);
            $settled = (float) ($courier->settled_sum ?? 0);
            $net = round($collected - $settled, 2);
            $courier->owes_amount = round(max(0, $net), 2);
            $courier->owed_amount = round(max(0, -$net), 2);

            return $courier;
        });

        return view('admin.couriers.index', [
            'title' => AppStrings::NAV_COURIERS,
            'couriers' => $couriers,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.couriers.create', [
            'title' => AppStrings::ADD_COURIER,
            'courier' => new Courier(['is_active' => true]),
        ]);
    }

    public function store(CourierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Courier::query()->create($data);

        return redirect()
            ->route('admin.couriers.index')
            ->with('success', AppStrings::COURIER_CREATED);
    }

    public function edit(Courier $courier): View
    {
        $summary = $this->ledger->summary($courier);
        $entries = $courier->ledgerEntries()
            ->with('order:id,order_number')
            ->limit(50)
            ->get();

        return view('admin.couriers.edit', [
            'title' => AppStrings::EDIT_COURIER,
            'courier' => $courier->loadCount('deliveredOrders'),
            'summary' => $summary,
            'entries' => $entries,
        ]);
    }

    public function update(CourierRequest $request, Courier $courier): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $courier->update($data);

        return redirect()
            ->route('admin.couriers.edit', $courier)
            ->with('success', AppStrings::COURIER_UPDATED);
    }

    public function settle(Request $request, Courier $courier): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'amount.required' => 'أدخل مبلغ التسديد.',
            'amount.min' => 'أدخل مبلغاً أكبر من صفر.',
        ]);

        $entry = $this->ledger->settle(
            $courier,
            (float) $data['amount'],
            $data['note'] ?? null,
            $request->user(),
        );
        $this->events->courierSettled($courier, (float) $entry->amount);

        return redirect()
            ->route('admin.couriers.edit', $courier)
            ->with('success', AppStrings::COURIER_SETTLED);
    }

    public function destroy(Courier $courier): RedirectResponse
    {
        $courier->delete();

        return redirect()
            ->route('admin.couriers.index')
            ->with('success', AppStrings::COURIER_DELETED);
    }
}
