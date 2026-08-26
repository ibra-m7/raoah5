<x-layouts.admin :title="$title">
    <div class="page-card p-4 p-md-5" style="max-width: 760px">
        <form method="POST" action="{{ route('admin.onboarding.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.onboarding._form')
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-brand">{{ $strings::SAVE }}</button>
                <a href="{{ route('admin.onboarding.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
