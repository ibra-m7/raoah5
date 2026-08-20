<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        subtitle="تحكم باسم المساعد ورسالته وأسلوبه وعدد المنتجات التي يظهرها في التطبيق"
    />

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.ai.conversations') }}" class="btn btn-outline-success rounded-pill">
            <i class="bi bi-chat-dots ms-1"></i>
            المحادثات
            @if ($conversationsCount)
                <span class="badge text-bg-success">{{ $conversationsCount }}</span>
            @endif
        </a>
        @if ($hasApiKey)
            <span class="badge badge-soft align-self-center">مفتاح Gemini جاهز على الخادم</span>
        @else
            <span class="badge text-bg-warning align-self-center">أضف GEMINI_API_KEY في ملف .env للخادم</span>
        @endif
    </div>

    <div class="page-card p-4 p-md-5" style="max-width: 860px">
        <form method="POST" action="{{ route('admin.ai.update') }}">
            @csrf
            @method('PUT')

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="ai_enabled" @checked(session()->hasOldInput() ? old('enabled') : $settings['enabled'])>
                <label class="form-check-label" for="ai_enabled">تشغيل المساعد في التطبيق</label>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="guests_allowed" value="1" id="ai_guests" @checked(session()->hasOldInput() ? old('guests_allowed') : $settings['guests_allowed'])>
                <label class="form-check-label" for="ai_guests">السماح للزوار باستخدام المساعد بدون تسجيل</label>
            </div>

            <div class="mb-3">
                <label class="form-label">اسم المساعد</label>
                <input type="text" name="name" value="{{ old('name', $settings['name']) }}" class="form-control @error('name') is-invalid @enderror" required maxlength="40">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">رسالة الترحيب</label>
                <textarea name="welcome" rows="3" class="form-control @error('welcome') is-invalid @enderror" required maxlength="500">{{ old('welcome', $settings['welcome']) }}</textarea>
                @error('welcome') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">تعليمات الأسلوب (System Prompt)</label>
                <textarea name="system_prompt" rows="8" class="form-control @error('system_prompt') is-invalid @enderror" required maxlength="4000">{{ old('system_prompt', $settings['system_prompt']) }}</textarea>
                <div class="form-text">يظهر للذكاء الاصطناعي فقط. التطبيق يعرض المنتجات الحقيقية من الكتالوج.</div>
                @error('system_prompt') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">أقصى عدد منتجات في الرد</label>
                    <input type="number" name="max_products" min="2" max="8" value="{{ old('max_products', $settings['max_products']) }}" class="form-control @error('max_products') is-invalid @enderror" required>
                    <div class="form-text">تظهر في التطبيق كشبكة عمودين.</div>
                    @error('max_products') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label">نموذج Gemini</label>
                    <select name="model" class="form-select @error('model') is-invalid @enderror" required>
                        @foreach ($models as $model)
                            <option value="{{ $model }}" @selected(old('model', $settings['model']) === $model)>{{ $model }}</option>
                        @endforeach
                    </select>
                    @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <button class="btn btn-brand">{{ $strings::SAVE }}</button>
        </form>
    </div>
</x-layouts.admin>
