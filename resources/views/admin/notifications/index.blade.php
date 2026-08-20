<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="أرسل عروضاً فورية لكل العملاء، وتابع تنبيهات الطلبات التي تصل للتطبيق"
    />

    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge badge-soft align-self-center">عملاء مفعّلون: {{ $eligibleCount }}</span>
        @if ($fcmReady)
            <span class="badge badge-soft align-self-center">إرسال الأجهزة جاهز</span>
        @else
            <span class="badge text-bg-warning align-self-center">ضع ملف حساب خدمة Firebase ليصل الإشعار إلى شاشة القفل</span>
        @endif
    </div>

    <div class="page-card p-4 p-md-5 mb-4" style="max-width: 820px">
        <h2 class="h5 mb-3">إشعار فوري لكل العملاء</h2>
        <p class="text-muted mb-4">يصل للعملاء الذين فعّلوا الإشعارات في التطبيق، ويُحفظ في صندوق إشعاراتهم.</p>
        <form method="POST" action="{{ route('admin.notifications.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">العنوان</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" maxlength="80" required placeholder="اليوم تخفيضات وعروض">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">نص الرسالة</label>
                <textarea name="body" rows="4" class="form-control @error('body') is-invalid @enderror" maxlength="500" required placeholder="خصومات على المنظفات اليوم فقط داخل التطبيق">{{ old('body') }}</textarea>
                @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-4">
                <label class="form-label">النوع</label>
                <select name="type" class="form-select @error('type') is-invalid @enderror">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type', 'promo') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button class="btn btn-brand" @disabled($eligibleCount === 0)>
                <i class="bi bi-send ms-1"></i>
                إرسال الآن
            </button>
        </form>
    </div>

    <div class="page-card p-4 mb-4">
        <h2 class="h5 mb-3">الرسائل المرسلة</h2>
        @if ($campaigns->isEmpty())
            <p class="text-muted mb-0">لم تُرسل رسائل جماعية بعد.</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>النوع</th>
                            <th>العملاء</th>
                            <th>الأجهزة</th>
                            <th>التاريخ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campaigns as $campaign)
                            <tr>
                                <td>
                                    <strong>{{ $campaign->title }}</strong>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($campaign->body, 70) }}</div>
                                </td>
                                <td>{{ $campaign->type?->label() }}</td>
                                <td>{{ $campaign->recipients_count }}</td>
                                <td>{{ $campaign->push_count }}</td>
                                <td>{{ $campaign->sent_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.notifications.resend', $campaign) }}" class="d-inline" onsubmit="return confirm('إعادة إرسال هذه الرسالة لكل العملاء المفعّلين؟')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success rounded-pill" type="submit">
                                            <i class="bi bi-arrow-repeat ms-1"></i>
                                            إعادة الإرسال
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.simple-pager :paginator="$campaigns" />
        @endif
    </div>

    <div class="page-card p-4" id="notification-log">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">سجل الإشعارات</h2>
            @if ($notifications->total() > 0)
                <form method="POST" action="{{ route('admin.notifications.log.clear') }}" onsubmit="return confirm(@js($strings::CONFIRM_CLEAR_NOTIFICATION_LOG))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit">
                        <i class="bi bi-trash ms-1"></i>
                        {{ $strings::CLEAR_LOG }}
                    </button>
                </form>
            @endif
        </div>
        @if ($notifications->isEmpty())
            <x-admin.empty-state icon="bi-bell" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>العميل</th>
                            <th>النوع</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notifications as $notification)
                            <tr>
                                <td>{{ $notification->title }}</td>
                                <td>{{ $notification->user?->name ?? '—' }}</td>
                                <td>{{ $notification->type?->label() ?? $notification->type?->value }}</td>
                                <td>{{ $notification->read_at ? 'مقروء' : 'جديد' }}</td>
                                <td>{{ $notification->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.simple-pager :paginator="$notifications" />
        @endif
    </div>

</x-layouts.admin>
