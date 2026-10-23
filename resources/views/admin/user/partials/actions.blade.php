<div class="dropdown">
    @if(session()->has('super_admin_impersonator_id'))
        <form action="{{ route('super-admin.switch-user', $user->id) }}" method="POST" class="d-inline me-1">
            @csrf
            <button type="submit" class="btn btn-sm btn-label-primary" title="Super Admin: open this agent workspace">
                <i class="bx bx-log-in-circle me-1"></i>Login as Agent
            </button>
        </form>
    @endif
    <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
        <i class="bx bx-dots-vertical-rounded"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end m-0">
        <a class="dropdown-item" href="{{ route('admin.users.edit', $user->id) }}">
            <i class="bx bx-edit me-2"></i> Edit
        </a>
        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure?')">
                <i class="bx bx-trash me-2"></i> Delete
            </button>
        </form>
    </div>
</div>
