@extends('admin.layout.app')

@section('title', 'Shortcuts')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h4 class="mb-1">Shortcuts</h4>
                <p class="text-body-secondary mb-0">Create and manage reusable replies. Type <code>/</code> in a chat to use them.</p>
            </div>
            <button type="button" class="btn btn-primary" id="shortcut-new">
                <i class="bx bx-plus me-1"></i> Create shortcut
            </button>
        </div>

        <div class="card mb-4" id="shortcut-editor" hidden>
            <div class="card-header">
                <h5 class="mb-0" id="shortcut-editor-title">Create shortcut</h5>
            </div>
            <div class="card-body">
                <form id="shortcut-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="shortcut-name">Shortcut</label>
                            <div class="input-group">
                                <span class="input-group-text">/</span>
                                <input class="form-control" id="shortcut-name" name="shortcut" maxlength="50" pattern="[A-Za-z0-9][A-Za-z0-9_-]*" placeholder="greeting" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="shortcut-title">Title</label>
                            <input class="form-control" id="shortcut-title" name="title" maxlength="100" placeholder="Greeting" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="shortcut-content">Reply</label>
                            <textarea class="form-control" id="shortcut-content" name="content" maxlength="5000" rows="4" placeholder="Write the reply..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="shortcut-scope">Who can use it?</label>
                            <select class="form-select" id="shortcut-scope" name="scope">
                                <option value="personal">Only me</option>
                                <option value="team">Entire team</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="shortcut-brand-field" hidden>
                            <label class="form-label" for="shortcut-brand">Team / brand</label>
                            <select class="form-select" id="shortcut-brand" name="brand_id">
                                <option value="">Select a brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary" id="shortcut-save">Save shortcut</button>
                            <button type="button" class="btn btn-outline-secondary" id="shortcut-cancel">Cancel</button>
                        </div>
                    </div>
                    <div class="small text-danger mt-3" id="shortcut-form-status" role="status" aria-live="polite"></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Shortcut</th>
                            <th>Title</th>
                            <th>Reply</th>
                            <th>Availability</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="shortcut-table">
                        @forelse($shortcuts as $shortcut)
                            <tr data-shortcut="{{ $shortcut->id }}">
                                <td><code>/{{ $shortcut->shortcut }}</code></td>
                                <td>{{ $shortcut->title }}</td>
                                <td class="text-truncate" style="max-width: 24rem">{{ $shortcut->content }}</td>
                                <td>
                                    @if($shortcut->scope === 'team')
                                        <span class="badge bg-label-primary">Team · {{ $shortcut->brand?->name ?? 'Brand' }}</span>
                                    @else
                                        <span class="badge bg-label-secondary">Only me</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-text-primary shortcut-edit"
                                        data-id="{{ $shortcut->id }}"
                                        data-shortcut="{{ $shortcut->shortcut }}"
                                        data-title="{{ $shortcut->title }}"
                                        data-content="{{ $shortcut->content }}"
                                        data-scope="{{ $shortcut->scope }}"
                                        data-brand-id="{{ $shortcut->brand_id }}">Edit</button>
                                    <button type="button" class="btn btn-sm btn-text-danger shortcut-delete" data-id="{{ $shortcut->id }}">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr id="shortcut-empty">
                                <td colspan="5" class="text-center text-body-secondary py-5">No shortcuts yet. Create one to use it in chat with <code>/</code>.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        (() => {
            const form = document.getElementById('shortcut-form');
            const editor = document.getElementById('shortcut-editor');
            const scope = document.getElementById('shortcut-scope');
            const brandField = document.getElementById('shortcut-brand-field');
            const brand = document.getElementById('shortcut-brand');
            const status = document.getElementById('shortcut-form-status');
            const table = document.getElementById('shortcut-table');
            const storeUrl = @json(route('admin.chat-shortcuts.store'));
            const updateUrl = @json(route('admin.chat-shortcuts.update', ':id'));
            const deleteUrl = @json(route('admin.chat.shortcuts.destroy', ':id'));

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]').content;
            }

            function updateScopeFields() {
                const isTeam = scope.value === 'team';
                brandField.hidden = !isTeam;
                brand.required = isTeam;
            }

            function resetForm() {
                form.reset();
                delete form.dataset.shortcutId;
                document.getElementById('shortcut-editor-title').textContent = 'Create shortcut';
                document.getElementById('shortcut-save').textContent = 'Save shortcut';
                status.textContent = '';
                updateScopeFields();
                editor.hidden = true;
            }

            function openCreateForm() {
                resetForm();
                editor.hidden = false;
                document.getElementById('shortcut-name').focus();
            }

            document.getElementById('shortcut-new').addEventListener('click', openCreateForm);
            document.getElementById('shortcut-cancel').addEventListener('click', resetForm);
            scope.addEventListener('change', updateScopeFields);

            table.addEventListener('click', async event => {
                const edit = event.target.closest('.shortcut-edit');
                if (edit) {
                    form.reset();
                    form.dataset.shortcutId = edit.dataset.id;
                    form.elements.shortcut.value = edit.dataset.shortcut;
                    form.elements.title.value = edit.dataset.title;
                    form.elements.content.value = edit.dataset.content;
                    scope.value = edit.dataset.scope;
                    brand.value = edit.dataset.brandId || '';
                    updateScopeFields();
                    document.getElementById('shortcut-editor-title').textContent = 'Update shortcut';
                    document.getElementById('shortcut-save').textContent = 'Update shortcut';
                    status.textContent = '';
                    editor.hidden = false;
                    document.getElementById('shortcut-name').focus();
                    return;
                }

                const remove = event.target.closest('.shortcut-delete');
                if (!remove) return;
                remove.disabled = true;
                status.textContent = '';
                try {
                    const response = await fetch(deleteUrl.replace(':id', encodeURIComponent(remove.dataset.id)), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Could not delete shortcut.');
                    remove.closest('tr').remove();
                    if (!table.querySelector('tr[data-shortcut]')) {
                        table.innerHTML = '<tr id="shortcut-empty"><td colspan="5" class="text-center text-body-secondary py-5">No shortcuts yet. Create one to use it in chat with <code>/</code>.</td></tr>';
                    }
                } catch (error) {
                    status.textContent = error.message || 'Could not delete shortcut.';
                    remove.disabled = false;
                }
            });

            form.addEventListener('submit', async event => {
                event.preventDefault();
                status.textContent = '';
                const button = document.getElementById('shortcut-save');
                button.disabled = true;
                const payload = Object.fromEntries(new FormData(form).entries());
                if (!payload.brand_id) delete payload.brand_id;
                const shortcutId = form.dataset.shortcutId;
                const url = shortcutId ? updateUrl.replace(':id', encodeURIComponent(shortcutId)) : storeUrl;

                try {
                    const response = await fetch(url, {
                        method: shortcutId ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat().join(' ') || 'Could not save shortcut.');
                    window.location.reload();
                } catch (error) {
                    status.textContent = error.message || 'Could not save shortcut.';
                    button.disabled = false;
                }
            });

            updateScopeFields();
        })();
    </script>
@endsection
