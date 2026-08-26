<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="route('admin.onboarding.create')"
        :create-label="$strings::ADD_ONBOARDING"
        subtitle="شرائح الترحيب التي تظهر لأول مرة بعد السبلاش. إن لم توجد شرائح مفعّلة يستخدم التطبيق الشرائح الافتراضية."
    />

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" style="max-width: 240px" placeholder="{{ $strings::SEARCH }}">
        <select name="status" class="form-select" style="max-width: 140px">
            <option value="">{{ $strings::STATUS }}</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')">{{ $strings::ACTIVE }}</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')">{{ $strings::INACTIVE }}</option>
        </select>
        <button class="btn btn-outline-success rounded-pill">{{ $strings::FILTER }}</button>
    </form>

    <div class="page-card p-4">
        @if ($slides->isEmpty())
            <x-admin.empty-state icon="bi-collection" :action="route('admin.onboarding.create')" :action-label="$strings::ADD_ONBOARDING" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>العنوان</th>
                            <th>الترتيب</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($slides as $slide)
                            <tr>
                                <td class="align-middle" style="width: 72px">
                                    @if ($slide->image_url)
                                        <img src="{{ \App\Support\Media::url($slide->image_url) }}" alt="" class="table-thumb" style="width:56px;height:40px;object-fit:cover">
                                    @else
                                        <span class="payment-method-thumb" style="width:56px;height:40px"><i class="bi bi-image"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $slide->title }}</div>
                                    <div class="text-muted small">{{ $slide->subtitle }}</div>
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($slide->description, 80) }}</div>
                                </td>
                                <td>{{ $slide->sort_order }}</td>
                                <td>
                                    @if ($slide->is_active)
                                        <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                    @else
                                        <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.onboarding.edit', $slide) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.onboarding.destroy', $slide) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger rounded-pill">{{ $strings::DELETE }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $slides->links() }}
        @endif
    </div>
</x-layouts.admin>
