<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="آخر محادثات العملاء والزوار مع المساعد"
    />

    <div class="mb-3">
        <a href="{{ route('admin.ai.index') }}" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-sliders ms-1"></i>
            إعدادات المساعد
        </a>
    </div>

    <div class="page-card p-4">
        @if ($conversations->isEmpty())
            <x-admin.empty-state icon="bi-stars" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>آخر رسالة</th>
                            <th>الرسائل</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conversations as $conversation)
                            @php
                                $last = $conversation->messages->first();
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.ai.conversations.show', $conversation) }}">{{ $conversation->id }}</a>
                                </td>
                                <td>
                                    @if ($conversation->user)
                                        {{ $conversation->user->name }}
                                    @else
                                        <span class="text-muted">زائر</span>
                                    @endif
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($last?->content, 70) ?: '—' }}</td>
                                <td><span class="badge badge-soft">{{ $conversation->messages_count }}</span></td>
                                <td>{{ $conversation->updated_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $conversations->links() }}
        @endif
    </div>
</x-layouts.admin>
