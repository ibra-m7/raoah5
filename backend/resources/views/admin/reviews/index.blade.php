<x-layouts.admin :title="$title">
    <div class="page-card p-4">
        @if ($reviews->isEmpty())
            <x-admin.empty-state icon="bi-star" />
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>العميل</th>
                            <th>التقييم</th>
                            <th>التعليق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            <tr>
                                <td>{{ $review->product?->name }}</td>
                                <td>{{ $review->user?->name }}</td>
                                <td>{{ $review->rating }}/5</td>
                                <td>{{ $review->comment }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $reviews->links() }}
        @endif
    </div>
</x-layouts.admin>
