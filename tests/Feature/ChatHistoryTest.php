<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ChatHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function createMessageAt(array $attributes, $createdAt): Message
    {
        $message = Message::create($attributes);
        $message->created_at = $createdAt;
        $message->save();

        return $message;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_agent_can_send_a_reply_in_a_visitor_chat(): void
    {
        $brand = Brand::create(['name' => 'Test brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'admin-reply-visitor',
        ]);
        $visitorUser = User::create([
            'name' => 'Visitor',
            'email' => 'reply-visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $agent = User::create([
            'name' => 'Agent',
            'email' => 'reply-agent@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $chat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $agent->id,
            'status' => 'open',
        ]);

        $this->actingAs($agent)
            ->get(route('admin.chat.show', $chat->id, false))
            ->assertOk()
            ->assertSee('form-send-message')
            ->assertSee('message-input')
            ->assertSee('send-msg-btn');

        $this->get(route('admin.chat', [], false))
            ->assertOk()
            ->assertSee('id="profile-save-button"', false)
            ->assertSee('Save details');

        $response = $this
            ->postJson(route('admin.chat.send', [], false), [
                'chat_id' => $chat->id,
                'message' => 'Agent reply',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'sent')
            ->assertJsonPath('message.message', 'Agent reply')
            ->assertJsonPath('message.role', 2)
            ->assertJsonPath('message.user.id', $agent->id);
        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'sender' => $agent->id,
            'message' => 'Agent reply',
        ]);
        $this->assertNotSame($visitorUser->id, $agent->id);
    }

    public function test_chat_inbox_separates_online_visitors_from_offline_history(): void
    {
        $brand = Brand::create(['name' => 'Presence brand', 'status' => 1]);
        $admin = User::create([
            'name' => 'Presence admin',
            'email' => 'presence-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);

        $visitors = [];
        foreach ([
            ['Online Visitor', now()],
            ['Offline Visitor', now()->subMinutes(10)],
        ] as [$name, $lastSeenAt]) {
            $visitor = Visitor::create([
                'brand_id' => $brand->id,
                'session_id' => str_replace(' ', '-', strtolower($name)),
                'last_seen_at' => $lastSeenAt,
            ]);
            $visitorUser = User::create([
                'name' => $name,
                'email' => str_replace(' ', '-', strtolower($name)) . '@example.com',
                'password' => 'password',
                'role' => 3,
                'visitor_id' => $visitor->id,
            ]);
            $visitors[$name] = [$visitor, $visitorUser];
        }

        $onlineChat = Chat::create([
            'visitor_id' => $visitors['Online Visitor'][0]->id,
            'brand_id' => $brand->id,
            'status' => 'open',
        ]);
        $offlineOpenChat = Chat::create([
            'visitor_id' => $visitors['Offline Visitor'][0]->id,
            'brand_id' => $brand->id,
            'status' => 'open',
        ]);
        $offlineClosedChat = Chat::create([
            'visitor_id' => $visitors['Offline Visitor'][0]->id,
            'brand_id' => $brand->id,
            'status' => 'closed',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chat', [], false))
            ->assertOk()
            ->assertSee('data-name="Online Visitor"', false)
            ->assertDontSee('data-name="Offline Visitor"', false);

        $this->get(route('admin.chat', ['view' => 'history'], false))
            ->assertOk()
            ->assertSee('History')
            ->assertSee('data-name="Offline Visitor"', false)
            ->assertDontSee('data-name="Online Visitor"', false)
            ->assertSee('data-chat-id="' . $offlineOpenChat->id . '"', false)
            ->assertSee('data-chat-id="' . $offlineClosedChat->id . '"', false);

        $this->get(route('admin.chat.contacts', ['view' => 'history'], false))
            ->assertOk()
            ->assertSee('data-name="Offline Visitor"', false)
            ->assertDontSee('data-name="Online Visitor"', false);

        $this->assertNotSame($onlineChat->id, $offlineOpenChat->id);
    }

    public function test_admin_can_view_but_cannot_send_messages_or_update_visitor_details(): void
    {
        $brand = Brand::create(['name' => 'Test brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'admin-view-only-visitor',
        ]);
        $visitorUser = User::create([
            'name' => 'Visitor',
            'email' => 'view-only-visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'view-only-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $chat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $admin->id,
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chat.show', $chat->id, false))
            ->assertOk()
            ->assertDontSee('form-send-message')
            ->assertDontSee('message-input')
            ->assertDontSee('send-msg-btn');

        $this->get(route('admin.chat', [], false))
            ->assertOk()
            ->assertSee('id="profile-name"', false)
            ->assertSee('readonly', false)
            ->assertDontSee('id="profile-save-button"', false)
            ->assertDontSee('Save details');

        $this->postJson(route('admin.chat.send', [], false), [
            'chat_id' => $chat->id,
            'message' => 'Admin should not send',
        ])->assertForbidden();

        $this->patchJson(route('admin.chat.profile.update', $chat->id, false), [
            'name' => 'Changed by admin',
            'email' => 'changed@example.com',
            'phone' => '5551234',
            'address' => 'Updated address',
            'notes' => 'Updated notes',
            'tags' => ['updated'],
        ])->assertForbidden();

        $this->get(route('admin.visitor.edit', $visitor->id, false))->assertForbidden();
        $this->put(route('admin.visitor.update', $visitor->id, false), [
            'email' => 'changed@example.com',
        ])->assertForbidden();

        $this->assertDatabaseMissing('messages', [
            'chat_id' => $chat->id,
            'message' => 'Admin should not send',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $visitorUser->id,
            'name' => 'Visitor',
            'email' => 'view-only-visitor@example.com',
        ]);
    }

    public function test_profile_shows_only_chats_at_least_one_week_old_as_past_chats(): void
    {
        $brand = Brand::create(['name' => 'Test brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'persistent-visitor',
        ]);
        $visitorUser = User::create([
            'name' => 'Visitor',
            'email' => 'visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $pastDate = now()->subWeek();
        $pastChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'closed',
            'last_visitor_activity_at' => $pastDate,
            'closed_at' => $pastDate,
        ]);
        $currentChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'open',
            'last_visitor_activity_at' => now(),
        ]);
        $otherOpenChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'open',
            'last_visitor_activity_at' => now()->subMinute(),
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        $oldOpenChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'open',
            'last_visitor_activity_at' => now()->subWeeks(2),
            'created_at' => now()->subWeeks(2),
            'updated_at' => now()->subWeeks(2),
        ]);
        $recentClosedChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'closed',
            'last_visitor_activity_at' => now()->subDays(2),
            'closed_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $this->createMessageAt([
            'chat_id' => $pastChat->id,
            'sender' => $visitorUser->id,
            'message' => 'I was here last week',
        ], $pastDate);
        $this->createMessageAt([
            'chat_id' => $oldOpenChat->id,
            'sender' => $visitorUser->id,
            'message' => 'An older open conversation',
        ], now()->subWeeks(2));

        $response = $this->actingAs($admin)->getJson(route('admin.chat.profile', $pastChat->id, false));

        $response->assertOk()
            ->assertJsonPath('current_chat.id', $currentChat->id)
            ->assertJsonPath('past_message_count', 2)
            ->assertJsonFragment(['id' => $pastChat->id, 'preview' => 'I was here last week'])
            ->assertJsonFragment(['id' => $oldOpenChat->id, 'preview' => 'An older open conversation', 'status' => 'closed'])
            ->assertJsonMissing(['id' => $otherOpenChat->id])
            ->assertJsonMissing(['id' => $recentClosedChat->id]);

        $pastChats = collect($response->json('past_chats'));
        $pastChatEntry = $pastChats->firstWhere('id', $pastChat->id);
        $this->assertFalse($pastChats->contains('id', $currentChat->id));
        $this->assertSame($pastDate->toIso8601String(), $pastChatEntry['date']);
        $this->assertCount(2, $response->json('past_chats'));
    }

    public function test_recent_agent_reply_keeps_a_conversation_out_of_past_chats(): void
    {
        $brand = Brand::create(['name' => 'Recent reply brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'recent-agent-reply-visitor',
        ]);
        $visitorUser = User::create([
            'name' => 'Recent reply visitor',
            'email' => 'recent-agent-reply-visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $agent = User::create([
            'name' => 'Recent reply agent',
            'email' => 'recent-agent-reply-agent@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $admin = User::create([
            'name' => 'Recent reply admin',
            'email' => 'recent-agent-reply-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);

        $oldChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'closed',
            'last_visitor_activity_at' => now()->subWeeks(2),
            'closed_at' => now()->subWeeks(2),
            'created_at' => now()->subWeeks(2),
            'updated_at' => now()->subWeeks(2),
        ]);
        $recentChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'open',
            'last_visitor_activity_at' => now()->subWeeks(2),
            'created_at' => now()->subWeeks(2),
            'updated_at' => now()->subWeeks(2),
        ]);
        $this->createMessageAt([
            'chat_id' => $oldChat->id,
            'sender' => $visitorUser->id,
            'message' => 'Old conversation',
        ], now()->subWeeks(2));
        $this->createMessageAt([
            'chat_id' => $recentChat->id,
            'sender' => $agent->id,
            'message' => 'hello today',
        ], now());

        $response = $this->actingAs($admin)
            ->getJson(route('admin.chat.profile', $oldChat->id, false));

        $response->assertOk()
            ->assertJsonPath('current_chat.id', $recentChat->id)
            ->assertJsonFragment(['id' => $oldChat->id, 'preview' => 'Old conversation']);
        $pastChats = collect($response->json('past_chats'));
        $this->assertTrue($pastChats->contains('id', $oldChat->id));
        $this->assertFalse($pastChats->contains('id', $recentChat->id));

        $this->get(route('admin.chat.show', $recentChat->id, false) . '?past=1')
            ->assertOk()
            ->assertDontSee('form-send-message')
            ->assertDontSee('message-input');
    }

    public function test_old_open_conversation_remains_current_and_profile_counts_old_messages(): void
    {
        $brand = Brand::create(['name' => 'Old open chat brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'old-open-current-visitor',
        ]);
        $visitorUser = User::create([
            'name' => 'Old open visitor',
            'email' => 'old-open-visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $agent = User::create([
            'name' => 'Old open agent',
            'email' => 'old-open-agent@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $agent->auth_brands()->attach($brand->id);
        $chat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $agent->id,
            'status' => 'open',
            'last_visitor_activity_at' => now()->subWeeks(2),
            'created_at' => now()->subWeeks(2),
            'updated_at' => now()->subWeeks(2),
        ]);
        $this->createMessageAt([
            'chat_id' => $chat->id,
            'sender' => $visitorUser->id,
            'message' => 'Old visitor message',
        ], now()->subWeeks(2));

        $this->actingAs($agent)
            ->getJson(route('admin.chat.profile', $chat->id, false))
            ->assertOk()
            ->assertJsonPath('current_chat.id', $chat->id)
            ->assertJsonPath('past_message_count', 1);

        $this->postJson(route('admin.chat.send', [], false), [
            'chat_id' => $chat->id,
            'message' => 'Reply to old open chat',
        ])->assertOk()
            ->assertJsonPath('message.message', 'Reply to old open chat');
    }

    public function test_current_and_past_views_filter_messages_by_the_one_week_cutoff(): void
    {
        $brand = Brand::create(['name' => 'Message range brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'message-range-visitor',
        ]);
        $visitorUser = User::create([
            'name' => 'Message range visitor',
            'email' => 'message-range-visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $agent = User::create([
            'name' => 'Message range agent',
            'email' => 'message-range-agent@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $agent->auth_brands()->attach($brand->id);
        $chat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $agent->id,
            'status' => 'open',
        ]);
        $cutoff = now()->subWeek();
        $this->createMessageAt([
            'chat_id' => $chat->id,
            'sender' => $visitorUser->id,
            'message' => 'Older than one week',
        ], $cutoff->copy()->subSecond());
        $this->createMessageAt([
            'chat_id' => $chat->id,
            'sender' => $agent->id,
            'message' => 'Within the last week',
        ], $cutoff->copy()->addSecond());

        $this->actingAs($agent)
            ->getJson(route('admin.chat.messages', $chat->id, false))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Within the last week');

        $this->getJson(route('admin.chat.messages', $chat->id, false) . '?past=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Older than one week');

        $this->get(route('admin.chat.show', $chat->id, false) . '?past=1')
            ->assertOk()
            ->assertDontSee('form-send-message')
            ->assertDontSee('message-input');
        $this->get(route('admin.chat.show', $chat->id, false))
            ->assertOk()
            ->assertSee('form-send-message')
            ->assertSee('message-input');
    }

    public function test_past_chat_opens_full_read_only_conversation_without_a_composer(): void
    {
        $brand = Brand::create(['name' => 'Past chat view brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'read-only-past-chat-visitor',
            'last_seen_at' => now()->subWeeks(2),
        ]);
        $visitorUser = User::create([
            'name' => 'Past chat visitor',
            'email' => 'read-only-past-chat-visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $agent = User::create([
            'name' => 'Past chat agent',
            'email' => 'read-only-past-chat-agent@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $agent->auth_brands()->attach($brand->id);
        $pastChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $agent->id,
            'status' => 'closed',
            'last_visitor_activity_at' => now()->subWeeks(2),
            'closed_at' => now()->subWeeks(2),
        ]);
        $this->createMessageAt([
            'chat_id' => $pastChat->id,
            'sender' => $visitorUser->id,
            'message' => 'This old message should be visible',
        ], now()->subWeeks(2));

        $this->actingAs($agent)
            ->get(route('admin.chat.show', $pastChat->id, false) . '?past=1')
            ->assertOk()
            ->assertDontSee('form-send-message')
            ->assertDontSee('message-input')
            ->assertDontSee('send-msg-btn');

        $this->getJson(route('admin.chat.messages', $pastChat->id, false) . '?past=1')
            ->assertOk()
            ->assertJsonPath('data.0.message', 'This old message should be visible');

        $this->postJson(route('admin.chat.send', [], false), [
            'chat_id' => $pastChat->id,
            'message' => 'This reply must be rejected',
        ])->assertForbidden();
    }

    public function test_visitor_source_and_search_keyword_are_saved_and_returned_in_profile(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'success'])]);
        $brand = Brand::create([
            'name' => 'Test brand',
            'domain' => 'example.test',
            'status' => 1,
        ]);
        $visitorSession = 'google-search-visitor';

        $initResponse = $this->postJson('/api/visitor-init', [
            'domain' => 'example.test',
            'session_id' => $visitorSession,
            'referrer' => 'https://www.google.com/search?q=live+chat+support',
            'landing_url' => 'https://example.test/pricing',
        ]);
        $initResponse->assertOk()->assertJsonPath('success', true);

        $visitor = Visitor::where('session_id', $visitorSession)->firstOrFail();
        $this->assertSame('google.com', $visitor->source);
        $this->assertSame('live chat support', $visitor->keyword);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'attribution-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $this->actingAs($admin)
            ->getJson(route('admin.chat.profile', $initResponse->json('chat_id'), false))
            ->assertOk()
            ->assertJsonPath('visitor.source', 'google.com')
            ->assertJsonPath('visitor.keyword', 'live chat support');
    }

    public function test_visitor_country_code_is_saved_and_used_for_chat_avatars(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Pakistan',
                'countryCode' => 'PK',
                'city' => 'Lahore',
            ]),
        ]);
        $brand = Brand::create([
            'name' => 'Country flag brand',
            'domain' => 'country-flag.test',
            'status' => 1,
        ]);

        $response = $this->postJson('/api/visitor-init', [
            'domain' => 'country-flag.test',
            'session_id' => 'pakistan-visitor',
        ])->assertOk();

        $visitor = Visitor::where('session_id', 'pakistan-visitor')->firstOrFail();
        $this->assertSame('PK', $visitor->country_code);

        $admin = User::create([
            'name' => 'Country flag admin',
            'email' => 'country-flag-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $chatPage = $this->actingAs($admin)->get(route('admin.chat', [], false))->assertOk();
        $chatPage->assertSee('https://flagcdn.com/w40/pk.png');
        $chatPage->assertSee('data-country-code="PK"', false);
        $this->getJson(route('admin.chat.profile', $response->json('chat_id'), false))
            ->assertOk()
            ->assertJsonPath('visitor.country_code', 'PK');
    }

    public function test_new_visitor_notification_includes_the_visitors_country_flag_code(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Pakistan',
                'countryCode' => 'PK',
            ]),
        ]);
        User::create([
            'name' => 'Country notification admin',
            'email' => 'country-notification-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        Brand::create([
            'name' => 'Notification flag brand',
            'domain' => 'notification-flag.test',
            'status' => 1,
        ]);

        $this->postJson('/api/visitor-init', [
            'domain' => 'notification-flag.test',
            'session_id' => 'notification-flag-visitor',
        ])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'type' => \App\Notifications\NewVisitorNotification::class,
        ]);
        $notification = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('type', \App\Notifications\NewVisitorNotification::class)
            ->first();
        $this->assertSame('PK', json_decode($notification->data, true)['country_code']);
    }

    public function test_localhost_visitors_use_public_ip_location_for_country_flag(): void
    {
        $this->app['env'] = 'local';
        Http::fakeSequence()
            ->push([
                'status' => 'fail',
                'message' => 'private range',
                'query' => '127.0.0.1',
            ])
            ->push([
                'status' => 'success',
                'country' => 'United States',
                'countryCode' => 'US',
                'query' => '203.0.113.25',
            ]);
        Brand::create([
            'name' => 'Local visitor flag brand',
            'domain' => 'local-visitor-flag.test',
            'status' => 1,
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/visitor-init', [
                'domain' => 'local-visitor-flag.test',
                'session_id' => 'localhost-us-visitor',
            ]);

        $response->assertOk();
        Http::assertSentCount(2);
        $visitor = Visitor::where('session_id', 'localhost-us-visitor')->firstOrFail();
        $this->assertSame('United States', $visitor->country);
        $this->assertSame('US', $visitor->country_code);
    }

    public function test_legacy_visitor_country_name_renders_flag_images_in_chat_list_and_header(): void
    {
        $brand = Brand::create(['name' => 'Legacy country brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'legacy-pakistan-visitor',
            'country' => 'United States',
            'last_seen_at' => now()->subMinutes(10),
        ]);
        User::create([
            'name' => 'Visitor-legacy',
            'email' => 'legacy-country-visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $agent = User::create([
            'name' => 'Legacy country agent',
            'email' => 'legacy-country-agent@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $agent->auth_brands()->attach($brand->id);
        $chat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $agent->id,
            'status' => 'open',
        ]);

        $this->actingAs($agent)
            ->get(route('admin.chat', ['view' => 'history'], false))
            ->assertOk()
            ->assertSee('https://flagcdn.com/w40/us.png');
        $this->get(route('admin.chat.show', $chat->id, false))
            ->assertOk()
            ->assertSee('https://flagcdn.com/w40/us.png');
        $this->getJson(route('admin.chat.profile', $chat->id, false))
            ->assertOk()
            ->assertJsonPath('visitor.country_code', 'US');
    }

    public function test_chat_message_history_includes_visitor_country_code_for_message_avatars(): void
    {
        $brand = Brand::create(['name' => 'Message flag brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'message-flag-visitor',
            'country' => 'United States',
        ]);
        $visitorUser = User::create([
            'name' => 'Visitor',
            'email' => 'message-flag-visitor@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $agent = User::create([
            'name' => 'Message flag agent',
            'email' => 'message-flag-agent@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $chat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $agent->id,
            'status' => 'open',
        ]);
        Message::create([
            'chat_id' => $chat->id,
            'sender' => $visitorUser->id,
            'message' => 'Hello from the visitor',
        ]);

        $this->actingAs($agent)
            ->getJson(route('admin.chat.messages', $chat->id, false))
            ->assertOk()
            ->assertJsonPath('data.0.user.country_code', 'US');
    }

    public function test_updating_visible_profile_fields_preserves_address_and_tags(): void
    {
        $brand = Brand::create(['name' => 'Test brand', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'profile-hidden-fields-visitor',
            'notes' => 'Existing notes',
            'tags' => ['existing-tag'],
        ]);
        $visitorUser = User::create([
            'name' => 'Visitor',
            'email' => 'profile-hidden-fields@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
            'address' => 'Existing address',
        ]);
        $agent = User::create([
            'name' => 'Agent',
            'email' => 'profile-hidden-fields-agent@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $agent->auth_brands()->attach($brand->id);
        $chat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $agent->id,
            'status' => 'open',
        ]);

        $this->actingAs($agent)
            ->patchJson(route('admin.chat.profile.update', $chat->id, false), [
                'name' => 'Updated visitor',
                'email' => $visitorUser->email,
                'phone' => '5551234',
                'notes' => 'Updated notes',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $visitorUser->id,
            'name' => 'Updated visitor',
            'address' => 'Existing address',
        ]);
        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'notes' => 'Updated notes',
            'tags' => json_encode(['existing-tag']),
        ]);
    }

    public function test_returning_visitor_gets_a_new_chat_after_a_week(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'success'])]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'returning-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $brand = Brand::create([
            'name' => 'Test brand',
            'domain' => 'example.test',
            'status' => 1,
        ]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'returning-visitor',
        ]);
        $previousChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'open',
            'last_visitor_activity_at' => now()->subWeek(),
            'created_at' => now()->subWeek(),
            'updated_at' => now()->subWeek(),
        ]);
        $olderOpenChat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'status' => 'open',
            'last_visitor_activity_at' => now()->subWeeks(2),
            'created_at' => now()->subWeeks(2),
            'updated_at' => now()->subWeeks(2),
        ]);
        $visitorUser = User::create([
            'name' => 'Returning visitor',
            'email' => 'returning@example.com',
            'password' => 'password',
            'role' => 3,
            'visitor_id' => $visitor->id,
        ]);
        $this->createMessageAt([
            'chat_id' => $previousChat->id,
            'sender' => $visitorUser->id,
            'message' => 'Last week conversation',
        ], now()->subWeek());
        $this->createMessageAt([
            'chat_id' => $olderOpenChat->id,
            'sender' => $visitorUser->id,
            'message' => 'Older conversation',
        ], now()->subWeeks(2));

        $response = $this->postJson('/api/visitor-init', [
            'domain' => 'example.test',
            'session_id' => 'returning-visitor',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotSame($previousChat->id, $response->json('chat_id'));
        $this->assertDatabaseHas('chats', [
            'id' => $previousChat->id,
            'status' => 'closed',
            'closed_reason' => 'inactive',
        ]);
        $this->assertDatabaseHas('chats', [
            'id' => $olderOpenChat->id,
            'status' => 'closed',
            'closed_reason' => 'inactive',
        ]);
        $this->assertDatabaseHas('chats', [
            'id' => $response->json('chat_id'),
            'visitor_id' => $visitor->id,
            'status' => 'open',
        ]);

        $profileResponse = $this->actingAs($admin)
            ->getJson(route('admin.chat.profile', $response->json('chat_id'), false));

        $profileResponse->assertOk()
            ->assertJsonPath('current_chat.id', $response->json('chat_id'))
            ->assertJsonFragment(['preview' => 'Last week conversation'])
            ->assertJsonFragment(['preview' => 'Older conversation']);
    }

}
