<ul class="list-unstyled chat-contact-list py-2 mb-0" id="chat-list">
    <li class="chat-contact-list-item chat-contact-list-item-title mt-0">
        <h5 class="text-primary mb-0">{{ $historyView ? 'History' : 'Online visitors' }}</h5>
    </li>
    @forelse ($chats as $chat)
        @php
            $isOnline = $chat->main_visitor?->last_seen_at
                && $chat->main_visitor->last_seen_at->gte(now()->subSeconds(\App\Models\Visitor::ONLINE_TIMEOUT_SECONDS));
            $isPastChat = $chat->isPastConversation(now()->subWeek());
            $count = $chat->unread_messages_count;
            $lastMessage = $chat->last_message_preview;
        @endphp

        <li class="chat-contact-list-item mb-1 {{ request('chatId') == $chat->id ? 'active' : '' }}">
            <a class="d-flex align-items-center chat-user"
                href="{{ route('admin.chat', array_filter([
                    'view' => $historyView ? 'history' : null,
                    'chatId' => $chat->id,
                    'past' => $isPastChat ? 1 : null,
                ])) }}"
                data-chat-id="{{ $chat->id }}"
                data-is-past-chat="{{ $isPastChat ? 'true' : 'false' }}"
                data-presence="{{ $isOnline ? 'online' : 'offline' }}"
                data-user-id="{{ $chat->visitor->id }}"
                data-visitor-id="{{ $chat->main_visitor->id ?? '' }}"
                data-chat-status="{{ $chat->status }}"
                data-name="{{ $chat->visitor->name }}"
                data-email="{{ $chat->visitor->email }}"
                data-phone="{{ $chat->visitor->phone ?? '-' }}"
                data-role="{{ $chat->visitor->isRole() ?? 'User' }}"
                data-ipaddress="{{ $chat->main_visitor->ip_address ?? '-' }}"
                data-country="{{ $chat->main_visitor->country ?? '-' }}"
                data-city="{{ $chat->main_visitor->city ?? '-' }}"
                data-device="{{ $chat->main_visitor->device ?? '-' }}"
                data-browser="{{ $chat->main_visitor->browser ?? '-' }}"
                data-os="{{ $chat->main_visitor->os ?? '-' }}"
                data-country-code="{{ $chat->main_visitor->country_code ?? '' }}"
                data-avatar="{{ $chat->visitor->image ? asset($chat->visitor->image) : asset('assets/images/default.png') }}">
                <div class="flex-shrink-0 avatar {{ $isOnline ? 'avatar-online' : 'avatar-offline' }} profile-av-1">
                    @if(preg_match('/^[A-Za-z]{2}$/', (string) ($chat->main_visitor->country_code ?? '')))
                        <img src="https://flagcdn.com/w40/{{ strtolower($chat->main_visitor->country_code) }}.png"
                            alt="{{ $chat->main_visitor->country ?? 'Visitor country' }} flag"
                            class="rounded-circle visitor-country-flag"
                            data-country-code="{{ strtolower($chat->main_visitor->country_code) }}">
                    @else
                        <img src="{{ $chat->visitor->image ? asset($chat->visitor->image) : asset('assets/images/default.png') }}"
                            alt="Avatar"
                            class="rounded-circle visitor-avatar-image">
                    @endif
                </div>

                <div class="chat-contact-info flex-grow-1 ms-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="chat-contact-name text-truncate m-0 fw-normal">{{ $chat->visitor->name }}</h6>
                        @if(in_array(auth()->user()->role, [1, 2]))
                            <span class="badge bg-danger ms-2 unread-badge"
                                style="display: {{ $count > 0 ? 'inline-block' : 'none' }}">{{ $count }}</span>
                        @endif
                        <small class="chat-contact-list-item-time">{{ optional($chat->updated_at)->diffForHumans() }}</small>
                    </div>
                    <small class="chat-contact-status text-truncate last-message">
                        {{ $lastMessage ?? 'No messages yet' }}
                    </small>
                </div>
            </a>
        </li>
    @empty
        <li class="chat-contact-list-item chat-list-item-0">
            <h6 class="text-body-secondary mb-0">
                {{ $historyView ? 'No offline visitors found' : 'No online visitors found' }}
            </h6>
        </li>
    @endforelse
</ul>
