<!DOCTYPE html>

<html lang="en" class="layout-navbar-fixed layout-compact layout-menu-fixed   " dir="ltr" data-skin="default"
    data-assets-path="" data-base-url="" data-framework="laravel" data-template="vertical-menu-template"
    data-bs-theme="dark">

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>
        Dashboard |
        {{ ($title = trim($__env->yieldContent('title'))) ? $title . ' - ' : '' }}{{ config('app.name', 'Live Chat') }}
    </title>
    <!-- Canonical SEO -->
    <link rel="canonical" href="{{ url()->current() }}" />
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/favicon/favicon.ico" />

    <!-- Include Styles -->
    <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
    <!-- BEGIN: Theme CSS-->
    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet">

    <link rel="preload" as="style" href="{{ asset('assets/css/app-chat-B_Ac8XJQ.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/app-chat-B_Ac8XJQ.css') }}" class="" />
    <!-- Fonts Icons -->
    <link rel="preload" as="style" href="{{ asset('assets/css/iconify-DDZnTNbY.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/iconify-DDZnTNbY.css') }}" class="" />
    <!-- BEGIN: Vendor CSS-->
    <link rel="preload" as="style" href="{{ asset('assets/css/pickr-themes-CFnMLNHJ.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/pickr-themes-CFnMLNHJ.css') }}" class="" />
    <!-- Core CSS -->
    <link rel="preload" as="style" href="{{ asset('assets/css/core-CsLdeUI9.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/core-CsLdeUI9.css') }}" class="template-customizer-core-css" />
    <link rel="preload" as="style" href="{{ asset('assets/css/demo-kASxDmGZ.css') }}" />
    <link rel="preload" as="style" href="{{ asset('assets/css/perfect-scrollbar-CfyPsj0y.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo-kASxDmGZ.css') }}" class="" />
    <link rel="stylesheet" href="{{ asset('assets/css/perfect-scrollbar-CfyPsj0y.css') }}" class="" />
    <!-- Vendor Styles -->
    <link rel="preload" as="style" href="{{ asset('assets/css/perfect-scrollbar-CfyPsj0y.css') }}" />
    <link rel="preload" as="style" href="{{ asset('assets/css/typeahead-CtVgDGc0.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/perfect-scrollbar-CfyPsj0y.css') }}" class="" />
    <link rel="stylesheet" href="{{ asset('assets/css/typeahead-CtVgDGc0.css') }}" class="" />
    <link rel="preload" as="style" href="{{ asset('assets/css/flag-icons-TUQATJgS.css') }}" />
    <link rel="preload" as="style" href="{{ asset('assets/css/apex-charts-DWhxKQx9.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/flag-icons-TUQATJgS.css') }}" class="" />
    <link rel="stylesheet" href="{{ asset('assets/css/apex-charts-DWhxKQx9.css') }}" class="" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <!-- Page Styles -->

    <link rel="preload" as="style" href="{{ asset('assets/css/template-customizer-CvTzP1B2.css') }}" />
    <link rel="modulepreload" href="{{ asset('assets/js/template-customizer-CZZ3zmqW.js') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/template-customizer-CvTzP1B2.css') }}" />
    <!-- app CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" class="" />
    <!-- END: app CSS-->
    <!-- Before closing </body> tag -->
    {{-- <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css"> --}}
    <!-- Select2 CSS -->
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" /> --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .chat-minimized-dock {
            position: fixed;
            z-index: 1080;
            right: 1.5rem;
            bottom: 0;
            display: flex;
            max-width: min(760px, calc(100vw - 2rem));
            gap: 0.5rem;
            overflow-x: auto;
        }

        .chat-dock-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            width: 220px;
            min-width: 170px;
            padding: 0.7rem 0.85rem;
            border: 1px solid var(--bs-border-color);
            border-bottom: 0;
            border-radius: 0.6rem 0.6rem 0 0;
            background: var(--bs-body-bg);
            box-shadow: 0 -0.2rem 0.8rem rgba(34, 48, 62, 0.12);
            color: var(--bs-body-color);
            cursor: pointer;
            text-align: left;
        }

        .chat-dock-tab-main {
            display: flex;
            flex: 1 1 auto;
            align-items: center;
            min-width: 0;
            gap: 0.65rem;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            cursor: pointer;
            text-align: left;
        }

        .chat-dock-tab-close {
            flex: 0 0 auto;
            width: 1.65rem;
            height: 1.65rem;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--bs-secondary-color);
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
        }

        .chat-dock-tab-close:hover {
            background: var(--bs-tertiary-bg);
            color: var(--bs-danger);
        }

        .chat-dock-tab:hover {
            background: var(--bs-tertiary-bg);
        }

        .chat-dock-tab-name {
            flex: 1;
            overflow: hidden;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chat-dock-unread {
            display: none;
            min-width: 1.2rem;
            padding: 0.1rem 0.35rem;
            border-radius: 1rem;
            background: var(--bs-danger);
            color: #fff;
            font-size: 0.7rem;
            text-align: center;
        }

        .chat-dock-toast {
            position: fixed;
            z-index: 1081;
            right: 1.5rem;
            bottom: 4rem;
            display: none;
            width: min(340px, calc(100vw - 2rem));
            padding: 1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.65rem;
            background: var(--bs-body-bg);
            box-shadow: 0 0.5rem 2rem rgba(34, 48, 62, 0.2);
            color: var(--bs-body-color);
            cursor: pointer;
        }

        .chat-dock-toast.show {
            display: block;
            animation: chat-dock-toast-in 0.2s ease-out;
        }

        .chat-dock-window {
            position: fixed;
            z-index: 1082;
            right: 1.5rem;
            bottom: 3.4rem;
            display: none;
            width: min(380px, calc(100vw - 2rem));
            height: min(520px, calc(100vh - 6rem));
            overflow: hidden;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem 0.75rem 0 0;
            background: var(--bs-body-bg);
            box-shadow: 0 0 2rem rgba(34, 48, 62, 0.25);
        }

        .chat-dock-window.show {
            display: flex;
            flex-direction: column;
        }

        .chat-dock-window-header {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: space-between;
            min-height: 48px;
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
        }

        .chat-dock-window-title {
            overflow: hidden;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chat-dock-window-actions {
            display: flex;
            gap: 0.35rem;
        }

        .chat-dock-error {
            margin: 0;
            padding: 0.45rem 0.8rem;
            background: var(--bs-danger-bg-subtle);
            color: var(--bs-danger-text-emphasis);
            font-size: 0.8rem;
        }

        .chat-dock-window-action {
            width: 1.8rem;
            height: 1.8rem;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--bs-secondary-color);
            cursor: pointer;
        }

        .chat-dock-window-action:hover {
            background: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
        }

        .chat-dock-window-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }

        .chat-dock-window-body .chat-history-wrapper {
            display: flex;
            height: 100%;
            flex-direction: column;
        }

        .chat-dock-window-body .chat-history-header {
            flex: 0 0 auto;
            padding: 0.7rem 0.9rem;
        }

        .chat-dock-window-body .chat-history-body {
            flex: 1 1 auto;
            min-height: 0;
            height: auto !important;
            padding: 0.85rem;
            overflow-y: auto !important;
        }

        .chat-dock-window-body .chat-history-footer {
            flex: 0 0 auto;
            margin: 0.5rem;
            padding: 0.35rem;
        }

        .chat-dock-window-body .chat-history-footer .form-send-message {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .chat-dock-window-body .chat-history-footer .message-input {
            min-width: 0;
        }

        .chat-dock-window-body .chat-history-footer .emoji-btn {
            display: none;
        }

        .chat-shortcut-tools {
            position: relative;
            margin-top: 0.5rem;
        }

        .visitor-country-flag {
            display: block;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .visitor-country-flag[hidden] {
            display: none !important;
        }

        .chat-shortcut-panel {
            position: absolute;
            z-index: 1082;
            right: 0;
            bottom: calc(100% + 0.5rem);
            width: min(360px, calc(100vw - 2rem));
            max-height: min(420px, calc(100vh - 8rem));
            overflow-y: auto;
            padding: 0.75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.65rem;
            background: var(--bs-body-bg);
            box-shadow: 0 0.5rem 1.5rem rgba(34, 48, 62, 0.2);
        }

        .chat-shortcut-list {
            display: grid;
            gap: 0.25rem;
            max-height: 180px;
            margin-top: 0.5rem;
            overflow-y: auto;
        }

        .chat-shortcut-option {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            width: 100%;
            padding: 0.5rem;
            border: 0;
            border-radius: 0.375rem;
            background: transparent;
            color: var(--bs-body-color);
            text-align: left;
        }

        .chat-shortcut-option:hover,
        .chat-shortcut-option:focus {
            background: var(--bs-tertiary-bg);
        }

        .chat-shortcut-option-description {
            display: block;
            overflow: hidden;
            color: var(--bs-secondary-color);
            font-size: 0.75rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @keyframes chat-dock-toast-in {
            from { opacity: 0; transform: translateY(0.5rem); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 575.98px) {
            .chat-minimized-dock {
                right: 0.5rem;
                max-width: calc(100vw - 1rem);
            }

            .chat-dock-tab {
                width: 170px;
            }

            .chat-dock-window {
                right: 0.5rem;
                bottom: 3.2rem;
                width: calc(100vw - 1rem);
                height: min(520px, calc(100vh - 5rem));
            }

            .chat-dock-toast {
                right: 0.5rem;
                bottom: 3.75rem;
            }
        }
    </style>
    @yield('css')
</head>

<body>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('admin.layout.sidebar')
            <div class="layout-page">
                @include('admin.layout.header')
                <div class="content-wrapper">
                    @yield('content')
                    @include('admin.layout.footer')
                </div>
            </div>
        </div>
    </div>

    <div class="chat-minimized-dock" id="chat-minimized-dock" aria-label="Minimized conversations"></div>
    <button type="button" class="chat-dock-toast" id="chat-dock-toast" aria-live="polite"></button>
    <section class="chat-dock-window" id="chat-dock-window" aria-label="Conversation">
        <header class="chat-dock-window-header">
            <div class="chat-dock-window-title" id="chat-dock-window-title"></div>
            <div class="chat-dock-window-actions">
                <button type="button" class="chat-dock-window-action" id="chat-dock-window-minimize" aria-label="Minimize conversation" title="Minimize">
                    <i class="bx bx-minus"></i>
                </button>
                <button type="button" class="chat-dock-window-action" id="chat-dock-window-close" aria-label="Close conversation" title="Remove from dock">
                    <i class="bx bx-x"></i>
                </button>
            </div>
        </header>
        <p class="chat-dock-error" id="chat-dock-error" role="alert" hidden></p>
        <div class="chat-dock-window-body" id="chat-dock-window-body"></div>
    </section>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <link rel="modulepreload" href="{{ asset('assets/js/helpers-0nSKkh37.js') }}" />
    <script type="module" src="{{ asset('assets/js/helpers-0nSKkh37.js') }}"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <link rel="modulepreload" href="{{ asset('assets/js/config-BoP0Nie5.js') }}" />
    <script type="module" src="{{ asset('assets/js/config-BoP0Nie5.js') }}"></script>
    {{-- <link rel="modulepreload" href="{{ asset('assets/js/jquery-Bou6iJJX.js') }}" /> --}}
    {{-- <link rel="modulepreload" href="{{ asset('assets/js/jquery-NjmgXMI-.js') }}" /> --}}
    <link rel="modulepreload" href="{{ asset('assets/js/_commonjsHelpers-D6-XlEtG.js') }}" />
    {{-- <link rel="modulepreload" href="{{ asset('assets/js/jquery-BQXThELV.js') }}" /> --}}
    <link rel="modulepreload" href="{{ asset('assets/js/popper-MwzM93Hw.js') }}" />
    <link rel="modulepreload" href="{{ asset('assets/js/bootstrap-D6PdghTj.js') }}" />
    <link rel="modulepreload" href="{{ asset('assets/js/autocomplete-js-BLyOkDc2.js') }}" />
    {{-- <script type="module" src="{{ asset('assets/js/jquery-Bou6iJJX.js') }}"></script> --}}
    <script type="module" src="{{ asset('assets/js/popper-MwzM93Hw.js') }}"></script>
    <script type="module" src="{{ asset('assets/js/bootstrap-D6PdghTj.js') }}"></script>
    <script type="module" src="{{ asset('assets/js/autocomplete-js-BLyOkDc2.js') }}"></script>
    <link rel="modulepreload" href="{{ asset('assets/js/pickr-71-TLRtn.js') }}" />
    <link rel="modulepreload" href="{{ asset('assets/js/_commonjsHelpers-D6-XlEtG.js') }}" />
    <script type="module" src="{{ asset('assets/js/pickr-71-TLRtn.js') }}"></script>
    <link rel="modulepreload" href="{{ asset('assets/js/perfect-scrollbar-D2XDwrzR.js') }}" />
    <link rel="modulepreload" href="{{ asset('assets/js/_commonjsHelpers-D6-XlEtG.js') }}" />
    <link rel="modulepreload" href="{{ asset('assets/js/hammer-DLEdXtvS.js') }}" />
    <link rel="modulepreload" href="{{ asset('assets/js/menu-Cc3Gq5JA.js') }}" />
    <script type="module" src="{{ asset('assets/js/perfect-scrollbar-D2XDwrzR.js') }}"></script>
    <script type="module" src="{{ asset('assets/js/hammer-DLEdXtvS.js') }}"></script>
    <script type="module" src="{{ asset('assets/js/menu-Cc3Gq5JA.js') }}"></script>
    {{-- <link rel="modulepreload" href="{{ asset('assets/js/main-CGh5h70G.js') }}" /><script type="module" src="{{ asset('assets/js/main-CGh5h70G.js') }}"></script> --}}
    <link rel="modulepreload" href="{{ asset('assets/js/app-chat-B0MYhsTQ.js') }}" />
    <script type="module" src="{{ asset('assets/js/app-chat-B0MYhsTQ.js') }}"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <link rel="modulepreload" href="{{ asset('assets/js/app-T1DpEqax.js') }}" />
    <script type="module" src="{{ asset('assets/js/app-T1DpEqax.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

    <script>
        const chatShortcutRoute = @json(route('admin.chat.shortcuts', ':id'));
        const shortcutLoadTimers = new WeakMap();

        function chatShortcutUrl(chatId) {
            return chatShortcutRoute.replace(':id', encodeURIComponent(String(chatId)));
        }

        function getSlashShortcut(input) {
            const cursor = input.selectionStart ?? input.value.length;
            const beforeCursor = input.value.slice(0, cursor);
            const match = beforeCursor.match(/(?:^|\s)\/([a-z0-9_-]*)$/i);
            if (!match) return null;

            return {
                query: match[1],
                start: match.index + match[0].lastIndexOf('/'),
                cursor
            };
        }

        function applyChatShortcut(input, shortcut) {
            const slash = getSlashShortcut(input);
            const start = slash ? slash.start : (input.selectionStart ?? input.value.length);
            const end = slash ? slash.cursor : (input.selectionEnd ?? input.value.length);
            input.value = input.value.slice(0, start) + shortcut.content + input.value.slice(end);
            const cursor = start + shortcut.content.length;
            input.focus();
            input.setSelectionRange(cursor, cursor);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }

        async function loadChatShortcuts(panel, chatId, query = '') {
            const list = panel.querySelector('.chat-shortcut-list');
            panel.dataset.query = query;
            list.textContent = 'Loading shortcuts...';
            try {
                const url = new URL(chatShortcutUrl(chatId), window.location.origin);
                if (query) url.searchParams.set('q', query);
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Could not load shortcuts.');
                if (panel.dataset.query !== query) return;

                list.replaceChildren();
                if (!result.shortcuts.length) {
                    const empty = document.createElement('div');
                    empty.className = 'small text-body-secondary p-2';
                    empty.textContent = query ? 'No matching shortcuts.' : 'No shortcuts yet.';
                    list.appendChild(empty);
                    return;
                }

                result.shortcuts.forEach(shortcut => {
                    const row = document.createElement('div');
                    row.className = 'd-flex align-items-center gap-1';
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'chat-shortcut-option flex-grow-1';
                    option.setAttribute('role', 'option');
                    const description = document.createElement('span');
                    const trigger = document.createElement('strong');
                    trigger.textContent = `/${shortcut.shortcut} · ${shortcut.title}`;
                    const preview = document.createElement('span');
                    preview.className = 'chat-shortcut-option-description';
                    preview.textContent = shortcut.content;
                    description.append(trigger, preview);
                    const scope = document.createElement('span');
                    scope.className = 'badge bg-label-secondary';
                    scope.textContent = shortcut.scope === 'team' ? 'Team' : 'Personal';
                    option.append(description, scope);
                    option.addEventListener('click', () => {
                        const input = panel.closest('.chat-history-footer').querySelector('.message-input');
                        applyChatShortcut(input, shortcut);
                        panel.hidden = true;
                        panel.closest('.chat-shortcut-tools').querySelector('.chat-shortcut-toggle').setAttribute('aria-expanded', 'false');
                    });
                    row.appendChild(option);

                    list.appendChild(row);
                });
            } catch (error) {
                list.textContent = error.message || 'Could not load shortcuts.';
            }
        }

        document.addEventListener('click', event => {
            if (!event.target.closest('.chat-shortcut-tools')) {
                document.querySelectorAll('.chat-shortcut-panel:not([hidden])').forEach(panel => {
                    panel.hidden = true;
                });
            }
        });

        document.addEventListener('input', event => {
            if (!event.target.matches('.message-input')) return;
            const input = event.target;
            const slash = getSlashShortcut(input);
            const panel = input.closest('.chat-history-footer').querySelector('.chat-shortcut-panel');
            if (!panel) return;
            clearTimeout(shortcutLoadTimers.get(input));

            if (!slash) {
                panel.hidden = true;
                return;
            }

            panel.hidden = false;
            const chatId = input.closest('.chat-history-footer').querySelector('.form-send-message').dataset.chatId;
            const timer = setTimeout(() => loadChatShortcuts(panel, chatId, slash.query), 120);
            shortcutLoadTimers.set(input, timer);
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && event.target.matches('.message-input')) {
                const panel = event.target.closest('.chat-history-footer')?.querySelector('.chat-shortcut-panel');
                if (panel) {
                    panel.hidden = true;
                }
                return;
            }
            if (event.key !== 'Enter' || event.shiftKey || !event.target.matches('.message-input')) return;

            const slash = getSlashShortcut(event.target);
            const panel = event.target.closest('.chat-history-footer').querySelector('.chat-shortcut-panel');
            if (!slash || !panel || panel.hidden) return;

            const firstOption = panel.querySelector('.chat-shortcut-option');
            if (!firstOption) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            firstOption.click();
        });
    </script>

    <script>
        Pusher.logToConsole = true;
        document.addEventListener('DOMContentLoaded', function() {

            const currentUserId = {{ auth()->id() }};
            const currentUserRole = {{ auth()->user()->role }};

            // 1️⃣ Hide badge if no unread notifications
            const badge = document.getElementById('notificationBadge');
            const badge2 = document.getElementById('notificationBadge2');
            if (parseInt(badge.innerText) === 0) {
                badge.classList.add('d-none');
                badge2.classList.add('d-none');
            }

            // 2️⃣ Mark all as read click
            document.querySelector('.dropdown-notifications-all').addEventListener('click', function() {
                fetch("{{ route('notifications.markRead') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            badge.classList.add('d-none');
                            badge2.classList.add('d-none');
                            document.querySelectorAll('#notificationList .badge-dot').forEach(el => el
                                .remove());
                        }
                    });
            });

            // 3️⃣ Pusher listener for new notifications
            var pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
                cluster: "{{ env('PUSHER_APP_CLUSTER') }}"
            });
            var channel = pusher.subscribe('admin-notifications');
            const dock = document.getElementById('chat-minimized-dock');
            const dockToast = document.getElementById('chat-dock-toast');
            const dockWindow = document.getElementById('chat-dock-window');
            const dockWindowTitle = document.getElementById('chat-dock-window-title');
            const dockWindowBody = document.getElementById('chat-dock-window-body');
            const dockWindowError = document.getElementById('chat-dock-error');
            const dockChats = new Map();
            const dockChannels = new Set();
            let toastTimeout;
            let toastChatId = null;
            const chatRoute = @json(route('admin.chat'));
            const chatShowRoute = @json(route('admin.chat.show', ':id'));
            const chatMessagesRoute = @json(route('admin.chat.messages', ':id'));
            const chatSendRoute = @json(route('admin.chat.send'));
            const dockStorageKey = 'minimizedChats-{{ auth()->id() }}';
            const notifySound = new Audio(@json(asset('assets/audio/notify.mp3')));
            let floatingChatId = null;
            let floatingRequestId = 0;

            function chatUrl(route, chatId) {
                return route.replace(':id', encodeURIComponent(String(chatId)));
            }

            function saveDockChats() {
                sessionStorage.setItem(dockStorageKey, JSON.stringify(Array.from(dockChats.values())));
            }

            function renderDock() {
                dock.replaceChildren();

                dockChats.forEach(chat => {
                    const tab = document.createElement('div');
                    tab.className = 'chat-dock-tab';
                    tab.dataset.chatId = chat.id;

                    const main = document.createElement('button');
                    main.type = 'button';
                    main.className = 'chat-dock-tab-main';

                    const icon = document.createElement('span');
                    icon.className = 'avatar avatar-xs';
                    icon.innerHTML = '<span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-message"></i></span>';

                    const name = document.createElement('span');
                    name.className = 'chat-dock-tab-name';
                    name.textContent = chat.name;

                    const unread = document.createElement('span');
                    unread.className = 'chat-dock-unread';
                    unread.textContent = chat.unread ? String(chat.unread) : '';
                    unread.style.display = chat.unread ? 'inline-block' : 'none';

                    main.append(icon, name, unread);

                    const close = document.createElement('button');
                    close.type = 'button';
                    close.className = 'chat-dock-tab-close';
                    close.dataset.chatId = chat.id;
                    close.setAttribute('aria-label', `Remove ${chat.name} from chat dock`);
                    close.title = 'Remove chat';
                    close.textContent = '×';

                    tab.append(main, close);
                    dock.appendChild(tab);
                });
            }

            function showChatToast(chatId, title, message) {
                toastChatId = String(chatId);
                dockToast.replaceChildren();

                const heading = document.createElement('strong');
                heading.className = 'd-block mb-1';
                heading.textContent = title;
                const preview = document.createElement('span');
                preview.textContent = message;
                dockToast.append(heading, preview);
                dockToast.classList.add('show');

                clearTimeout(toastTimeout);
                toastTimeout = setTimeout(() => dockToast.classList.remove('show'), 7000);
            }

            function openChatFromDock(chatId) {
                const chatPagePath = new URL(chatRoute, window.location.origin).pathname.replace(/\/+$/, '');
                if (window.location.pathname.replace(/\/+$/, '') === chatPagePath) {
                    window.dispatchEvent(new CustomEvent('chat-dock-open', {
                        detail: {
                            chatId: String(chatId),
                            name: dockChats.get(String(chatId))?.name
                        }
                    }));
                    return;
                }

                openFloatingChat(String(chatId));
            }

            function removeDockChat(chatId) {
                const key = String(chatId);
                dockChats.delete(key);
                saveDockChats();
                renderDock();

                if (floatingChatId === key) {
                    floatingChatId = null;
                    dockWindow.classList.remove('show');
                    dockWindowBody.replaceChildren();
                }

                if (dockChannels.has(key)) {
                    pusher.unsubscribe(`chat.${key}`);
                    dockChannels.delete(key);
                }
            }

            function renderFloatingMessage(message) {
                const history = dockWindowBody.querySelector('.chat-history');
                if (!history || Array.from(history.querySelectorAll('[data-message-id]')).some(item => item.dataset.messageId === String(message.id))) return;

                const role = message.is_ai ? 4 : (message.user && Number(message.user.role) === 3 ? 3 : (Number(message.role) === 3 ? 3 : 2));
                const text = document.createElement('li');
                text.className = role === 3 ? 'chat-message' : 'chat-message chat-message-right';
                text.dataset.messageId = message.id || '';
                const avatar = message.user?.image
                    ? new URL(String(message.user.image).replace(/^\/+/, ''), `${window.location.origin}/`).toString()
                    : @json(asset('assets/images/default.png'));
                const senderName = message.is_ai ? 'AI Assistant' : (message.user?.name || (role === 3 ? 'Visitor' : 'Agent'));
                const bubble = document.createElement('div');
                bubble.className = 'chat-message-wrapper flex-grow-1';
                const badge = document.createElement('div');
                badge.className = `${role === 3 ? 'text-start' : 'text-end'} mb-1`;
                const badgeText = document.createElement('span');
                badgeText.className = 'badge bg-label-secondary';
                badgeText.textContent = senderName;
                badge.appendChild(badgeText);
                const messageText = document.createElement('div');
                messageText.className = 'chat-message-text';
                const paragraph = document.createElement('p');
                paragraph.className = 'mb-0';
                messageText.appendChild(paragraph);
                const messageTime = document.createElement('div');
                messageTime.className = 'text-end text-body-secondary mt-1';
                const timeText = document.createElement('small');
                timeText.textContent = message.formatted_created_at || '';
                messageTime.appendChild(timeText);
                bubble.append(badge, messageText, messageTime);
                bubble.querySelector('.chat-message-text p').textContent = message.message || '';
                const row = document.createElement('div');
                row.className = 'd-flex overflow-hidden';
                if (role === 3) {
                    const image = document.createElement('img');
                    image.src = avatar;
                    image.alt = '';
                    image.className = 'rounded-circle';
                    const avatarWrap = document.createElement('div');
                    avatarWrap.className = 'avatar avatar-sm flex-shrink-0 me-3';
                    avatarWrap.appendChild(image);
                    row.append(avatarWrap, bubble);
                } else {
                    row.append(bubble);
                }
                text.appendChild(row);
                history.appendChild(text);
                const body = dockWindowBody.querySelector('.chat-history-body');
                if (body) body.scrollTop = body.scrollHeight;
            }

            function showFloatingError(message) {
                if (dockWindowBody.querySelector('.form-send-message')) {
                    dockWindowError.textContent = message;
                    dockWindowError.hidden = false;
                    return;
                }

                dockWindowBody.textContent = message;
            }

            async function sendFloatingMessage(form, chatId) {
                const input = form.querySelector('.message-input');
                const message = input.value.trim();
                if (!message || String(floatingChatId) !== String(chatId)) return;

                const sendButton = form.querySelector('.send-msg-btn');
                sendButton.disabled = true;
                try {
                    const response = await fetch(chatSendRoute, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ chat_id: chatId, message })
                    });
                    const sent = await response.json();
                    if (!response.ok) throw new Error(sent.message || sent.error || 'Message could not be sent.');
                    if (sent.status !== 'sent' || !sent.message) {
                        throw new Error('The server did not confirm that your message was sent.');
                    }

                    if (String(floatingChatId) === String(chatId) && dockWindow.classList.contains('show')) {
                        renderFloatingMessage(sent.message);
                    }
                    input.value = '';
                    dockWindowError.hidden = true;
                    dockWindowError.textContent = '';
                } catch (error) {
                    console.error('Unable to send chat message:', error);
                    showFloatingError(error.message || 'Message could not be sent.');
                } finally {
                    sendButton.disabled = false;
                }
            }

            async function openFloatingChat(chatId) {
                const chat = dockChats.get(String(chatId));
                if (!chat) return;

                floatingChatId = String(chatId);
                const requestId = ++floatingRequestId;
                dockWindowTitle.textContent = chat.name;
                dockWindowBody.textContent = 'Loading conversation...';
                dockWindowError.hidden = true;
                dockWindowError.textContent = '';
                dockWindow.classList.add('show');
                chat.unread = 0;
                saveDockChats();
                renderDock();

                try {
                    const partialResponse = await fetch(chatUrl(chatShowRoute, chatId), {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!partialResponse.ok) throw new Error(`Unable to load conversation (${partialResponse.status}).`);
                    const html = await partialResponse.text();
                    if (requestId !== floatingRequestId) return;

                    const fragment = document.createElement('div');
                    fragment.innerHTML = html;
                    const wrapper = fragment.querySelector('.chat-history-wrapper');
                    if (!wrapper) throw new Error('Conversation content was not returned.');
                    dockWindowBody.replaceChildren(wrapper);

                    const messagesResponse = await fetch(`${chatUrl(chatMessagesRoute, chatId)}?limit=50&offset=0`, {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!messagesResponse.ok) throw new Error(`Unable to load messages (${messagesResponse.status}).`);
                    const result = await messagesResponse.json();
                    if (requestId !== floatingRequestId) return;

                    const history = dockWindowBody.querySelector('.chat-history');
                    const emptyState = dockWindowBody.querySelector('#no-message');
                    if (result.data?.length) {
                        emptyState?.remove();
                        result.data.forEach(renderFloatingMessage);
                    }
                    dockWindowBody.querySelector('.form-send-message')?.addEventListener('submit', async event => {
                        event.preventDefault();
                        sendFloatingMessage(event.currentTarget, chatId);
                    });
                    dockWindowBody.querySelector('.send-msg-btn')?.addEventListener('click', () => {
                        dockWindowBody.querySelector('.form-send-message')?.requestSubmit();
                    });
                    dockWindowBody.querySelector('.message-input')?.addEventListener('keydown', event => {
                        if (event.key === 'Enter' && !event.shiftKey) {
                            event.preventDefault();
                            dockWindowBody.querySelector('.form-send-message')?.requestSubmit();
                        }
                    });
                    const body = dockWindowBody.querySelector('.chat-history-body');
                    if (body) body.scrollTop = body.scrollHeight;
                    if (!history) throw new Error('Conversation history could not be initialized.');
                } catch (error) {
                    if (requestId !== floatingRequestId) return;
                    console.error('Unable to open docked chat:', error);
                    showFloatingError(error.message || 'Unable to open this conversation.');
                }
            }

            function subscribeToDockChat(chat) {
                const key = String(chat.id);
                if (dockChannels.has(key)) return;

                dockChannels.add(key);
                pusher.subscribe(`chat.${key}`).bind('new-message', data => {
                    const isVisitorMessage = Number(data.role) === 3 || data.sender_type === 'visitor';
                    const chatPagePath = new URL(chatRoute, window.location.origin).pathname.replace(/\/+$/, '');
                    const isChatPage = window.location.pathname.replace(/\/+$/, '') === chatPagePath;

                    if (floatingChatId === key && dockWindow.classList.contains('show')) {
                        renderFloatingMessage(data);
                        if (isVisitorMessage) {
                            const openedChat = dockChats.get(key);
                            if (openedChat) {
                                openedChat.unread = 0;
                                openedChat.preview = data.message || 'New message';
                                openedChat.lastMessageId = String(data.id || '');
                                saveDockChats();
                                renderDock();
                            }
                        }
                        return;
                    }

                    if (!isVisitorMessage) return;

                    const currentChatId = new URLSearchParams(window.location.search).get('chatId');
                    const isViewingChat = isChatPage && currentChatId === key;
                    if (isViewingChat) return;

                    const current = dockChats.get(key) || chat;
                    if (data.id && current.lastMessageId === String(data.id)) return;
                    current.unread = (Number(current.unread) || 0) + 1;
                    current.preview = data.message || 'New message';
                    current.lastMessageId = String(data.id || '');
                    dockChats.set(key, current);
                    saveDockChats();
                    renderDock();

                    notifySound.currentTime = 0;
                    notifySound.play().catch(() => {});

                    if (!isChatPage) {
                        openFloatingChat(key);
                        return;
                    }

                    showChatToast(key, current.name, current.preview);
                });
            }

            function registerDockChat(chat, openOnChatPage = false) {
                const normalized = {
                    id: String(chat.id),
                    name: chat.name || `Visitor-${chat.visitorId || chat.id}`,
                    unread: Number(chat.unread) || 0,
                    preview: chat.preview || '',
                    lastMessageId: chat.lastMessageId || ''
                };
                const existing = dockChats.get(normalized.id);
                dockChats.set(normalized.id, {
                    ...existing,
                    ...normalized,
                    unread: chat.unread === undefined ? (Number(existing?.unread) || 0) : normalized.unread,
                    preview: chat.preview === undefined ? (existing?.preview || '') : normalized.preview,
                    lastMessageId: normalized.lastMessageId || existing?.lastMessageId || ''
                });
                saveDockChats();
                renderDock();
                subscribeToDockChat(dockChats.get(normalized.id));

                if (openOnChatPage) {
                    openChatFromDock(normalized.id);
                }
            }

            function handleVisitorMessageNotification(data) {
                const authorized = currentUserRole === 1
                    || (Array.isArray(data.user_ids) && data.user_ids.some(id => String(id) === String(currentUserId)));
                if (!authorized) return;

                const chatId = data.chat?.id;
                if (!chatId) {
                    console.error('Visitor message notification did not include a chat ID.');
                    return;
                }

                const key = String(chatId);
                const messageId = String(data.message?.id || '');
                const existing = dockChats.get(key);
                if (messageId && existing?.lastMessageId === messageId) return;

                const isChatPage = window.location.pathname.replace(/\/+$/, '')
                    === new URL(chatRoute, window.location.origin).pathname.replace(/\/+$/, '');
                const isViewingChat = isChatPage
                    && new URLSearchParams(window.location.search).get('chatId') === key;
                const preview = data.message?.message || 'New message';

                registerDockChat({
                    id: key,
                    visitorId: data.visitor?.id,
                    name: existing?.name || `Visitor-${data.visitor?.id || key}`,
                    unread: isViewingChat ? 0 : (Number(existing?.unread) || 0) + 1,
                    preview,
                    lastMessageId: messageId
                });

                if (isViewingChat) return;

                notifySound.currentTime = 0;
                notifySound.play().catch(() => {});

                if (!isChatPage) {
                    openFloatingChat(key);
                    return;
                }

                showChatToast(key, dockChats.get(key)?.name || 'New visitor message', preview);
            }

            try {
                const savedChats = JSON.parse(sessionStorage.getItem(dockStorageKey) || '[]');
                savedChats.forEach(chat => registerDockChat(chat));
            } catch (error) {
                console.error('Unable to restore minimized chats:', error);
                sessionStorage.removeItem(dockStorageKey);
            }

            window.addEventListener('chat-dock-register', event => {
                registerDockChat(event.detail);
            });

            window.addEventListener('chat-dock-read', event => {
                const chat = dockChats.get(String(event.detail?.chatId));
                if (!chat) return;
                chat.unread = 0;
                saveDockChats();
                renderDock();
            });

            dock.addEventListener('click', event => {
                const closeButton = event.target.closest('.chat-dock-tab-close');
                if (closeButton) {
                    event.stopPropagation();
                    removeDockChat(closeButton.dataset.chatId);
                    return;
                }

                const mainButton = event.target.closest('.chat-dock-tab-main');
                if (mainButton) openChatFromDock(mainButton.closest('.chat-dock-tab').dataset.chatId);
            });

            document.getElementById('chat-dock-window-minimize').addEventListener('click', () => {
                dockWindow.classList.remove('show');
            });

            document.getElementById('chat-dock-window-close').addEventListener('click', () => {
                if (floatingChatId) removeDockChat(floatingChatId);
            });

            dockToast.addEventListener('click', () => {
                if (toastChatId) openChatFromDock(toastChatId);
            });

            channel.bind('visitor-message', handleVisitorMessageNotification);

            channel.bind('visitor-joined', function(data) {

                const isAuthorizedUser = currentUserRole === 1
                    || (data.user_ids && data.user_ids.includes(currentUserId));

                if (!isAuthorizedUser) {
                    return;
                }

                const chatId = data.chat?.id;
                if (!chatId) {
                    console.error('Visitor notification did not include a chat ID.');
                    return;
                }

                const isChatPage = window.location.pathname.replace(/\/+$/, '') === new URL(chatRoute, window.location.origin).pathname.replace(/\/+$/, '');
                const visitorName = `Visitor-${data.visitor.id}`;
                registerDockChat({
                    id: chatId,
                    visitorId: data.visitor.id,
                    name: visitorName
                }, isChatPage);

                if (!isChatPage) {
                    showChatToast(chatId, 'New visitor', `${data.brand.domain} just connected.`);
                }

                // Append to dropdown notification
                const html = `
                    <li class="list-group-item dropdown-notifications-item">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-sm">
                                    <span class="avatar-initial rounded-circle bg-label-info"><i class="bx bx-user-check"></i></span>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="small mb-1 fw-semibold">New Visitor</h6>
                                <small class="d-block text-body mb-1">${data.brand.domain} just visited.</small>
                                <small class="text-muted"><i class="bx bx-time-five me-1"></i>Just now</small>
                            </div>
                            <div class="flex-shrink-0 dropdown-notifications-actions">
                                <span class="badge badge-dot bg-primary"></span>
                            </div>
                        </div>
                    </li>
                `;
                document.getElementById('notificationList').insertAdjacentHTML('afterbegin', html);
                document.getElementById('notificationBadge').classList.remove('d-none');
                document.getElementById('notificationBadge2').classList.remove('d-none');
            });

            var brand_approval_notifications = document.querySelectorAll('.brand-approval-notification');
            brand_approval_notifications.forEach(notification => {
                notification.addEventListener('click', function() {
                    const notificationUrl = this.getAttribute('data-notification-url');
                    window.location.href = notificationUrl;
                });
            });
        });
    </script>

    @yield('js')

    {{-- <script type="module">
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize template customizer after DOM is loaded
            if (window.TemplateCustomizer) {
                try {
                    // Get the skin currently applied to the document
                    const appliedSkin = document.documentElement.getAttribute('data-skin') || "default";

                    window.templateCustomizer = new TemplateCustomizer({
                        defaultTextDir: "ltr",
                        defaultTheme: "light",
                        defaultSkin: appliedSkin,
                        defaultSemiDark: false,
                        defaultShowDropdownOnHover: "1",
                        displayCustomizer: "1",
                        lang: 'en',
                        'controls': ["color", "theme", "skins", "semiDark", "layoutCollapsed",
                            "layoutNavbarOptions", "headerType", "contentLayout", "rtl"
                        ],
                    });

                    // Ensure color is applied on page load
                } catch (error) {
                    console.warn('Template customizer initialization error:', error);
                }
            }
        });
    </script> --}}

    <script>
        $(document).ready(function() {
            @if(Session::has('success'))
                toastr.success('{{ session("success") }}', 'Success!', {
                    "timeOut": "3000",
                    "positionClass": "toast-top-center"
                });
            @endif

            @if(session()->has('error'))
                @foreach((array) session('error') as $error)
                    toastr.error('{{ $error }}', 'Error!', {
                        "timeOut": "3000",
                        "positionClass": "toast-top-center"
                    });
                @endforeach
            @endif
        });
    </script>

</body>

</html>
