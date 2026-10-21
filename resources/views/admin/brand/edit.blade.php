@extends('admin.layout.app')

@section('title', 'Edit Brand')

@section('content')
    <style>
        span.select2-selection.select2-selection--multiple {
            background: transparent;
            display: block;
            width: 100%;
            padding: .543rem .9375rem;
            font-size: .9375rem;
            font-weight: 400;
            line-height: 1.375;
            color: var(--bs-heading-color);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: transparent;
            background-clip: padding-box;
            border-radius: var(--bs-border-radius);
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            border-color:
                color-mix(in srgb, #e6e6f1 22%, #393a5a);
        }

        li.select2-selection__choice {
            color: black;
            padding-left: 20px !important;
        }

        span.select2-dropdown {
            background: #2b2c40 !important;
        }
    </style>
    <style>
        .install-wrapper {
            background: #1e1e2f;
            border-radius: 12px;
            padding: 30px;
            color: #fff;
            position: relative;
        }

        .tab-back-btn {
            position: absolute;
            top: 20px;
            right: 25px;
            background: #2a2a40;
            border: 1px solid #444;
            color: #fff;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s;
        }

        .tab-back-btn:hover {
            background: #6366f1;
            border-color: #6366f1;
            color: #fff;
        }

        .script-box {
            background: #2a2a40;
            border-radius: 10px;
            padding: 20px;
            font-size: 14px;
            color: #bfc7ff;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }

        .script-box:hover {
            background: #32325a;
            box-shadow: 0 0 15px rgba(105, 108, 255, 0.4);
        }

        .copy-toast {
            position: absolute;
            top: 10px;
            right: 15px;
            background: #4ade80;
            color: #000;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: none;
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col">
                <div class="card mb-6">
                    <div class="nav-align-top">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#brand-info"
                                    role="tab" aria-selected="true"><span
                                        class="icon-base bx bx-user d-sm-none"></span><span class="d-none d-sm-block">Brand
                                        Info</span></button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#chat-settings" role="tab"
                                    aria-selected="false" tabindex="-1"><span
                                        class="icon-base bx bx-user-pin d-sm-none"></span><span
                                        class="d-none d-sm-block">Chat Settings</span></button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#install-script"
                                    role="tab">
                                    <span class="d-none d-sm-block">Install Script</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade active show" id="brand-info" role="tabpanel">
                            <form action="{{ route('admin.brand.update', ['brand' => $brand->id]) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Brand Name</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $brand->name) }}" required>
                                        @error('name')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="user_ids" class="form-label fw-bold">Select Users</label>
                                        <select
                                            class="form-select @error('user_ids') is-invalid @enderror @error('user_ids.*') is-invalid @enderror"
                                            id="user_ids" name="user_ids[]" multiple="multiple" required>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}"
                                                    {{ in_array($user->id, old('user_ids', $selectedUserIds ?? [])) ? 'selected' : '' }}>
                                                    {{ $user->name }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_ids')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="{{ old('email', $brand->email) }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="url" class="form-label">Brand URL</label>
                                        <input type="text" class="form-control" id="url" name="url"
                                            value="{{ old('url', $brand->url) }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="domain" class="form-label">Brand Domain</label>
                                        <input type="text" class="form-control" id="domain" name="domain"
                                            value="{{ old('domain', $brand->domain) }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="1"
                                                {{ old('status', $brand->status ?? '') == 1 ? 'selected' : '' }}>Active
                                            </option>
                                            <option value="0"
                                                {{ old('status', $brand->status ?? '') == 0 ? 'selected' : '' }}>
                                                Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="allowed_ips" class="form-label">Allowed IPs</label>
                                        <textarea class="form-control" name="allowed_ips"
                                            placeholder="Example: 192.168.1.1,192.168.1.5">{{ old('allowed_ips', $brand->allowed_ips) }}</textarea>

                                        <small class="text-muted">
                                            Multiple IPs comma separated likho
                                        </small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Update Brand</button>
                                        <a href="{{ route('admin.brand') }}" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="chat-settings" role="tabpanel">
                            <form method="POST" action="{{ route('admin.brand.chat.settings') }}">
                                @csrf
                                <div class="row">
                                    <input type="hidden" name="brand_id" value="{{ $brand->id ?? '' }}">

                                    <div class="col-md-6 mb-3">
                                        <label>Enable Chat</label>
                                        <select class="form-select" name="chat_enabled">
                                            <option value="1"
                                                {{ isset($chatSettings) && $chatSettings->chat_enabled == 1 ? 'selected' : '' }}>
                                                Enabled</option>
                                            <option value="0"
                                                {{ isset($chatSettings) && $chatSettings->chat_enabled == 0 ? 'selected' : '' }}>
                                                Disabled</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label>Primary Color</label>
                                        <input type="color" name="primary_color" class="form-control"
                                            value="{{ $chatSettings->primary_color ?? '#696cff' }}">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>Popup Delay (Seconds)</label>
                                        <input type="number" name="popup_delay" class="form-control"
                                            value="{{ $chatSettings->popup_delay ?? '5' }}">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>Sound Notification</label>
                                        <select class="form-select" name="sound_enabled">
                                            <option value="1"
                                                {{ isset($chatSettings) && $chatSettings->sound_enabled == 1 ? 'selected' : '' }}>
                                                Enabled</option>
                                            <option value="0"
                                                {{ isset($chatSettings) && $chatSettings->sound_enabled == 0 ? 'selected' : '' }}>
                                                Disabled</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>Chat Position</label>
                                        <select class="form-select" name="chat_position">
                                            <option value="left"
                                                {{ isset($chatSettings) && $chatSettings->chat_position == 'left' ? 'selected' : '' }}>
                                                Left</option>
                                            <option value="right"
                                                {{ isset($chatSettings) && $chatSettings->chat_position == 'right' ? 'selected' : '' }}>
                                                Right</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label>Welcome Message</label>
                                        <textarea name="welcome_message" class="form-control">{{ $chatSettings->welcome_message ?? '' }}</textarea>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label>Offline Message</label>
                                        <textarea name="offline_message" class="form-control">{{ $chatSettings->offline_message ?? '' }}</textarea>
                                    </div>

                                    <div class="col-md-12 mb-3 mt-4">
                                        <h5 class="fw-bold border-bottom pb-2">AI Fallback Settings</h5>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>Enable AI Fallback</label>
                                        <select class="form-select" name="ai_enabled">
                                            <option value="1" {{ isset($chatSettings) && $chatSettings->ai_enabled == 1 ? 'selected' : '' }}>Enabled</option>
                                            <option value="0" {{ isset($chatSettings) && $chatSettings->ai_enabled == 0 ? 'selected' : '' }}>Disabled</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>AI Handoff Delay (Seconds)</label>
                                        <input type="number" name="ai_handoff_delay" class="form-control" min="10" max="3600"
                                            value="{{ $chatSettings->ai_handoff_delay ?? 60 }}" placeholder="60">
                                        <small class="text-muted">
                                            Kitne seconds ke baad AI takeover kare agar agent reply na kare. Default: 60 seconds.
                                        </small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>AI Provider</label>
                                        <select class="form-select" name="ai_provider" id="ai_provider" onchange="updateModelOptions()">
                                            <option value="ollama" {{ (isset($chatSettings) && $chatSettings->ai_provider == 'ollama') || !isset($chatSettings) ? 'selected' : '' }}>Ollama (Local)</option>
                                            <option value="openai" {{ isset($chatSettings) && $chatSettings->ai_provider == 'openai' ? 'selected' : '' }}>OpenAI (ChatGPT)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label>AI Model</label>
                                        <input type="text" name="ai_model" id="ai_model" class="form-control" list="model_suggestions" value="{{ $chatSettings->ai_model ?? 'llama3' }}" placeholder="e.g. llama3">
                                        <datalist id="model_suggestions">
                                            <option value="llama3">
                                            <option value="mistral">
                                            <option value="gemma2">
                                            <option value="phi3">
                                        </datalist>
                                        <small class="text-muted" id="model_help_text">Ollama model installed on your machine (e.g. llama3, mistral)</small>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label>AI System Prompt</label>
                                        <textarea name="ai_prompt" class="form-control" rows="3" placeholder="You are a helpful customer support agent...">{{ $chatSettings->ai_prompt ?? '' }}</textarea>
                                        <small class="text-muted">Give the AI custom instructions on tone or persona.</small>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="fw-bold">Auto-Trained Website Knowledge</label>
                                            <div>
                                                @if(isset($chatSettings) && $chatSettings->ai_trained_at)
                                                    <span class="badge bg-success me-2">
                                                        <i class="bx bx-check-circle"></i> Trained {{ \Carbon\Carbon::parse($chatSettings->ai_trained_at)->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning me-2">
                                                        <i class="bx bx-time"></i> Not Trained Yet
                                                    </span>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnResyncAi" onclick="resyncBrandAi({{ $brand->id }})">
                                                    <i class="bx bx-refresh"></i> Re-sync from Website URL
                                                </button>
                                            </div>
                                        </div>
                                        <textarea name="website_knowledge" id="website_knowledge" class="form-control" rows="6" placeholder="Website knowledge will be automatically extracted from {{ $brand->url ?: $brand->domain }}...">{{ $chatSettings->website_knowledge ?? '' }}</textarea>
                                        <small class="text-muted">This knowledge is automatically extracted when you add/update the Brand URL. You can also edit it manually if needed.</small>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <button type="submit" class="btn btn-primary">Save Settings</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="install-script" role="tabpanel">
                            <div class="install-wrapper mt-4">
                                <a href="{{ route('admin.brand') }}" class="tab-back-btn">
                                    ← Back
                                </a>
                                <h5 class="fw-bold mb-3">Install Live Chat Widget</h5>
                                <p class="text-muted">
                                    Copy this code and paste it before the
                                    <code>&lt;/body&gt;</code> tag of your website.
                                </p>
                                <div class="script-box mt-3" onclick="copyInstallScript()">
                                    <span class="copy-toast" id="toastInstall">Copied!</span>
                                    <pre id="installScript" class="mb-0">{{ $script }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
    <script>
        $("#user_ids").select2({
            placeholder: "Select a User",
            allowClear: true
        });
    </script>
    <script>
        function copyInstallScript() {
            let text = document.getElementById("installScript").innerText;

            navigator.clipboard.writeText(text).then(function() {
                let toast = document.getElementById("toastInstall");
                toast.style.display = "inline-block";

                setTimeout(() => {
                    toast.style.display = "none";
                }, 1500);
            });
        }

        function resyncBrandAi(brandId) {
            const btn = document.getElementById("btnResyncAi");
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Training AI...';

            let url = "{{ route('admin.brand.resync-ai', ':id') }}".replace(':id', brandId);

            fetch(url, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                }
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-check"></i> Training Started!';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    location.reload();
                }, 2000);
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert("Error starting AI training. Please check logs.");
            });
        }

        function updateModelOptions() {
            const provider = document.getElementById("ai_provider").value;
            const modelInput = document.getElementById("ai_model");
            const suggestions = document.getElementById("model_suggestions");
            const helpText = document.getElementById("model_help_text");

            if (provider === "openai") {
                suggestions.innerHTML = `
                    <option value="gpt-4o-mini">
                    <option value="gpt-4o">
                    <option value="gpt-3.5-turbo">
                `;
                if (!modelInput.value || modelInput.value === "llama3") {
                    modelInput.value = "gpt-4o-mini";
                }
                helpText.innerText = "OpenAI model name (e.g. gpt-4o-mini, gpt-4o). Requires OPENAI_API_KEY in .env";
            } else {
                suggestions.innerHTML = `
                    <option value="llama3">
                    <option value="mistral">
                    <option value="gemma2">
                    <option value="phi3">
                `;
                if (!modelInput.value || modelInput.value === "gpt-4o-mini") {
                    modelInput.value = "llama3";
                }
                helpText.innerText = "Ollama model installed on your machine (e.g. llama3, mistral)";
            }
        }

        // Run on page load to set correct helper
        document.addEventListener("DOMContentLoaded", function() {
            updateModelOptions();
        });
    </script>
@endsection
