<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Chat;
use App\Models\ChatShortcut;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ChatShortcutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
    }

    public function test_agents_can_create_personal_and_team_shortcuts_with_correct_visibility(): void
    {
        $brand = Brand::create(['name' => 'Shortcut team', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'shortcut-visitor',
        ]);
        $agent = User::create([
            'name' => 'Agent One',
            'email' => 'shortcut-agent-one@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $teammate = User::create([
            'name' => 'Agent Two',
            'email' => 'shortcut-agent-two@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $otherAgent = User::create([
            'name' => 'Other Agent',
            'email' => 'shortcut-agent-other@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $agent->auth_brands()->attach($brand->id);
        $teammate->auth_brands()->attach($brand->id);
        $chat = Chat::create([
            'visitor_id' => $visitor->id,
            'brand_id' => $brand->id,
            'agent_id' => $agent->id,
            'status' => 'open',
        ]);

        $createTeamResponse = $this->actingAs($agent)
            ->postJson(route('admin.chat.shortcuts.store', $chat->id, false), [
                'shortcut' => 'greeting',
                'title' => 'Greeting',
                'content' => 'Hello! How can I help?',
                'scope' => 'team',
            ]);

        $createTeamResponse
            ->assertCreated()
            ->assertJsonPath('shortcut.shortcut', 'greeting')
            ->assertJsonPath('shortcut.scope', 'team');
        $teamShortcutId = $createTeamResponse->json('shortcut.id');

        $personalResponse = $this->postJson(route('admin.chat.shortcuts.store', $chat->id, false), [
            'shortcut' => 'signature',
            'title' => 'My signature',
            'content' => 'Regards, Agent One',
            'scope' => 'personal',
        ])->assertCreated();
        $personalShortcutId = $personalResponse->json('shortcut.id');

        $this->putJson(route('admin.chat.shortcuts.update', [$chat->id, $personalShortcutId], false), [
            'shortcut' => 'signature',
            'title' => 'Updated signature',
            'content' => 'Best regards, Agent One',
            'scope' => 'personal',
        ])
            ->assertOk()
            ->assertJsonPath('shortcut.title', 'Updated signature')
            ->assertJsonPath('shortcut.content', 'Best regards, Agent One');
        $this->assertDatabaseHas('chat_shortcuts', [
            'id' => $personalShortcutId,
            'title' => 'Updated signature',
            'content' => 'Best regards, Agent One',
        ]);

        $this->getJson(route('admin.chat.shortcuts', $chat->id, false) . '?q=gree')
            ->assertOk()
            ->assertJsonCount(1, 'shortcuts')
            ->assertJsonPath('shortcuts.0.content', 'Hello! How can I help?');

        $this->getJson(route('admin.chat.shortcuts', $chat->id, false))
            ->assertOk()
            ->assertJsonCount(2, 'shortcuts');

        $this->actingAs($teammate)
            ->getJson(route('admin.chat.shortcuts', $chat->id, false))
            ->assertOk()
            ->assertJsonCount(1, 'shortcuts')
            ->assertJsonPath('shortcuts.0.shortcut', 'greeting')
            ->assertJsonPath('shortcuts.0.can_edit', false);
        $this->putJson(route('admin.chat.shortcuts.update', [$chat->id, $teamShortcutId], false), [
            'shortcut' => 'greeting',
            'title' => 'Updated greeting',
            'content' => 'Hi!',
            'scope' => 'team',
        ])->assertForbidden();

        $this->actingAs($otherAgent)
            ->getJson(route('admin.chat.shortcuts', $chat->id, false))
            ->assertForbidden();

        $this->actingAs($agent)
            ->deleteJson(route('admin.chat.shortcuts.destroy', $teamShortcutId, false))
            ->assertOk();
        $this->assertDatabaseMissing('chat_shortcuts', ['id' => $teamShortcutId]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'shortcut-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $this->actingAs($admin)
            ->getJson(route('admin.chat.shortcuts', $chat->id, false))
            ->assertForbidden();
    }

    public function test_duplicate_shortcut_names_are_rejected_per_scope(): void
    {
        $brand = Brand::create(['name' => 'Shortcut team', 'status' => 1]);
        $visitor = Visitor::create([
            'brand_id' => $brand->id,
            'session_id' => 'shortcut-duplicate-visitor',
        ]);
        $agent = User::create([
            'name' => 'Agent',
            'email' => 'shortcut-duplicate-agent@example.com',
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

        ChatShortcut::create([
            'user_id' => $agent->id,
            'brand_id' => $brand->id,
            'shortcut' => 'greeting',
            'title' => 'Greeting',
            'content' => 'Hello',
            'scope' => 'team',
        ]);

        $this->actingAs($agent)
            ->postJson(route('admin.chat.shortcuts.store', $chat->id, false), [
                'shortcut' => 'GREETING',
                'title' => 'Another greeting',
                'content' => 'Hi there',
                'scope' => 'team',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('shortcut');
    }

    public function test_agents_manage_shortcuts_from_the_sidebar_page(): void
    {
        $brand = Brand::create(['name' => 'Shortcut brand', 'status' => 1]);
        $agent = User::create([
            'name' => 'Shortcut manager',
            'email' => 'shortcut-manager@example.com',
            'password' => 'password',
            'role' => 2,
        ]);
        $agent->auth_brands()->attach($brand->id);

        $this->actingAs($agent)
            ->get(route('admin.chat-shortcuts.index', [], false))
            ->assertOk()
            ->assertSee('Create shortcut')
            ->assertSee('Shortcut brand');

        $this->postJson(route('admin.chat-shortcuts.store', [], false), [
            'shortcut' => 'welcome',
            'title' => 'Welcome',
            'content' => 'Welcome to our support chat!',
            'scope' => 'team',
            'brand_id' => $brand->id,
        ])->assertOk()
            ->assertJsonPath('message', 'Shortcut saved.');
        $shortcut = ChatShortcut::where('shortcut', 'welcome')->firstOrFail();

        $this->putJson(route('admin.chat-shortcuts.update', $shortcut->id, false), [
            'shortcut' => 'welcome_back',
            'title' => 'Welcome back',
            'content' => 'How can I help you today?',
            'scope' => 'personal',
        ])->assertOk()
            ->assertJsonPath('message', 'Shortcut updated.');
        $this->assertDatabaseHas('chat_shortcuts', [
            'id' => $shortcut->id,
            'shortcut' => 'welcome_back',
            'scope' => 'personal',
            'brand_id' => null,
        ]);

        $this->deleteJson(route('admin.chat.shortcuts.destroy', $shortcut->id, false))
            ->assertOk();
        $this->assertDatabaseMissing('chat_shortcuts', ['id' => $shortcut->id]);

        $admin = User::create([
            'name' => 'Read-only admin',
            'email' => 'shortcut-page-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $this->actingAs($admin)
            ->get(route('admin.chat-shortcuts.index', [], false))
            ->assertForbidden();
    }
}
