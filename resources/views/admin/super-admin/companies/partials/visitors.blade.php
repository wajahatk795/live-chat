<div class="company-list-scroll">
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Visitor</th><th>Brand</th><th>Location</th><th class="text-end">Last seen</th></tr></thead>
        <tbody>
            @forelse($visitors as $visitor)
                <tr>
                    <td><div class="fw-semibold">{{ $visitor->user?->name ?: 'Visitor '.$visitor->id }}</div><div class="text-body-secondary small">{{ $visitor->user?->email ?: $visitor->ip_address ?: '—' }}</div></td>
                    <td>{{ $visitor->brand?->name ?: '—' }}</td>
                    <td>{{ collect([$visitor->city, $visitor->country])->filter()->implode(', ') ?: '—' }}</td>
                    <td class="text-end text-body-secondary small">{{ $visitor->last_seen_at?->diffForHumans() ?: $visitor->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-4 text-center text-body-secondary">No visitors for this company.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>
@if($visitors->hasPages())
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3">
        <small class="text-body-secondary">Showing {{ $visitors->firstItem() }}–{{ $visitors->lastItem() }} of {{ number_format($visitors->total()) }} visitors</small>
        <div class="d-flex gap-2">
            @if($visitors->previousPageUrl())<a data-company-page class="btn btn-sm btn-outline-primary" href="{{ $visitors->previousPageUrl() }}">Previous</a>@endif
            @if($visitors->nextPageUrl())<a data-company-page class="btn btn-sm btn-outline-primary" href="{{ $visitors->nextPageUrl() }}">Next</a>@endif
        </div>
    </div>
@else
    @if($visitors->total() > 0)<small class="d-block text-body-secondary pt-3">Showing all {{ number_format($visitors->total()) }} visitors</small>@endif
@endif
