<x-layouts.admin :title="$title">
    <x-admin.page-head :title="$title" />

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="page-card p-4 p-md-5">
                <x-admin.help-note>
                    نزّل القالب (50 منتجاً، باركود من 1 إلى 50). سمِّ كل صورة برقم الباركود مثل 3.png وضعها في ZIP ثم ارفع الملفين معاً.
                </x-admin.help-note>

                <a href="{{ route('admin.products.import.template') }}" class="btn btn-brand mb-4">
                    <i class="bi bi-download ms-1"></i>
                    {{ $strings::DOWNLOAD_PRODUCT_TEMPLATE }}
                </a>

                <form method="POST" action="{{ route('admin.products.import.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">ملف Excel أو CSV</label>
                        <input type="file" name="file" accept=".xls,.xlsx,.csv,.xml" class="form-control @error('file') is-invalid @enderror" required>
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">الحد الأقصى 10 ميجابايت. الصيغ: xls / xlsx / csv</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">صور المنتجات (ZIP) — اختياري</label>
                        <input type="file" name="images_zip" accept=".zip,application/zip" class="form-control @error('images_zip') is-invalid @enderror">
                        @error('images_zip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">أسماء الملفات = الباركود، مثل 1.png و 3.jpg. الحد الأقصى 50 ميجابايت.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-brand">بدء الاستيراد</button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill">{{ $strings::CANCEL }}</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="page-card p-4">
                <h2 class="h5 fw-bold mb-3">الحقول</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>العمود</th>
                                <th>النوع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($columns as $column)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $column['header'] }}</div>
                                        <div class="text-muted small">{{ $column['hint'] }}</div>
                                    </td>
                                    <td>
                                        @if ($column['required'])
                                            <span class="badge badge-sale">إلزامي</span>
                                        @else
                                            <span class="badge badge-soft">اختياري</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($categories->isEmpty())
                    <div class="text-danger small mt-2">أضف قسماً واحداً على الأقل قبل الاستيراد.</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
