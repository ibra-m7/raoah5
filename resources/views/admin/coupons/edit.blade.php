<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 860px">
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
            @csrf
            @method('PUT')
            @include('admin.coupons._form')
            <div class="d-flex gap-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
