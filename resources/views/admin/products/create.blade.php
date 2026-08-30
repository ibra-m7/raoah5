<x-layouts.admin :title="$title">
    <div class="page-card product-create-card p-4 p-md-5">
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="product-create-form">
            @csrf
            @include('admin.products._form')
            <div class="product-form-actions d-flex gap-2 pt-2">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
        @include('admin.products._gift-modal')
    </div>
</x-layouts.admin>
