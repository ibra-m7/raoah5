<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="خصومات المنتجات التي تظهر في شريط العروض داخل التطبيق"
        :create="route('admin.offers.create')"
        :create-label="$strings::ADD_OFFER"
    />

    <x-admin.help-note>العرض = منتج سعره المخفّض أقل من سعره الأصلي. حذف العرض هنا يلغي الخصم فقط ولا يحذف المنتج.</x-admin.help-note>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 260px" placeholder="ابحث عن منتج عليه خصم...">
        <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
    </form>

    <div class="page-card p-4">
        @if ($offers->isEmpty())
            <x-admin.empty-state icon="bi-percent" :action="route('admin.offers.create')" :action-label="$strings::ADD_OFFER" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>المنتج</th>
                            <th>السعر الأصلي</th>
                            <th>سعر العرض</th>
                            <th>الخصم</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($offers as $product)
                            <tr>
                                <td>
                                    @if ($product->primaryImage?->url)
                                        <img src="{{ \App\Support\Media::url($product->primaryImage->url) }}" alt="" class="table-thumb">
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $product->name }}</div>
                                    <div class="text-muted small">{{ $product->category?->name }}</div>
                                </td>
                                <td>{{ number_format((float) $product->price, 2) }} {{ $strings::CURRENCY }}</td>
                                <td class="fw-bold">{{ number_format((float) $product->discount_price, 2) }} {{ $strings::CURRENCY }}</td>
                                <td><span class="badge badge-sale">{{ $product->discount_percent }}%</span></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.offers.edit', $product) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.offers.destroy', $product) }}" onsubmit="return confirm('سيتم إلغاء الخصم عن هذا المنتج فقط.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger rounded-pill">إلغاء الخصم</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $offers->links() }}
        @endif
    </div>
</x-layouts.admin>
