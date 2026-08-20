<x-layouts.admin :title="$title">
    <div class="page-card p-4">
        @if ($slides->isEmpty())
            <x-admin.empty-state icon="bi-collection" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>الوصف</th>
                            <th>{{ $strings::STATUS }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($slides as $slide)
                            <tr>
                                <td>{{ $slide->title }}</td>
                                <td>{{ $slide->description }}</td>
                                <td><span class="badge badge-soft">{{ $slide->is_active ? 'نشط' : 'مخفي' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $slides->links() }}
        @endif
    </div>
</x-layouts.admin>
