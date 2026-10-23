@extends('admin.layout.app')
@section('title', $company->name)
@section('content')
<style>
    .company-list-scroll{height:390px;overflow:auto;overscroll-behavior:contain;border-radius:.35rem}
    .company-list-scroll thead th{position:sticky;top:0;z-index:2;background:var(--bs-card-bg,#2b2c40)}
    .company-list-scroll::-webkit-scrollbar{width:8px;height:8px}
    .company-list-scroll::-webkit-scrollbar-thumb{border-radius:8px;background:rgba(105,108,255,.45)}
    .company-list-panel{min-width:0}
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4"><div class="d-flex align-items-center gap-3"><a href="{{ route('admin.super-admin.companies.index') }}" class="btn btn-icon btn-label-secondary"><i class="bx bx-arrow-back"></i></a><div><div class="d-flex align-items-center gap-2"><h1 class="h4 mb-0">{{ $company->name }}</h1><span class="badge {{ $company->is_active?'bg-label-success':'bg-label-secondary' }}">{{ $company->is_active?'Active':'Inactive' }}</span></div><div class="text-body-secondary small mt-1">{{ $company->domain ?: ($company->email ?: 'No domain set') }} · {{ ucfirst($company->subscription_plan) }} plan</div></div></div><div class="d-flex gap-2"><a class="btn btn-label-primary" href="{{ route('admin.super-admin.companies.edit',$company) }}"><i class="bx bx-edit me-1"></i>Edit</a><form method="POST" action="{{ route('admin.super-admin.companies.toggle',$company) }}">@csrf @method('PATCH')<button class="btn {{ $company->is_active?'btn-outline-warning':'btn-outline-success' }}">{{ $company->is_active?'Deactivate':'Activate' }}</button></form></div></div>
    <div class="row g-3 mb-4"><div class="col-sm-6 col-xl-3"><div class="card card-body"><span class="text-body-secondary small">Company team</span><strong class="fs-4">{{ $company->team_members_count }}</strong></div></div><div class="col-sm-6 col-xl-3"><div class="card card-body"><span class="text-body-secondary small">Brands / workspaces</span><strong class="fs-4">{{ $company->brands_count }}</strong></div></div><div class="col-sm-6 col-xl-3"><div class="card card-body"><span class="text-body-secondary small">Visitors</span><strong class="fs-4">{{ $company->visitors_count }}</strong></div></div><div class="col-sm-6 col-xl-3"><div class="card card-body"><span class="text-body-secondary small">Conversations</span><strong class="fs-4">{{ number_format($chatCount) }}</strong></div></div></div>
    <section class="card mb-4"><div class="card-header"><h2 class="h6 mb-1">Company workspaces</h2><p class="text-body-secondary small mb-0">Brands that own this company's visitor and chat data</p></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="ps-4">Brand</th><th>Domain</th><th>Chats</th><th class="pe-4">Form submissions</th></tr></thead><tbody>@forelse($brands as $brand)<tr><td class="ps-4 fw-semibold">{{ $brand->name ?: 'Unnamed brand' }}</td><td>{{ $brand->domain ?: '—' }}</td><td>{{ $brand->chats_count }}</td><td class="pe-4">{{ $brand->form_submissions_count }}</td></tr>@empty<tr><td colspan="4" class="py-4 text-center text-body-secondary">No brands linked to this company.</td></tr>@endforelse</tbody></table></div></section>
    <div class="row g-4 mb-4">
    <div class="col-xl-7"><section class="card h-100 company-list-panel" id="company-visitors" data-list-url="{{ route('admin.super-admin.companies.visitors',$company) }}"><div class="card-header d-flex justify-content-between align-items-center gap-2"><div><h2 class="h6 mb-1">All visitors</h2><p class="text-body-secondary small mb-0">{{ number_format($company->visitors_count) }} visitors · 25 per page</p></div><button type="button" class="btn btn-sm btn-primary company-list-load"><i class="bx bx-list-ul me-1"></i>Load visitors</button></div><div class="p-3" data-list-content><div class="text-body-secondary small text-center py-3">Visitor records load when you open this list.</div></div></section></div>
    <div class="col-xl-5"><section class="card h-100 company-list-panel" id="company-conversations" data-list-url="{{ route('admin.super-admin.companies.conversations',$company) }}"><div class="card-header d-flex justify-content-between align-items-center gap-2"><div><h2 class="h6 mb-1">Recent conversations</h2><p class="text-body-secondary small mb-0">{{ number_format($chatCount) }} conversations · 25 per page</p></div><button type="button" class="btn btn-sm btn-outline-primary company-list-load"><i class="bx bx-message-square-dots me-1"></i>Load conversations</button></div><div class="p-3" data-list-content><div class="text-body-secondary small text-center py-3">Conversation records load when you open this list.</div></div></section></div></div>
    <section class="card mb-4"><div class="card-header"><h2 class="h6 mb-0">Recent form submissions</h2></div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th class="ps-4">Form</th><th>Brand</th><th>Page</th><th class="pe-4">Submitted</th></tr></thead><tbody>@forelse($recentSubmissions as $submission)<tr><td class="ps-4">{{ $submission->form_action ?: 'Website form' }}</td><td>{{ $submission->brand?->name ?: '—' }}</td><td class="text-truncate" style="max-width:260px">{{ $submission->page_url ?: '—' }}</td><td class="pe-4 text-body-secondary small">{{ $submission->created_at->diffForHumans() }}</td></tr>@empty<tr><td colspan="4" class="py-4 text-center text-body-secondary">No form submissions.</td></tr>@endforelse</tbody></table></div></section>
    <section class="card"><div class="card-header"><h2 class="h6 mb-1">Company team</h2><p class="text-body-secondary small mb-0">Open the company workspace as an Admin or Agent.</p></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-4">Name</th><th>Email</th><th>Role</th><th>Joined</th><th class="text-end pe-4">Workspace access</th></tr></thead><tbody>@forelse($members as $member)<tr><td class="ps-4 fw-semibold">{{ $member->name }}</td><td>{{ $member->email }}</td><td><span class="badge {{ (int)$member->role===1?'bg-label-primary':'bg-label-info' }}">{{ $member->isRole() }}</span></td><td class="text-body-secondary small">{{ $member->created_at->format('M j, Y') }}</td><td class="text-end pe-4"><form method="POST" action="{{ route('admin.super-admin.companies.impersonate', [$company, $member]) }}">@csrf<button class="btn btn-sm btn-primary" type="submit"><i class="bx bx-log-in-circle me-1"></i>Open as {{ $member->isRole() }}</button></form></td></tr>@empty<tr><td colspan="5" class="text-center py-4 text-body-secondary">No team members yet.</td></tr>@endforelse</tbody></table></div>@if($members->hasPages())<div class="p-3">{{ $members->links() }}</div>@endif</section>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.company-list-panel').forEach(panel => {
        const content = panel.querySelector('[data-list-content]');
        const button = panel.querySelector('.company-list-load');
        const load = async (url = panel.dataset.listUrl) => {
            button.disabled = true;
            content.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span><span class="ms-2 text-body-secondary small">Loading…</span></div>';
            try {
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('This list could not be loaded.');
                content.innerHTML = await response.text();
            } catch (error) {
                content.innerHTML = '';
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger mb-0';
                alert.textContent = error.message;
                content.appendChild(alert);
            } finally {
                button.disabled = false;
            }
        };
        button.addEventListener('click', () => load());
        content.addEventListener('click', event => {
            const link = event.target.closest('a[data-company-page]');
            if (!link) return;
            event.preventDefault();
            load(link.href);
        });
    });
});
</script>
@endsection
