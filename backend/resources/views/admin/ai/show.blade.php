<x-layouts.admin :title="$title">
    <x-admin.page-head
        :title="$title"
        :subtitle="$conversation->user?->name ? 'عميل: '.$conversation->user->name : 'زائر'"
    />

    <div class="mb-3">
        <a href="{{ route('admin.ai.conversations') }}" class="btn btn-outline-secondary rounded-pill">
            {{ $strings::BACK }}
        </a>
    </div>

    <div class="page-card p-4" style="max-width: 820px">
        @forelse ($conversation->messages as $message)
            <div class="mb-3 p-3 rounded-3 {{ $message->role?->value === 'user' ? 'bg-light' : 'border' }}">
                <div class="small text-muted mb-1">
                    {{ $message->role?->value === 'user' ? 'العميل' : 'المساعد' }}
                    · {{ $message->created_at?->format('Y-m-d H:i') }}
                </div>
                <div>{{ $message->content }}</div>
                @if ($message->suggested_product_ids)
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        @foreach ($message->suggested_product_ids as $productId)
                            @php $product = $products->get((int) $productId); @endphp
                            <span class="badge badge-soft">
                                {{ $product?->name ?? ('منتج #'.$productId) }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <x-admin.empty-state icon="bi-chat" />
        @endforelse
    </div>
</x-layouts.admin>
