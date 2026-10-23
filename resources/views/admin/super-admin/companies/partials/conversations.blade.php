<div class="company-list-scroll">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th class="ps-3">Visitor</th><th>Brand</th><th>Status</th><th class="pe-3">Started</th></tr></thead>
            <tbody>
                @forelse($conversations as $chat)
                    <tr>
                        <td class="ps-3"><a href="{{ route('admin.chat.show', $chat->id) }}" class="text-body fw-semibold">{{ $chat->main_visitor?->user?->name ?: 'Visitor '.$chat->visitor_id }}</a></td>
                        <td>{{ $chat->brand?->name ?: '—' }}</td>
                        <td><span class="badge {{ $chat->status === 'open' ? 'bg-label-success' : 'bg-label-secondary' }}">{{ ucfirst($chat->status) }}</span></td>
                        <td class="pe-3 text-body-secondary small">{{ $chat->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-body-secondary">No conversations for this company.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($conversations->hasPages())
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3">
        <small class="text-body-secondary">Showing {{ $conversations->firstItem() }}–{{ $conversations->lastItem() }} of {{ number_format($conversations->total()) }} conversations</small>
        <div class="d-flex gap-2">
            @if($conversations->previousPageUrl())<a data-company-page class="btn btn-sm btn-outline-primary" href="{{ $conversations->previousPageUrl() }}">Previous</a>@endif
            @if($conversations->nextPageUrl())<a data-company-page class="btn btn-sm btn-outline-primary" href="{{ $conversations->nextPageUrl() }}">Next</a>@endif
        </div>
    </div>
@elseif($conversations->total() > 0)
    <small class="d-block text-body-secondary pt-2">Showing all {{ number_format($conversations->total()) }} conversations</small>
@endif
