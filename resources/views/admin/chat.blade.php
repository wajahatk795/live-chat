@extends('admin.layout.app')

@section('css')
<style>
    .zendesk-chat-layout {
        width: 100%;
        max-width: 100%;
        height: calc(100vh - 11.3rem);
        min-height: 520px;
    }

    .zendesk-chat-layout > .row {
        display: flex;
        height: 100%;
        flex-wrap: nowrap;
        align-items: stretch;
        min-width: 0;
    }

    .zendesk-chat-layout > .row > .app-chat-contacts {
        position: relative;
        display: flex !important;
        flex: 0 0 21rem !important;
        width: 21rem !important;
        min-width: 21rem;
        max-width: none;
        box-sizing: border-box;
        height: 100%;
        flex-direction: column;
        inset: auto;
    }

    .zendesk-chat-layout .app-chat-contacts .sidebar-header {
        flex: 0 0 auto;
    }

    .zendesk-chat-layout .app-chat-contacts .sidebar-body {
        height: auto !important;
        min-height: 0;
        flex: 1 1 auto;
    }

    .zendesk-chat-layout > .row > .chat-main-panel {
        flex: 1 1 0 !important;
        width: 0;
        min-width: 0;
        max-width: none;
    }

    .zendesk-chat-layout > .row > .app-chat-sidebar-right {
        position: relative !important;
        inset: auto !important;
        display: flex !important;
        flex: 0 0 19rem !important;
        width: 19rem !important;
        min-width: 19rem;
        max-width: none;
        box-sizing: border-box;
        height: 100%;
        flex-direction: column;
        transform: none !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto;
        border-left: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
    }

    .zendesk-chat-layout .app-chat-sidebar-right .sidebar-header {
        position: relative;
        flex: 0 0 auto;
        padding: 1rem !important;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .zendesk-chat-layout .app-chat-sidebar-right .sidebar-body {
        flex: 1 1 auto;
        min-height: 0;
        height: auto !important;
        padding: 0.9rem !important;
        overflow-x: hidden;
        overflow-y: auto;
    }

    .zendesk-chat-layout .app-chat-sidebar-right dd {
        overflow-wrap: anywhere;
    }

    .chat-profile-input {
        margin-bottom: 0.55rem;
    }

    .chat-profile-section {
        padding: 0.85rem 0;
        border-top: 1px solid var(--bs-border-color);
    }

    .chat-profile-stat {
        min-width: 0;
        padding: 0.6rem 0.35rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.4rem;
        text-align: center;
    }

    .chat-profile-stat strong,
    .chat-profile-stat span {
        display: block;
    }

    .chat-profile-stat span {
        color: var(--bs-secondary-color);
        font-size: 0.72rem;
    }

    .chat-profile-path {
        overflow-wrap: anywhere;
    }

    .chat-profile-tag {
        margin: 0 0.25rem 0.25rem 0;
    }

    .visitor-country-flag-fallback {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
            width: 32px;
            height: 32px;
            object-fit: cover;
    }

    .chat-session-navigation {
        position: relative;
        display: none;
        flex: 0 0 auto;
        align-items: center;
        gap: 0.5rem;
        min-height: 48px;
        padding: 0.45rem 0.85rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .chat-session-navigation.show {
        display: flex;
    }

    .chat-session-navigation .past-chat-list {
        position: absolute;
        z-index: 5;
        top: 100%;
        left: 0.75rem;
        display: none;
        width: min(350px, calc(100% - 1.5rem));
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        background: var(--bs-body-bg);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.18);
    }

    .chat-session-navigation .past-chat-list.show {
        display: block;
    }

    .chat-session-navigation .past-chat-list button {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .chat-main-panel {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-width: 0;
        min-height: 0;
        padding: 0;
    }

    .chat-main-panel > #app-chat-conversation,
    .chat-main-panel > #app-chat-history {
        flex: 1 1 auto;
        width: 100%;
        min-width: 0;
        min-height: 0;
        height: auto;
        block-size: auto !important;
    }

    .chat-main-panel > #app-chat-history:not(.d-none) {
        display: flex !important;
        flex-direction: column;
        overflow: hidden;
    }

    .chat-main-panel .chat-history-wrapper {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
    }

    .chat-main-panel .chat-history-footer {
        box-sizing: border-box;
        flex: 0 0 auto;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0.75rem 1rem !important;
        overflow: visible;
    }

    .chat-main-panel .chat-history-footer .form-send-message {
        display: flex;
        justify-content: flex-start !important;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        min-width: 0;
        margin: 0;
        box-sizing: border-box;
    }

    .chat-main-panel .chat-history-footer .message-input {
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
        margin-right: 0 !important;
    }

    .chat-main-panel .chat-history-footer .message-actions {
        flex: 0 0 auto;
        gap: 0.375rem;
        margin-left: auto;
        margin-right: 0.5rem;
    }

    .chat-main-panel .chat-history-footer .send-message-status:empty {
        display: none;
    }

    .chat-main-panel .emoji-picker__wrapper {
        z-index: 1060 !important;
    }

    .chat-history-body {
        flex: 1 1 auto;
        min-height: 0;
        height: auto !important;
        block-size: auto !important;
        overflow-y: auto !important;
        scroll-behavior: smooth !important;
    }

    @media (max-width: 991.98px) {
        .zendesk-chat-layout {
            height: calc(100vh - 9rem);
            min-height: 420px;
        }

        .zendesk-chat-layout .app-chat-contacts {
            flex-basis: 14rem !important;
            width: 14rem !important;
            min-width: 14rem;
        }

        .zendesk-chat-layout > .row > .app-chat-sidebar-right {
            flex-basis: 17rem !important;
            width: 17rem !important;
            min-width: 15rem;
        }
    }

    @media (max-width: 767.98px) {
        .zendesk-chat-layout {
            height: auto;
            min-height: calc(100vh - 9rem);
        }

        .zendesk-chat-layout > .row {
            height: auto;
            flex-direction: column;
            flex-wrap: nowrap;
        }

        .zendesk-chat-layout > .row > .app-chat-contacts {
            flex: 0 0 15rem !important;
            width: 100% !important;
            min-width: 0;
            height: 15rem;
        }

        .zendesk-chat-layout > .row > .chat-main-panel {
            flex: 0 0 auto !important;
            width: 100%;
            height: 30rem;
            min-height: 55vh;
        }

        .zendesk-chat-layout > .row > .app-chat-sidebar-right {
            display: flex !important;
            flex: 0 0 auto !important;
            width: 100% !important;
            min-width: 0;
            height: auto;
            min-height: 24rem;
        }
    }

    .zendesk-chat-layout:not(.has-active-chat) > .row > .app-chat-sidebar-right {
        display: none !important;
        visibility: hidden !important;
        width: 0 !important;
        min-width: 0 !important;
        flex: 0 0 0 !important;
        border: none !important;
    }
    .zendesk-chat-layout:not(.has-active-chat) #app-chat-sidebar-right {
        display: none !important;
    }

    .chat-system-message {
        justify-content: center !important;
    }

    .app-chat .app-chat-history .chat-history-body .chat-history .chat-message:not(:last-child) {
        margin-bottom: 1rem !important;
    }

</style>
@endsection

@section('content')
    <!-- Content wrapper -->
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="app-chat card overflow-hidden zendesk-chat-layout">
            <div class="row g-0">
                <!-- Sidebar Left -->
                <div class="col app-chat-sidebar-left app-sidebar overflow-hidden" id="app-chat-sidebar-left">
                    <div
                        class="chat-sidebar-left-user sidebar-header d-flex flex-column justify-content-center align-items-center flex-wrap px-6 pt-12">
                        <div class="avatar avatar-xl avatar-online chat-sidebar-avatar">
                            <img src="{{ auth()->user()->image ? asset(auth()->user()->image) : asset('assets/images/default.png')  }}"
                                alt="Avatar" class="rounded-circle">
                        </div>
                        <h5 class="mt-4 mb-0">{{ auth()->user()->name ?? 'Guest' }}</h5>
                        <span>{{ auth()->user()->isRole() ?? 'Unknown Role' }}</span>
                        <i class="icon-base bx bx-x icon-lg cursor-pointer close-sidebar" data-bs-toggle="sidebar"
                            data-overlay="" data-target="#app-chat-sidebar-left"></i>
                    </div>
                    <div class="sidebar-body px-6 pb-6 ps">
                        <div class="my-6">
                            <div class="maxLength-wrapper">
                                <label for="chat-sidebar-left-user-about"
                                    class="text-uppercase text-body-secondary mb-1">About</label>
                                <textarea id="chat-sidebar-left-user-about"
                                    class="form-control chat-sidebar-left-user-about maxLength-example" rows="3"
                                    maxlength="120">Hey there, we’re just writing to let you know that you’ve been subscribed to a repository on GitHub.</textarea>
                                <small id="textarea-maxlength-info" class="maxLength label-success">100/120</small>
                            </div>
                        </div>
                        <div class="my-6">
                            <p class="text-uppercase text-body-secondary mb-1">Status</p>
                            <div class="d-grid gap-2 pt-2 text-heading ms-2">
                                <div class="form-check form-check-success">
                                    <input name="chat-user-status" class="form-check-input" type="radio" value="active"
                                        id="user-active" checked="">
                                    <label class="form-check-label" for="user-active">Online</label>
                                </div>
                                <div class="form-check form-check-warning">
                                    <input name="chat-user-status" class="form-check-input" type="radio" value="away"
                                        id="user-away">
                                    <label class="form-check-label" for="user-away">Away</label>
                                </div>
                                <div class="form-check form-check-danger">
                                    <input name="chat-user-status" class="form-check-input" type="radio" value="busy"
                                        id="user-busy">
                                    <label class="form-check-label" for="user-busy">Do not Disturb</label>
                                </div>
                                <div class="form-check form-check-secondary">
                                    <input name="chat-user-status" class="form-check-input" type="radio" value="offline"
                                        id="user-offline">
                                    <label class="form-check-label" for="user-offline">Offline</label>
                                </div>
                            </div>
                        </div>
                        <div class="my-6">
                            <p class="text-uppercase text-body-secondary mb-1">Settings</p>
                            <ul class="list-unstyled d-grid gap-4 ms-2 pt-2 text-heading">
                                <li class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="icon-base bx bx-lock-alt me-1"></i>
                                        <span class="align-middle">Two-step Verification</span>
                                    </div>
                                    <div class="form-check form-switch mb-0 me-1">
                                        <input type="checkbox" class="form-check-input" checked="">
                                    </div>
                                </li>
                                <li class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="icon-base bx bx-bell me-1"></i>
                                        <span class="align-middle">Notification</span>
                                    </div>
                                    <div class="form-check form-switch mb-0 me-1">
                                        <input type="checkbox" class="form-check-input">
                                    </div>
                                </li>
                                <li>
                                    <i class="icon-base bx bx-user me-1"></i>
                                    <span class="align-middle">Invite Friends</span>
                                </li>
                                <li>
                                    <i class="icon-base bx bx-trash me-1"></i>
                                    <span class="align-middle">Delete Account</span>
                                </li>
                            </ul>
                        </div>
                        <div class="d-flex mt-6">
                            <button class="btn btn-primary w-100" data-bs-toggle="sidebar" data-overlay=""
                                data-target="#app-chat-sidebar-left"><i
                                    class="icon-base bx bx-log-out icon-sm me-2"></i>Logout</button>
                        </div>
                        <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
                            <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
                        </div>
                        <div class="ps__rail-y" style="top: 0px; right: 0px;">
                            <div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 0px;"></div>
                        </div>
                    </div>
                </div>
                <!-- /Sidebar Left-->
                <!-- Chat & Contacts -->
                <div class="col app-chat-contacts app-sidebar flex-grow-0 overflow-hidden border-end"
                    id="app-chat-contacts">
                    <div class="sidebar-header px-6 border-bottom d-flex align-items-center">
                        <div class="d-flex align-items-center me-6 me-lg-0">
                            <div class="flex-shrink-0 avatar avatar-online me-4 profile-av-2" data-bs-toggle="sidebar"
                                data-overlay="app-overlay-ex" data-target="#app-chat-sidebar-right">
                                <img class="user-avatar rounded-circle cursor-pointer"
                                    src="{{ auth()->user()->image ? asset(auth()->user()->image) : asset('assets/images/default.png')  }}"
                                    alt="Avatar">
                            </div>
                            <div class="flex-grow-1 input-group input-group-merge rounded-pill">
                                <span class="input-group-text" id="basic-addon-search31"><i
                                        class="icon-base bx bx-search icon-sm"></i></span>
                                <input type="text" class="form-control chat-search-input" placeholder="Search..."
                                    aria-label="Search..." aria-describedby="basic-addon-search31">
                            </div>
                        </div>
                        <i class="icon-base bx bx-x icon-lg cursor-pointer position-absolute top-50 end-0 translate-middle d-lg-none d-block"
                            data-overlay="" data-bs-toggle="sidebar" data-target="#app-chat-contacts"></i>
                    </div>
                    <div class="sidebar-body ps ps--active-y">
                        <!-- Chats -->
                        @include('admin.chat._contacts')
                        <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
                            <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
                        </div>
                        <div class="ps__rail-y" style="top: 0px; height: 447px; right: 0px;">
                            <div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 198px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col chat-main-panel">
                    <div class="chat-session-navigation" id="chat-session-navigation">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="current-chat-tab">Current chat</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="past-chat-toggle">
                            Past chats <span class="badge bg-label-secondary ms-1" id="past-chat-count">0</span>
                        </button>
                        <div class="past-chat-list" id="past-chat-list"></div>
                    </div>
                    <!-- Chat conversation -->
                    <div class="app-chat-conversation d-flex align-items-center justify-content-center flex-column"
                        id="app-chat-conversation">
                        <div class="bg-label-primary p-8 rounded-circle">
                            <i class="icon-base bx bx-message-alt-detail icon-48px"></i>
                        </div>
                        <p class="my-4">Select a contact to start a conversation.</p>
                        <button class="btn btn-primary app-chat-conversation-btn" id="app-chat-conversation-btn">Select
                            Contact</button>
                    </div>
                    <!-- /Chat conversation -->
                    <!-- Chat History -->
                    <div class="app-chat-history d-none" id="app-chat-history"></div>
                </div>
                <!-- Sidebar Right -->
                <aside class="col app-chat-sidebar-right app-sidebar overflow-hidden {{ empty($chatId) ? 'd-none' : '' }}" id="app-chat-sidebar-right">
                    <div class="sidebar-header d-flex align-items-center">
                        <div class="avatar avatar-md flex-shrink-0 me-3">
                            <img src="{{ asset('assets/images/default.png') }}" alt="Visitor" class="rounded-circle" id="sidebar-avatar">
                            <img src="" alt="Visitor country flag" class="rounded-circle visitor-country-flag" id="sidebar-avatar-flag" hidden>
                        </div>
                        <div class="overflow-hidden">
                            <h6 class="mb-0 text-truncate" id="sidebar-name">Visitor profile</h6>
                            <small class="text-body-secondary" id="sidebar-role">Visitor details</small>
                        </div>
                    </div>
                    <div class="sidebar-body ps">
                        <section class="chat-profile-section" aria-label="Visitor activity">
                            <div class="d-flex gap-2">
                                <div class="chat-profile-stat flex-fill">
                                    <strong id="profile-session-duration">-</strong><span>Time on site</span>
                                </div>
                            </div>
                        </section>

                        <section class="chat-profile-section">
                            <h6 class="small text-uppercase text-body-secondary mb-2">Visitor path</h6>
                            <a class="small chat-profile-path" id="profile-visitor-path" href="#" target="_blank" rel="noopener noreferrer">No page recorded</a>
                        </section>

                        <section class="chat-profile-section">
                            <h6 class="small text-uppercase text-body-secondary mb-2">Past chats</h6>
                            <div class="chat-profile-stat">
                                <strong id="profile-past-chat-count">0</strong><span>Past messages</span>
                            </div>
                        </section>

                        <section class="chat-profile-section">
                            <h6 class="small text-uppercase text-body-secondary mb-2">Location & device</h6>
                            <dl class="small mb-0">
                                <dt>Session started</dt><dd id="profile-session-started">-</dd>
                                <dt>Source</dt><dd id="profile-source">-</dd>
                                <dt>Keyword</dt><dd id="profile-keyword">-</dd>
                                <dt>Location</dt><dd id="profile-location">-</dd>
                                <dt>Browser</dt><dd id="profile-browser">-</dd>
                                <dt>Platform</dt><dd id="profile-os">-</dd>
                                <dt>Device</dt><dd id="profile-device">-</dd>
                                <dt>IP address</dt><dd id="profile-ip-address">-</dd>
                            </dl>
                        </section>

                        <form id="visitor-profile-form" class="chat-profile-section">
                            <input type="hidden" id="profile-visitor-id">
                            <label class="form-label small mb-1" for="profile-name">Name</label>
                            <input class="form-control form-control-sm chat-profile-input" id="profile-name" name="name" maxlength="255" placeholder="Add name" required @if((int) auth()->user()->role === 1) readonly @endif>
                            <label class="form-label small mb-1" for="profile-email">Email</label>
                            <input class="form-control form-control-sm chat-profile-input" id="profile-email" name="email" type="email" maxlength="255" placeholder="Add email" @if((int) auth()->user()->role === 1) readonly @endif>
                            <label class="form-label small mb-1" for="profile-phone">Phone</label>
                            <input class="form-control form-control-sm chat-profile-input" id="profile-phone" name="phone" maxlength="30" placeholder="Add phone number" @if((int) auth()->user()->role === 1) readonly @endif>
                            <label class="form-label small mb-1" for="profile-notes">Visitor notes</label>
                            <textarea class="form-control form-control-sm chat-profile-input" id="profile-notes" name="notes" rows="3" maxlength="5000" placeholder="Add visitor notes" @if((int) auth()->user()->role === 1) readonly @endif></textarea>
                            @if((int) auth()->user()->role === 2)
                                <button class="btn btn-sm btn-primary w-100" type="submit" id="profile-save-button">Save details</button>
                            @endif
                            <div class="small mt-2" id="profile-save-status" role="status"></div>
                        </form>
                    </div>
                </aside>
                <!-- /Sidebar Right -->
                <div class="app-overlay"></div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script type="module">
    import { EmojiButton } from 'https://cdn.skypack.dev/@joeattardi/emoji-button';

    const picker = new EmojiButton({
        position: 'top-start',
        theme: 'auto'
    });

    let activeInput = null;
    let activeEmojiButton = null;

    function positionEmojiPicker(button) {
        const panel = document.querySelector('.chat-main-panel');
        const pickerElement = document.querySelector('.emoji-picker__wrapper');
        if (!panel || !pickerElement) return;

        const panelBounds = panel.getBoundingClientRect();
        const buttonBounds = button.getBoundingClientRect();
        const padding = 12;
        pickerElement.style.position = 'fixed';
        pickerElement.style.transform = 'none';
        pickerElement.style.maxWidth = `${Math.max(0, panelBounds.width - padding * 2)}px`;
        pickerElement.style.maxHeight = `${Math.max(0, panelBounds.height - padding * 2)}px`;
        pickerElement.style.overflowY = 'auto';
        pickerElement.style.zIndex = '1060';

        const pickerBounds = pickerElement.getBoundingClientRect();
        const maxLeft = Math.max(panelBounds.left + padding, panelBounds.right - pickerBounds.width - padding);
        const maxTop = Math.max(panelBounds.top + padding, panelBounds.bottom - pickerBounds.height - padding);
        const left = Math.min(Math.max(buttonBounds.left, panelBounds.left + padding), maxLeft);
        const top = Math.min(
            Math.max(buttonBounds.top - pickerBounds.height - padding, panelBounds.top + padding),
            maxTop
        );

        pickerElement.style.left = `${left}px`;
        pickerElement.style.top = `${top}px`;
        pickerElement.style.right = 'auto';
        pickerElement.style.bottom = 'auto';
    }

    // 🔹 When emoji selected
    picker.on('emoji', selection => {
        if (!activeInput) return;

        const start = activeInput.selectionStart;
        const end = activeInput.selectionEnd;
        const text = activeInput.value;

        // Insert emoji at cursor position
        activeInput.value =
            text.slice(0, start) +
            selection.emoji +
            text.slice(end);

        // Move cursor after emoji
        const newPos = start + selection.emoji.length;
        activeInput.setSelectionRange(newPos, newPos);

        activeInput.focus();
    });

    // 🔹 Open picker on emoji button click
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.emoji-btn');
        if (!btn) return;

        // Find related message input
        const form = btn.closest('.form-send-message');
        if (!form) return;

        activeInput = form.querySelector('.message-input');
        if (!activeInput) return;

        activeEmojiButton = btn;
        picker.togglePicker(btn);
        requestAnimationFrame(() => positionEmojiPicker(btn));
    });

    window.addEventListener('resize', () => {
        if (activeEmojiButton) positionEmojiPicker(activeEmojiButton);
    });
</script>
<script>
let notifySound;

document.addEventListener('DOMContentLoaded', () => {
    notifySound = new Audio("{{ asset('assets/audio/notify.mp3') }}");
    notifySound.volume = 0.7;
});

document.addEventListener('click', function unlockAudio() {
    notifySound.play()
        .then(() => {
            notifySound.pause();
            notifySound.currentTime = 0;
            console.log('🔓 Audio unlocked');
            document.removeEventListener('click', unlockAudio);
        })
        .catch(err => console.error('Unlock failed', err));
});

Pusher.logToConsole = true;

const pusherChat = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', {
    cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
    forceTLS: true
});

let chatId = {{ $chatId ?? 'null' }};
let chatChannel = null;
const currentUserId = {{ auth()->id() }};
let currentPage = 1;
let lastPage = null;
let lastMessageId = null;
const openChatTabs = new Map();
const boundChatChannels = new Set();
const boundSidebarChannels = new Set();
let activeChatRequest = 0;
let messageRequestId = 0;
let currentInboxChatId = null;
let currentInboxVisitorId = null;
let profileRequestId = 0;
let isRefreshingChatContacts = false;
const profileRoute = "{{ route('admin.chat.profile', ':id') }}";
const profileUpdateRoute = "{{ route('admin.chat.profile.update', ':id') }}";
const chatContactsUrl = @json(route('admin.chat.contacts'));
const historyView = @json($historyView);
const currentUserRole = @json((int) auth()->user()->role);
const authorizedBrandIds = @json(auth()->user()->role == 1 ? [] : auth()->user()->auth_brands->pluck('id')->values());

function subscribeSidebarChat(element) {
    const id = element.dataset.chatId;
    if (!id || boundSidebarChannels.has(id)) return;

    boundSidebarChannels.add(id);
    pusherChat.subscribe(`chat.${id}`).bind('new-message', data => {
        if (data.role != 3) return;

        if (!isChatActive(id)) {
            const badge = getUnreadBadge(id);
            if (badge) {
                badge.innerText = parseInt(badge.innerText || 0) + 1;
                badge.style.display = 'inline-block';
            }
            notifySound.currentTime = 0;
            notifySound.play().catch(() => {});
        } else {
            markMessagesRead(id);
        }

        updateLastMessage(id, data.message);
    });
}

async function refreshChatContacts() {
    if (document.visibilityState === 'hidden' || isRefreshingChatContacts) return;

    const currentList = document.getElementById('chat-list');
    if (!currentList) return;

    const activeId = currentList.querySelector('.chat-contact-list-item.active .chat-user')?.dataset.chatId;
    const url = new URL(chatContactsUrl, window.location.origin);
    if (historyView) url.searchParams.set('view', 'history');

    isRefreshingChatContacts = true;
    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error(`Unable to refresh visitor list (${response.status}).`);

        const html = await response.text();
        const refreshedList = new DOMParser().parseFromString(html, 'text/html').getElementById('chat-list');
        if (!refreshedList) throw new Error('The refreshed visitor list was not returned.');

        if (activeId) {
            refreshedList.querySelector(`.chat-user[data-chat-id="${CSS.escape(activeId)}"]`)
                ?.closest('.chat-contact-list-item')
                ?.classList.add('active');
        }

        currentList.replaceWith(refreshedList);
        refreshedList.querySelectorAll('.chat-user').forEach(subscribeSidebarChat);
        bindVisitorAvatarFallbacks(refreshedList);
        bindCountryFlagFallbacks(refreshedList);
    } catch (error) {
        console.error('Unable to refresh visitor list:', error);
    } finally {
        isRefreshingChatContacts = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {

    pusherChat.subscribe('admin-notifications').bind('visitor-online', data => {
        const isAuthorized = currentUserRole === 1
            || authorizedBrandIds.map(Number).includes(Number(data.brand?.id));
        if (!isAuthorized) return;

        refreshChatContacts();
    });

    pusherChat.subscribe('admin-notifications').bind('visitor-offline', data => {
        const isAuthorized = currentUserRole === 1
            || authorizedBrandIds.map(Number).includes(Number(data.brand?.id));
        if (!isAuthorized) return;

        refreshChatContacts();
    });

    // Subscribe currently open chat
    if (chatId) {
        subscribeToChat(chatId);
    }

    // Subscribe sidebar chats globally for unread / last message / sound
    document.querySelectorAll('.chat-user').forEach(subscribeSidebarChat);
    bindVisitorAvatarFallbacks(document);
    bindCountryFlagFallbacks(document);
    setInterval(refreshChatContacts, 10000);
});

// ================= Active chat messages =================

function subscribeToChat(chatId) {
    chatChannel = pusherChat.subscribe(`chat.${chatId}`);
    const chatKey = String(chatId);
    if (boundChatChannels.has(chatKey)) return;
    boundChatChannels.add(chatKey);

    chatChannel.bind('new-message', data => {
        if (String(window.chatId) !== chatKey) return;
        if (messageAlreadyRendered(data.id)) return;

        const isAi = data.is_ai == 1 || data.is_ai === true || data.role == 4;
        const userName = isAi ? 'AI Assistant' : (data.user ? data.user.name : (data.role == 3 ? 'Visitor' : 'Agent'));

        appendMessage(data.message, data.role, data.user?.image, data.formatted_created_at, data.is_read, data.id, isAi, userName, data.user?.country_code || data.country_code);

        setTimeout(() => {
            const chatBody = document.querySelector('.chat-history-body');
            chatBody.scrollTop = chatBody.scrollHeight;
        }, 0);

        // Reset unread badge if any
        const badge = getUnreadBadge(chatId);
        if (badge) {
            badge.innerText = 0;
            badge.style.display = 'none';
        }
    });

    chatChannel.bind('messages-read', data => {
        if (String(window.chatId) === chatKey && data.sender_type === 'agent') {
            document.querySelectorAll('.message-ticks').forEach(el => {
                el.innerHTML = '<i class="bx bx-check-double text-primary"></i>';
            });
        }
    });

    chatChannel.bind('typing', data => {
        if (String(window.chatId) === chatKey && data.role == 3) {
            const typing = document.getElementById('typing-indicator');
            if (!typing) return;

            typing.style.display = 'block';

            clearTimeout(window.typingTimeout);
            window.typingTimeout = setTimeout(() => {
                typing.style.display = 'none';
            }, 1500);
        }
    });

    chatChannel.bind('activity', data => {
        const tab = openChatTabs.get(chatKey);
        if (data.chat_status === 'closed' && tab) {
            tab.status = 'closed';
        }

        if (data.chat_status === 'closed') {
            const el = document.querySelector(`.chat-user[data-chat-id="${data.chat_id}"]`);
            if (el) el.dataset.chatStatus = 'closed';
        }

        if (String(window.chatId) === chatKey) {
            const ul = document.querySelector('.chat-history');
            if (ul) ul.appendChild(renderSystemMessage(data.message));
            if (data.chat_status === 'closed') {
                disableChatForm('Chat closed due to inactivity');
            }
        }
    });

    chatChannel.bind('ai-processing', data => {
        if (String(window.chatId) !== chatKey) return;
        if (data.processing) {
            disableChatForm('⏳ AI is generating a response... Please wait.');
        } else {
            enableChatForm();
        }
    });

    chatChannel.bind('agent-switched', data => {
        if (String(window.chatId) !== chatKey) return;
        const ul = document.querySelector('.chat-history');
        ul.appendChild(renderSystemMessage(data.message));

        const chatBody = document.querySelector('.chat-history-body');
        if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;
    });
}

// ================= Helper functions =================

function getUnreadBadge(chatId) {
    return document.querySelector(
        `.chat-user[data-chat-id="${chatId}"] .unread-badge`
    );
}

function isChatActive(chatId) {
    return document
        .querySelector(`.chat-user[data-chat-id="${chatId}"]`)
        ?.closest('.chat-contact-list-item')
        ?.classList.contains('active');
}

function updateLastMessage(chatId, message) {
    const el = document.querySelector(
        `.chat-user[data-chat-id="${chatId}"] .last-message`
    );
    if (el) {
        el.innerText = message.length > 30
            ? message.substring(0, 30) + '...'
            : message;
    }
}

function markMessagesRead(chatId) {
    clearTimeout(readTimeout);

    readTimeout = setTimeout(() => {

        fetch("{{ route('admin.chat.markRead') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ chat_id: chatId })
        })
        .then(() => {
            // ✅ hide badge after DB update confirm
            const badge = getUnreadBadge(chatId);
            if (badge) {
                badge.innerText = 0;
                badge.style.display = 'none';
            }
        });

    }, 300);
}

let readTimeout = null;

function renderMessage(text, role, userAvatar = null, created_at = null, is_read = false, msgId = null, is_ai = false, userName = null, countryCode = null) {
    const li = document.createElement('li');

    li.className = role != 3
        ? 'chat-message chat-message-right'
        : 'chat-message';

    const defaultAvatar = "{{ asset('assets/images/default.png') }}";
    countryCode = String(countryCode || '').toLowerCase();
    const hasCountryFlag = role == 3 && /^[a-z]{2}$/.test(countryCode);
    const avatar = hasCountryFlag
        ? `https://flagcdn.com/w40/${countryCode}.png`
        : userAvatar || defaultAvatar;

    let senderBadge = '';
    if (is_ai || role == 4) {
        senderBadge = `<div class="mb-1 text-end"><span class="badge bg-label-info py-1 px-2 rounded-pill shadow-xs" style="font-size: 11px; font-weight: 600;"><i class="bx bx-bot me-1"></i> AI Assistant</span></div>`;
    } else if (role != 3) {
        senderBadge = `<div class="mb-1 text-end"><span class="badge bg-label-primary py-1 px-2 rounded-pill shadow-xs" style="font-size: 11px; font-weight: 600;"><i class="bx bx-user me-1"></i> ${escapeHtml(userName || 'Agent')}</span></div>`;
    } else {
        senderBadge = `<div class="mb-1 text-start"><span class="badge bg-label-secondary py-1 px-2 rounded-pill shadow-xs" style="font-size: 11px; font-weight: 600;"><i class="bx bx-user-voice me-1"></i> Visitor</span></div>`;
    }

    const aiBubbleStyle = (is_ai || role == 4)
        ? 'background: rgba(3, 195, 236, 0.09) !important; border: 1px solid rgba(3, 195, 236, 0.35) !important;'
        : '';

    li.innerHTML = `
        <div class="d-flex overflow-hidden">
            ${role == 3 ? `
            <div class="user-avatar flex-shrink-0 me-4">
                <div class="avatar avatar-sm">
                    <img src="${avatar}" class="rounded-circle${hasCountryFlag ? ' visitor-country-flag' : (role == 3 ? ' visitor-avatar-image' : '')}"${hasCountryFlag ? ` data-country-code="${countryCode}"` : ''}>
                </div>
            </div>` : ''}

            <div class="chat-message-wrapper flex-grow-1">
                ${senderBadge}
                <div class="chat-message-text" style="${aiBubbleStyle}">
                    <p class="mb-0">${escapeHtml(text)}</p>
                </div>
                <div class="text-end text-body-secondary mt-1 d-flex align-items-center justify-content-end">
                    <small class="me-1">${created_at || ''}</small>
                    ${role != 3 ? `
                        <span class="message-ticks" data-msg-id="${msgId}" style="font-size: 14px; line-height: 1;">
                            ${is_read ? '<i class="bx bx-check-double text-primary"></i>' : '<i class="bx bx-check text-muted"></i>'}
                        </span>
                    ` : ''}
                </div>
            </div>

            ${role != 3 ? `
            <div class="user-avatar flex-shrink-0 ms-4">
                <div class="avatar avatar-sm">
                    ${(is_ai || role == 4)
                        ? `<span class="avatar-initial rounded-circle bg-label-info"><i class="bx bx-bot"></i></span>`
                        : `<img src="${avatar}" class="rounded-circle">`
                    }
                </div>
            </div>` : ''}
        </div>
    `;

    bindVisitorAvatarFallbacks(li);
    bindCountryFlagFallbacks(li);
    return li;
}

function renderSystemMessage(text) {
    const li = document.createElement('li');
    li.className = 'chat-message chat-system-message';

    let badgeColor;
    if (text === "Visitor opened the chat") {
        badgeColor = 'bg-label-success';
    } else if (text === "Visitor closed the chat") {
        badgeColor = 'bg-label-danger';
    } else {
        badgeColor = 'bg-label-secondary';
    }

    const content = document.createElement('span');
    const navigationPrefix = 'Visitor navigated to: ';
    if (text.startsWith(navigationPrefix)) {
        content.append(document.createTextNode(navigationPrefix));
        const visitedUrl = text.slice(navigationPrefix.length);
        try {
            const parsedUrl = new URL(visitedUrl);
            if (['http:', 'https:'].includes(parsedUrl.protocol)) {
                const link = document.createElement('a');
                link.href = parsedUrl.toString();
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = visitedUrl;
                content.appendChild(link);
            } else {
                content.appendChild(document.createTextNode(visitedUrl));
            }
        } catch {
            content.appendChild(document.createTextNode(visitedUrl));
        }
    } else {
        content.textContent = text;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'd-flex justify-content-center my-2';
    const badge = document.createElement('span');
    badge.className = `badge ${badgeColor} px-3 py-1 rounded-pill text-muted`;
    badge.appendChild(content);
    wrapper.appendChild(badge);
    li.appendChild(wrapper);

    return li;
}

function appendMessage(text, role, userAvatar = null, created_at = null, is_read = false, msgId = null, is_ai = false, userName = null, countryCode = null) {
    const ul = document.querySelector('.chat-history');

    const noMessage = document.getElementById('no-message');
    if (noMessage) {
        noMessage.remove();
    }

    const li = renderMessage(text, role, userAvatar, created_at, is_read, msgId, is_ai, userName, countryCode);
    ul.appendChild(li);

    // ✅ Scroll chat container to bottom
    const chatBody = document.querySelector('.chat-history-body');
    chatBody.scrollTop = chatBody.scrollHeight;
}

function messageAlreadyRendered(messageId) {
    if (messageId == null) return false;
    return Array.from(document.querySelectorAll('.message-ticks[data-msg-id]'))
        .some(tick => tick.dataset.msgId === String(messageId));
}

let limit = 20;
let offset = 0;
let loading = false;
let allLoaded = false;

function loadMessages(chatId, prepend = false) {
    if (loading || allLoaded) return;
    loading = true;
    const requestedChatId = String(chatId);
    const requestId = ++messageRequestId;

    const container = document.querySelector('.chat-history-body');
    if (!container) {
        loading = false;
        return;
    }
    const ul = container.querySelector('.chat-history');
    const oldScrollHeight = container.scrollHeight;
    const oldScrollTop = container.scrollTop;

    const isPastHistory = document.getElementById('app-chat-history').dataset.readOnlyHistory === 'true';
    const messageParams = new URLSearchParams({
        limit: String(limit),
        offset: String(offset),
        prepend: prepend ? '1' : '0'
    });
    if (isPastHistory) messageParams.set('past', '1');
    fetch(`{{ route('admin.chat.messages', ':id') }}`.replace(':id', encodeURIComponent(requestedChatId)) + `?${messageParams}`)
        .then(res => res.json())
        .then(data => {
            if (String(window.chatId) !== requestedChatId) return;

            if (!data.count || !data.data.length) {
                allLoaded = true;
                return;
            }

            let messages = data.data;

            // No need to reverse, backend sends oldest first
            messages.forEach(msg => {
                if (!msg.user && !msg.is_ai) {
                    const li = renderSystemMessage(msg.message);
                    prepend ? ul.prepend(li) : ul.appendChild(li);
                    return;
                }

                const isAi = msg.is_ai == 1 || msg.is_ai === true;
                const role = isAi ? 4 : (msg.user && msg.user.role == 3 ? 3 : 1);
                const userName = isAi ? 'AI Assistant' : (msg.user ? msg.user.name : 'Agent');
                const avatar = msg.user ? msg.user.image : null;

                const li = renderMessage(msg.message, role, avatar, msg.formatted_created_at, msg.is_read, msg.id, isAi, userName, msg.user?.country_code);
                prepend ? ul.prepend(li) : ul.appendChild(li);
            });

            // Maintain scroll position
            if (prepend) {
                container.scrollTop = container.scrollHeight - oldScrollHeight + oldScrollTop;
            } else {
                container.scrollTop = container.scrollHeight; // scroll to bottom initially
            }

            offset += data.count;
            allLoaded = !data.has_more;
        })
        .finally(() => {
            if (requestId === messageRequestId) {
                loading = false;
            }
        });
}

document.getElementById('load-more-btn')
    ?.addEventListener('click', () => {
        loadMessages(window.chatId, true);
    });

document.addEventListener('click', function (event) {
    const item = event.target.closest('#chat-list .chat-user');
    if (!item) return;

    event.preventDefault();
    updateSidebar(item.dataset);
    activateChatTab(item.dataset.chatId, item.dataset.chatStatus, item.dataset, item.dataset.isPastChat === 'true');
});

let isSending = false;
let typingTimer = null;
let isTyping = false;

document.addEventListener('input', function (e) {

    if (!e.target.classList.contains('message-input')) return;

    const form = e.target.closest('.form-send-message');

    // avoid spam
    if (!isTyping) {
        isTyping = true;

        fetch("{{ route('admin.chat.typing') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                chat_id: form.dataset.chatId
            })
        });
    }

    // reset timer
    clearTimeout(typingTimer);

    typingTimer = setTimeout(() => {
        isTyping = false; // allow next typing request
    }, 1500);
});

document.addEventListener('click', function (e) {

    const btn = e.target.closest('.send-msg-btn');
    if (!btn) return;

    if (isSending) return;

    const form = btn.closest('.form-send-message');
    const input = form.querySelector('.message-input');
    const message = input.value.trim();
    if (!message) return;

    sendMessage(form, input, message);
});

document.addEventListener('keydown', function (e) {

    if (!e.target.classList.contains('message-input')) return;

    // Shift + Enter = new line
    if (e.key === 'Enter' && e.shiftKey) return;

    // Enter press
    if (e.key === 'Enter') {
        e.preventDefault();

        if (isSending) return; // 🚫 disable enter while sending

        const form = e.target.closest('.form-send-message');
        const input = e.target;
        const message = input.value.trim();
        if (!message) return;

        sendMessage(form, input, message);
    }
});

function sendMessage(form, input, message) {

    isSending = true;
    input.disabled = true;
    const sendButton = form.querySelector('.send-msg-btn');
    const status = form.closest('.chat-history-footer').querySelector('.send-message-status');
    sendButton.disabled = true;
    status.textContent = '';

    return fetch("{{ route('admin.chat.send') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            chat_id: form.dataset.chatId,
            message: message
        })
    })
    .then(async res => {
        const result = await res.json();
        if (!res.ok) {
            throw new Error(result.message || result.error || 'Your message could not be sent.');
        }
        return result;
    })
    .then(result => {
        if (result.status !== 'sent' || !result.message) {
            throw new Error('The server did not confirm that your message was sent.');
        }

        if (String(window.chatId) === String(form.dataset.chatId) && !messageAlreadyRendered(result.message.id)) {
            appendMessage(
                result.message.message,
                result.message.role,
                result.message.user?.image,
                result.message.formatted_created_at,
                result.message.is_read,
                result.message.id,
                false,
                result.message.user?.name
            );
        }
        input.value = '';
    })
    .catch(err => {
        console.error('Send message error:', err);
        status.textContent = err.message || 'Your message could not be sent. Please try again.';
    })
    .finally(() => {
        isSending = false;
        input.disabled = false;
        sendButton.disabled = false;
        input.focus();
    });
}

document.addEventListener('DOMContentLoaded', function () {
    let params = new URLSearchParams(window.location.search);
    let urlChatId = params.get('chatId');

    if (urlChatId) {
        const contact = getChatContact(urlChatId);
        activateChatTab(
            urlChatId,
            params.get('past') === '1' ? 'past' : (contact?.dataset.chatStatus || 'open'),
            contact?.dataset || null,
            params.get('past') === '1'
        );
    } else {
        document.querySelector('.zendesk-chat-layout')?.classList.remove('has-active-chat');
        document.getElementById('app-chat-sidebar-right')?.classList.add('d-none');
    }
});

window.addEventListener('chat-dock-open', event => {
    const { chatId: requestedChatId, name } = event.detail || {};
    if (!requestedChatId) return;

    const contact = getChatContact(requestedChatId);
    activateChatTab(
        requestedChatId,
        contact?.dataset.chatStatus || 'open',
        contact?.dataset || { name: name || `Visitor-${requestedChatId}` }
    );
});

document.getElementById('current-chat-tab').addEventListener('click', () => {
    if (!currentInboxChatId) return;
    const current = openChatTabs.get(String(currentInboxChatId));
    activateChatTab(currentInboxChatId, current?.status || 'open', current?.contact || null);
});

document.getElementById('past-chat-toggle').addEventListener('click', () => {
    openLatestPastChat();
});

function openLatestPastChat() {
    document.getElementById('past-chat-list').querySelector('button')?.click();
}

function getChatContact(chatId) {
    return Array.from(document.querySelectorAll('.chat-user'))
        .find(contact => String(contact.dataset.chatId) === String(chatId));
}

function activateChatTab(chatId, chatStatus = 'open', contactData = null, isPastChat = false) {
    const id = String(chatId);
    const contact = contactData || getChatContact(id)?.dataset || {};
    const name = contact.name || openChatTabs.get(id)?.name || 'Visitor';
    const isAlreadyVisible = String(window.chatId) === id
        && document.getElementById('app-chat-history').dataset.chatId === id
        && document.getElementById('app-chat-history').dataset.readOnlyHistory === String(isPastChat);

    openChatTabs.set(id, {
        id,
        name,
        status: chatStatus || 'open',
        contact: { ...contact },
        isPastChat
    });
    const visitorId = contact.visitorId ? String(contact.visitorId) : null;
    if (visitorId && currentInboxVisitorId !== visitorId) {
        currentInboxChatId = null;
        currentInboxVisitorId = visitorId;
    }
    if (!isPastChat) {
        currentInboxChatId = chatStatus === 'closed' ? null : id;
    }
    document.getElementById('current-chat-tab').disabled = !currentInboxChatId;
    document.querySelector('.zendesk-chat-layout').classList.add('has-active-chat');
    document.getElementById('app-chat-sidebar-right')?.classList.remove('d-none');
    document.getElementById('chat-session-navigation').classList.add('show');
    if (!isPastChat) {
        window.dispatchEvent(new CustomEvent('chat-dock-register', {
            detail: { id, name }
        }));
        window.dispatchEvent(new CustomEvent('chat-dock-read', {
            detail: { chatId: id }
        }));
    }
    if (contact.name) {
        updateSidebar(contact);
        document.getElementById('profile-visitor-id').value = contact.visitorId || '';
        document.getElementById('profile-notes').value = '';
        document.getElementById('profile-save-status').textContent = '';
        document.getElementById('profile-past-chat-count').textContent = '0';
        document.getElementById('past-chat-count').textContent = '0';
        document.getElementById('past-chat-list').replaceChildren();
        document.getElementById('profile-session-duration').textContent = '-';
        document.getElementById('profile-session-started').textContent = '-';
        const pathLink = document.getElementById('profile-visitor-path');
        pathLink.removeAttribute('href');
        pathLink.textContent = 'Loading visitor details...';
    }
    loadVisitorProfile(id);
    window.chatId = id;
    chatId = id;

    const url = new URL(window.location.href);
    url.searchParams.set('chatId', id);
    if (isPastChat) {
        url.searchParams.set('past', '1');
    } else {
        url.searchParams.delete('past');
    }
    window.history.replaceState({}, '', url);

    document.querySelectorAll('.chat-user').forEach(item => {
        item.closest('.chat-contact-list-item')?.classList.remove('active');
    });
    getChatContact(id)?.closest('.chat-contact-list-item')?.classList.add('active');
    document.getElementById('app-chat-conversation').classList.add('d-none');
    if (isPastChat) {
        document.querySelector('.chat-history-footer')?.remove();
    }

    if (isAlreadyVisible) return;

    const requestId = ++activeChatRequest;
    messageRequestId++;
    loading = false;
    allLoaded = false;
    offset = 0;
    const history = document.getElementById('app-chat-history');
    history.dataset.chatId = id;
    history.classList.add('d-none');
    history.innerHTML = '';

    let getChat = "{{ route('admin.chat.show', ':id') }}".replace(':id', encodeURIComponent(id));
    const isReadOnlyHistory = isPastChat || openChatTabs.get(id)?.isPastChat;
    if (isReadOnlyHistory) getChat += '?past=1';
    fetch(getChat)
        .then(res => {
            if (!res.ok) {
                throw new Error(`Chat request failed with status ${res.status}.`);
            }
            return res.text();
        })
        .then(html => {
            if (requestId !== activeChatRequest || String(window.chatId) !== id) return;

            const chatHistoryContainer = document.getElementById('app-chat-history');
            chatHistoryContainer.innerHTML = html;
            bindVisitorAvatarFallbacks(chatHistoryContainer);
            bindCountryFlagFallbacks(chatHistoryContainer);
            chatHistoryContainer.dataset.readOnlyHistory = String(isReadOnlyHistory);
            chatHistoryContainer.classList.remove('d-none');

            const chatBody = chatHistoryContainer.querySelector('.chat-history-body');

            const badge = getUnreadBadge(chatId);
            if (badge) {
                badge.innerText = 0;
                badge.style.display = 'none';
            }

            chatBody.addEventListener('scroll', () => {
                if (chatBody.scrollTop === 0 && !loading && !allLoaded) {
                    loadMessages(window.chatId, true);
                }
            });

            loadMessages(id);
            if (!isReadOnlyHistory) {
                subscribeToChat(id);
                markMessagesRead(id);
            }

            if (isReadOnlyHistory || (openChatTabs.get(id)?.status || chatStatus) === 'closed') {
                disableChatForm('Chat closed');
            } else {
                enableChatForm();
            }

            initSidebar();
        })
        .catch(error => {
            if (requestId === activeChatRequest) {
                console.error('Unable to open chat:', error);
                const history = document.getElementById('app-chat-history');
                history.innerHTML = '<div class="d-flex h-100 align-items-center justify-content-center text-muted">Could not load this chat. Please try again.</div>';
                history.classList.remove('d-none');
            }
        });
}

function closeChatTab(chatId) {
    const id = String(chatId);
    openChatTabs.delete(id);

    if (String(window.chatId) === id) {
        const nextChat = Array.from(openChatTabs.values()).pop();
        if (nextChat) {
            activateChatTab(nextChat.id, nextChat.status, nextChat.contact, nextChat.isPastChat);
            return;
        }

        activeChatRequest++;
        messageRequestId++;
        window.chatId = null;
        chatId = null;
        loading = false;
        allLoaded = false;

        const history = document.getElementById('app-chat-history');
        history.classList.add('d-none');
        history.innerHTML = '';
        delete history.dataset.chatId;
        document.getElementById('app-chat-conversation').classList.remove('d-none');
        document.querySelector('.zendesk-chat-layout').classList.remove('has-active-chat');
        document.getElementById('app-chat-sidebar-right')?.classList.add('d-none');
        document.querySelectorAll('.chat-user').forEach(item => {
            item.closest('.chat-contact-list-item')?.classList.remove('active');
        });

        const url = new URL(window.location.href);
        url.searchParams.delete('chatId');
        window.history.replaceState({}, '', url);
    }

}

function showCountryFlagFallback(image) {
    const countryCode = String(image.dataset.countryCode || '').toUpperCase();
    if (!/^[A-Z]{2}$/.test(countryCode)) {
        image.remove();
        return;
    }

    const fallback = document.createElement('img');
    fallback.className = 'rounded-circle visitor-country-flag-fallback';
    fallback.alt = image.alt || `${countryCode} flag`;
    fallback.width = 32;
    fallback.height = 32;
    fallback.onerror = () => {
        fallback.onerror = null;
        fallback.src = `https://flagcdn.com/48x36/${countryCode.toLowerCase()}.png`;
    };
    fallback.src = `https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/${countryCode.toLowerCase()}.svg`;
    image.hidden = true;
    image.insertAdjacentElement('afterend', fallback);
    image.countryFlagFallback = fallback;
}

function bindCountryFlagFallbacks(root) {
    const flags = [];
    if (root.matches?.('img.visitor-country-flag[data-country-code]')) {
        flags.push(root);
    }
    flags.push(...root.querySelectorAll('img.visitor-country-flag[data-country-code]'));

    flags.forEach(image => {
        if (image.dataset.fallbackBoundSrc === image.src) return;
        image.countryFlagFallback?.remove();
        image.countryFlagFallback = null;
        image.hidden = false;
        image.dataset.fallbackBoundSrc = image.src;
        image.onerror = () => showCountryFlagFallback(image);
        if (image.complete && image.naturalWidth === 0) {
            showCountryFlagFallback(image);
        }
    });
}

function bindVisitorAvatarFallbacks(root) {
    const avatarSelector = 'img#sidebar-avatar, .chat-user img.visitor-avatar-image, .chat-history img.visitor-avatar-image';
    const avatars = [];
    if (root.matches?.(avatarSelector)) {
        avatars.push(root);
    }
    avatars.push(...root.querySelectorAll(avatarSelector));

    avatars.forEach(image => {
        if (image.dataset.fallbackBoundSrc === image.src) return;
        image.dataset.fallbackBoundSrc = image.src;
        image.onerror = () => {
            image.onerror = null;
            image.src = "{{ asset('assets/images/default.png') }}";
        };
        if (image.complete && image.naturalWidth === 0) {
            image.onerror();
        }
    });
}

function updateSidebar(data) {
    const defaultAvatar = "{{ asset('assets/images/default.png') }}";
    const avatar = document.getElementById('sidebar-avatar');
    const flag = document.getElementById('sidebar-avatar-flag');
    const countryCode = String(data.countryCode || '').toLowerCase();
    flag.countryFlagFallback?.remove();
    flag.countryFlagFallback = null;
    if (/^[a-z]{2}$/.test(countryCode)) {
        flag.onerror = null;
        flag.dataset.fallbackBoundSrc = '';
        flag.src = `https://flagcdn.com/w40/${countryCode}.png`;
        flag.dataset.countryCode = countryCode;
        flag.alt = `${data.country || 'Visitor country'} flag`;
        flag.hidden = false;
        avatar.hidden = true;
    } else {
        avatar.src = data.avatar || defaultAvatar;
        avatar.hidden = false;
        flag.hidden = true;
    }
    bindVisitorAvatarFallbacks(avatar.parentElement);
    bindCountryFlagFallbacks(flag.parentElement);
    document.getElementById('sidebar-name').textContent = data.name || 'Visitor';
    document.getElementById('sidebar-role').textContent = data.visitorId
        ? `Visitor #${data.visitorId}`
        : (data.role || 'Visitor details');
    if (data.visitorId) {
        document.getElementById('profile-visitor-id').value = data.visitorId;
    }
    document.getElementById('profile-name').value = data.name || '';
    document.getElementById('profile-email').value = data.email || '';
    document.getElementById('profile-phone').value = data.phone === '-' ? '' : (data.phone || '');
    document.getElementById('profile-ip-address').textContent = data.ipaddress || '-';
    document.getElementById('profile-location').textContent = [data.city, data.country].filter(value => value && value !== '-').join(', ') || '-';
    document.getElementById('profile-device').textContent = data.device || '-';
    document.getElementById('profile-browser').textContent = data.browser || '-';
    document.getElementById('profile-os').textContent = data.os || '-';
}

function formatSessionDuration(seconds) {
    const totalMinutes = Math.floor(Math.max(0, Number(seconds) || 0) / 60);
    if (totalMinutes < 1) return 'Less than 1 min';
    if (totalMinutes < 60) return `${totalMinutes} min`;
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return minutes ? `${hours} hr ${minutes} min` : `${hours} hr`;
}

function renderPastChats(chats, currentChatId, pastMessageCount) {
    const navList = document.getElementById('past-chat-list');
    navList.replaceChildren();
    const pastChats = Array.isArray(chats) ? chats : [];
    document.getElementById('profile-past-chat-count').textContent = String(Math.max(0, Number(pastMessageCount) || 0));
    document.getElementById('past-chat-count').textContent = String(pastChats.length);

    if (!pastChats.length) {
        const empty = document.createElement('div');
        empty.className = 'p-3 small text-body-secondary';
        empty.textContent = 'No past chats';
        navList.appendChild(empty);
        return;
    }

    const makePastChatButton = pastChat => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-link text-start text-decoration-none w-100 p-2';
        button.dataset.chatId = String(pastChat.id);
        button.dataset.chatStatus = pastChat.status || 'closed';
        button.textContent = pastChat.preview || 'Open past conversation';
        button.setAttribute('aria-label', `Open past conversation: ${button.textContent}`);
        button.addEventListener('click', () => {
            const current = currentChatId ? openChatTabs.get(String(currentChatId)) : null;
            if (currentChatId) currentInboxChatId = String(currentChatId);
            activateChatTab(
                pastChat.id,
                'past',
                current?.contact || (currentChatId ? getChatContact(currentChatId)?.dataset : null) || null,
                true
            );
        });
        return button;
    };
    pastChats.forEach(pastChat => {
        navList.appendChild(makePastChatButton(pastChat));
    });
}

function updateCurrentInboxChat(profile) {
    const visitorId = String(profile.visitor.id);
    if (currentInboxVisitorId !== visitorId) {
        currentInboxChatId = null;
    }
    currentInboxVisitorId = visitorId;
    if (profile.current_chat?.id) {
        currentInboxChatId = String(profile.current_chat.id);
    }
    document.getElementById('current-chat-tab').disabled = !currentInboxChatId;
}

async function loadVisitorProfile(chatId) {
    const requestId = ++profileRequestId;
    const saveStatus = document.getElementById('profile-save-status');
    saveStatus.textContent = '';

    try {
        const url = profileRoute.replace(':id', encodeURIComponent(String(chatId)));
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!response.ok) throw new Error(`Visitor profile request failed with status ${response.status}.`);
        const profile = await response.json();
        if (requestId !== profileRequestId || String(window.chatId) !== String(chatId)) return;

        updateCurrentInboxChat(profile);
        const visitor = profile.visitor;
        updateSidebar({
            visitorId: visitor.id,
            name: visitor.name,
            email: visitor.email,
            phone: visitor.phone,
            avatar: visitor.avatar,
            countryCode: visitor.country_code,
            ipaddress: visitor.ip_address,
            country: visitor.country,
            city: visitor.city,
            device: visitor.device,
            browser: visitor.browser,
            os: visitor.os,
            source: visitor.source,
            keyword: visitor.keyword
        });
        document.getElementById('profile-visitor-id').value = visitor.id;
        document.getElementById('sidebar-role').textContent = `Visitor #${visitor.id}`;
        document.getElementById('profile-notes').value = visitor.notes || '';
        const pathLink = document.getElementById('profile-visitor-path');
        try {
            if (typeof visitor.last_url !== 'string' || !visitor.last_url.trim()) {
                throw new Error('No visitor page URL is available.');
            }
            const parsedUrl = new URL(visitor.last_url);
            if (['http:', 'https:'].includes(parsedUrl.protocol)) {
                pathLink.href = parsedUrl.toString();
                pathLink.textContent = visitor.last_url;
            } else {
                pathLink.removeAttribute('href');
                pathLink.textContent = 'No page recorded';
            }
        } catch {
            pathLink.removeAttribute('href');
            pathLink.textContent = 'No page recorded';
        }
        document.getElementById('profile-session-duration').textContent = formatSessionDuration(visitor.session_duration_seconds);
        document.getElementById('profile-session-started').textContent = visitor.session_started_at
            ? new Date(visitor.session_started_at).toLocaleString()
            : '-';
        document.getElementById('profile-source').textContent = visitor.source || 'Direct';
        document.getElementById('profile-keyword').textContent = visitor.keyword || '-';
        renderPastChats(profile.past_chats, currentInboxChatId, profile.past_message_count);
    } catch (error) {
        if (requestId !== profileRequestId) return;
        console.error('Unable to load visitor profile:', error);
        saveStatus.textContent = 'Could not load visitor details.';
        saveStatus.className = 'small mt-2 text-danger';
    }
}

document.getElementById('visitor-profile-form').addEventListener('submit', async event => {
    event.preventDefault();
    if ({{ (int) auth()->user()->role }} !== 2) return;

    const chatId = String(window.chatId || '');
    if (!chatId) return;

    const button = document.getElementById('profile-save-button');
    const status = document.getElementById('profile-save-status');
    button.disabled = true;
    status.textContent = 'Saving...';
    status.className = 'small mt-2 text-body-secondary';
    try {
        const response = await fetch(profileUpdateRoute.replace(':id', encodeURIComponent(chatId)), {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: document.getElementById('profile-name').value.trim(),
                email: document.getElementById('profile-email').value.trim() || null,
                phone: document.getElementById('profile-phone').value.trim() || null,
                notes: document.getElementById('profile-notes').value
            })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat().join(' ') || 'Visitor profile could not be saved.');
        status.textContent = result.message || 'Visitor profile saved.';
        status.className = 'small mt-2 text-success';
        document.getElementById('sidebar-name').textContent = document.getElementById('profile-name').value.trim();
        document.querySelector('#app-chat-history .chat-history-header h6')?.replaceChildren(
            document.createTextNode(document.getElementById('profile-name').value.trim())
        );
        window.dispatchEvent(new CustomEvent('chat-dock-register', {
            detail: { id: chatId, name: document.getElementById('profile-name').value.trim() }
        }));
        const contact = getChatContact(chatId);
        if (contact) {
            contact.dataset.name = document.getElementById('profile-name').value.trim();
            contact.dataset.email = document.getElementById('profile-email').value.trim();
            contact.dataset.phone = document.getElementById('profile-phone').value.trim();
            const name = contact.querySelector('.chat-contact-name');
            if (name) name.textContent = contact.dataset.name;
        }
    } catch (error) {
        console.error('Unable to save visitor profile:', error);
        status.textContent = error.message || 'Visitor profile could not be saved.';
        status.className = 'small mt-2 text-danger';
    } finally {
        button.disabled = false;
    }
});

function escapeHtml(str) {
    if (!str) return '';

    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function disableChatForm(reason = 'Chat closed') {
    const form = document.querySelector('.form-send-message');
    if (!form) return;

    const input = form.querySelector('.message-input');
    const sendBtn = form.querySelector('.send-msg-btn');
    const emojiBtn = form.querySelector('.emoji-btn');

    input.disabled = true;
    input.placeholder = reason;

    sendBtn.disabled = true;
    emojiBtn.style.pointerEvents = 'none';
    emojiBtn.style.opacity = '0.5';
}

function enableChatForm() {
    const form = document.querySelector('.form-send-message');
    if (!form) return;

    const input = form.querySelector('.message-input');
    const sendBtn = form.querySelector('.send-msg-btn');
    const emojiBtn = form.querySelector('.emoji-btn');

    input.disabled = false;
    input.placeholder = 'Type your message here...';

    sendBtn.disabled = false;
    emojiBtn.style.pointerEvents = 'auto';
    emojiBtn.style.opacity = '1';
}

document.addEventListener('click', function (e) {

    const btn = e.target.closest('.close-chat-btn');
    if (!btn) return;

    e.preventDefault();

    const chatId = btn.dataset.chatId;

    if (!chatId) return;

    if (!confirm('Close this chat?')) return;

    fetch("{{ route('admin.chat.close') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ chat_id: chatId })
    })
    .then(res => res.json())
    .then(() => {
        // optional UX
        disableChatForm('Chat closed');
    });
});

function initSidebar() {
    // Avatar click pe sidebar open karo
    document.querySelectorAll('[data-bs-toggle="sidebar"]').forEach(function(el) {
        el.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            if (!targetId) return;

            const sidebar = document.querySelector(targetId);
            if (!sidebar) return;

            sidebar.classList.toggle('show');

            // Overlay handle karo
            const hasOverlay = this.hasAttribute('data-overlay');
            if (hasOverlay) {
                let overlay = document.querySelector('.sidebar-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'sidebar-overlay';
                    document.body.appendChild(overlay);
                }
                overlay.classList.toggle('show');

                // Overlay click pe sidebar band karo
                overlay.addEventListener('click', function () {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
        });
    });

    // Close button bhi handle karo
    document.querySelectorAll('.close-sidebar').forEach(function(el) {
        el.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const sidebar = document.querySelector(targetId);
            if (sidebar) sidebar.classList.remove('show');

            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) overlay.classList.remove('show');
        });
    });
}
</script>
@endsection
