<div class="chat-history-wrapper">
    <div class="chat-history-header border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex overflow-hidden align-items-center">
                <i class="icon-base bx bx-menu icon-lg cursor-pointer d-lg-none d-block me-4"
                    data-bs-toggle="sidebar" data-overlay="" data-target="#app-chat-contacts"></i>
                <div class="flex-shrink-0 avatar avatar-online">
                    @if(preg_match('/^[A-Za-z]{2}$/', (string) ($chat->main_visitor->country_code ?? '')))
                        <img src="https://flagcdn.com/w40/{{ strtolower($chat->main_visitor->country_code) }}.png"
                            alt="{{ $chat->main_visitor->country ?? 'Visitor country' }} flag"
                            class="rounded-circle visitor-country-flag"
                            data-bs-toggle="sidebar" data-overlay="" data-target="#app-chat-sidebar-right">
                    @else
                        <img src="{{ $user->image ? asset($user->image) : asset('assets/images/default.png')  }}"
                            alt="Avatar" class="rounded-circle" data-bs-toggle="sidebar" data-overlay=""
                            data-target="#app-chat-sidebar-right">
                    @endif
                </div>
                <div class="chat-contact-info flex-grow-1 ms-4">
                    <h6 class="m-0 fw-normal">{{ $user->name }}</h6>
                </div>
            </div>
            <div class="d-flex align-items-center">
                {{-- <div class="dropdown">
                    <button
                        class="btn btn-icon btn-text-secondary text-secondary rounded-pill dropdown-toggle hide-arrow"
                        data-bs-toggle="dropdown" aria-expanded="true" id="chat-header-actions"><i
                            class="icon-base bx bx-dots-vertical-rounded icon-md"></i></button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="chat-header-actions">
                        <a class="dropdown-item" href="javascript:void(0);">View Contact</a>
                        <a class="dropdown-item" href="javascript:void(0);">Mute Notifications</a>
                        <a class="dropdown-item close-chat-btn" href="javascript:void(0);" data-chat-id="{{ $user->get_chat->id }}">Close Chat</a>
                        <a class="dropdown-item" href="javascript:void(0);">Clear Chat</a>
                        <a class="dropdown-item" href="javascript:void(0);">Report</a>
                    </div>
                </div> --}}
            </div>
        </div>
    </div>
    <div class="chat-history-body ps">

        <ul class="list-unstyled chat-history">
            <li class="text-center py-2">
                <button
                    class="btn btn-sm btn-outline-secondary"
                    id="load-more-btn"
                    style="display:none"
                >
                    Load more
                </button>
            </li>
            <li class="text-center text-muted py-3" id="no-message">
                No messages yet
            </li>
        </ul>
        <div id="typing-indicator"
            style="font-size:12px;color:#888;padding:6px;display:none">
            Visitor is typing...
        </div>
        <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
            <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
        </div>
        <div class="ps__rail-y" style="top: 0px; right: 0px;">
            <div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 0px;"></div>
        </div>
    </div>
    <!-- Chat message form -->
    @if((int) auth()->user()->role === 2 && !$readOnly)
        <div class="chat-history-footer shadow-xs">
            <form class="form-send-message d-flex justify-content-between align-items-center " data-chat-id="{{ $chatId }}">
                <input class="form-control message-input border-0 me-4 shadow-none"
                    placeholder="Type your message here...">
                <div class="message-actions d-flex align-items-center">
                    <span class="btn btn-text-secondary btn-icon rounded-pill cursor-pointer emoji-btn">
                        <i class="fa-regular fa-face-grin"></i>
                    </span>
                    <button type="button" class="btn btn-primary d-flex send-msg-btn">
                        <span class="align-middle d-md-inline-block d-none">Send</span>
                        <i class="icon-base bx bx-paper-plane icon-sm ms-md-2 ms-0"></i>
                    </button>
                </div>
            </form>
            @if((int) auth()->user()->role === 2)
                <div class="chat-shortcut-tools">
                    <div class="chat-shortcut-panel" hidden>
                        <div class="chat-shortcut-list" role="listbox"></div>
                        <div class="small text-danger chat-shortcut-status" role="status" aria-live="polite"></div>
                    </div>
                </div>
            @endif
            <div class="small text-danger mt-2 send-message-status" role="status" aria-live="polite"></div>
        </div>
    @endif
</div>
