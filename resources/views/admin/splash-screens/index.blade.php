<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :create="route('admin.splash-screens.create')"
        :create-label="$strings::ADD_SPLASH"
        subtitle="إن فعّلت شاشة هنا تظهر في التطبيق بدل السبلاش الافتراضي. إن لم توجد شاشة مفعّلة يبقى التصميم الحالي."
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
        @if ($splashes->isEmpty())
            <x-admin.empty-state icon="bi-phone" :action="route('admin.splash-screens.create')" :action-label="$strings::ADD_SPLASH" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>العنوان</th>
                            <th>النوع</th>
                            <th>المدة</th>
                            <th>{{ $strings::STATUS }}</th>
                            <th>{{ $strings::ACTIONS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($splashes as $splash)
                            <tr>
                                <td class="align-middle" style="width: 72px">
                                    @if ($splash->media_type === 'video')
                                        <span class="payment-method-thumb" style="width:56px;height:40px"><i class="bi bi-camera-video"></i></span>
                                    @elseif ($splash->media_url)
                                        <img src="{{ \App\Support\Media::url($splash->media_url) }}" alt="" class="table-thumb" style="width:56px;height:40px;object-fit:cover">
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $splash->title ?: 'بدون عنوان' }}</div>
                                </td>
                                <td>{{ $splash->media_type === 'video' ? 'فيديو' : 'صورة' }}</td>
                                <td>{{ number_format($splash->duration_ms / 1000, 1) }} ث</td>
                                <td>
                                    @if ($splash->is_active)
                                        <span class="badge badge-soft">{{ $strings::LIVE_IN_APP }}</span>
                                    @else
                                        <span class="badge badge-soft">{{ $strings::INACTIVE }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.splash-screens.edit', $splash) }}" class="btn btn-sm btn-outline-success rounded-pill">{{ $strings::EDIT }}</a>
                                        <form method="POST" action="{{ route('admin.splash-screens.destroy', $splash) }}" onsubmit="return confirm('{{ $strings::CONFIRM_DELETE }}')">
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
            {{ $splashes->links() }}
        @endif
    </div>
</x-layouts.admin>
