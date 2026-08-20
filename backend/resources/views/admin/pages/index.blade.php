<x-layouts.admin :title="$title">
    <div class="page-card p-4">
        @if ($pages->isEmpty())
            <x-admin.empty-state icon="bi-file-text" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>المعرف</th>
                            <th>{{ $strings::STATUS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td>{{ $page->title }}</td>
                                <td>{{ $page->slug }}</td>
                                <td><span class="badge badge-soft">{{ $page->is_active ? 'نشط' : 'مخفي' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $pages->links() }}
        @endif
    </div>
</x-layouts.admin>
