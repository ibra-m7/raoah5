<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="شريط الإعلانات أعلى الصفحة الرئيسية في التطبيق"
        :create="route('admin.banners.create')"
        :create-label="$strings::ADD_BANNER"
    />

    <x-admin.help-note>أضف صورة عريضة وعنواناً، ويمكن ربط الإعلان بمنتج أو قسم. يظهر في التطبيق فوراً بعد الحفظ.</x-admin.help-note>

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
        @if ($banners->isEmpty())
            <x-admin.empty-state icon="bi-image" :action="route('admin.banners.create')" :action-label="$strings::ADD_BANNER" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>العنوان</th>
                            <th>الوجهة</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banners as $banner)
                            <tr>
                                <td>
                                    @if ($banner->image_url)
                                        <img src="{{ \App\Support\Media::url($banner->image_url) }}" alt="" class="table-thumb" style="width: 72px; height: 44px">
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $banner->title }}</div>
                                    <div class="text-muted small">{{ $banner->subtitle }}</div>
                                </td>
                                <td>{{ $banner->link_type?->label() }}</td>
                                <td>
                                    @if ($banner->isLive())
                                        <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                    @elseif ($banner->is_active)
                                        <span class="badge badge-warn">{{ $strings::SCHEDULED }}</span>
                                    @else
                                        <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
            {{ $banners->links() }}
        @endif
    </div>
</x-layouts.admin>
