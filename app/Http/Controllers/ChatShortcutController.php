<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatShortcut;
use App\Models\Brand;
use Illuminate\Http\Request;

class ChatShortcutController extends Controller
{
    public function manage()
    {
        $this->authorizeAgent();
        $brandIds = auth()->user()->auth_brands()->pluck('brand.id');
        $shortcuts = ChatShortcut::query()
            ->where('user_id', auth()->id())
            ->where(function ($query) use ($brandIds) {
                $query->where('scope', 'personal')
                    ->orWhere(function ($query) use ($brandIds) {
                        $query->where('scope', 'team')->whereIn('brand_id', $brandIds);
                    });
            })
            ->with('brand')
            ->orderBy('scope')
            ->orderBy('shortcut')
            ->get();
        $brands = auth()->user()->auth_brands()->orderBy('name')->get(['brand.id', 'brand.name']);

        return view('admin.chat-shortcuts', compact('shortcuts', 'brands'));
    }

    public function manageStore(Request $request)
    {
        $this->authorizeAgent();
        $validated = $this->validateShortcut($request);
        $brandId = $this->brandIdForScope($validated['scope'], $validated['brand_id'] ?? null);
        $this->ensureUniqueShortcut($validated['shortcut'], $validated['scope'], $brandId);

        ChatShortcut::create([
            'user_id' => auth()->id(),
            'brand_id' => $brandId,
            'shortcut' => strtolower($validated['shortcut']),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'scope' => $validated['scope'],
        ]);

        return response()->json(['message' => 'Shortcut saved.']);
    }

    public function manageUpdate(Request $request, int $shortcutId)
    {
        $this->authorizeAgent();
        $shortcut = $this->ownedShortcut($shortcutId);
        $validated = $this->validateShortcut($request);
        $brandId = $this->brandIdForScope($validated['scope'], $validated['brand_id'] ?? null);
        $this->ensureUniqueShortcut($validated['shortcut'], $validated['scope'], $brandId, $shortcut->id);

        $shortcut->update([
            'brand_id' => $brandId,
            'shortcut' => strtolower($validated['shortcut']),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'scope' => $validated['scope'],
        ]);

        return response()->json(['message' => 'Shortcut updated.']);
    }

    public function index(Request $request, int $chatId)
    {
        $chat = $this->authorizedChat($chatId);
        $search = trim((string) $request->query('q', ''));

        $shortcuts = ChatShortcut::query()
            ->where(function ($query) use ($chat) {
                $query->where(function ($query) use ($chat) {
                    $query->where('scope', 'team')->where('brand_id', $chat->brand_id);
                })->orWhere(function ($query) {
                    $query->where('scope', 'personal')->where('user_id', auth()->id());
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('shortcut', 'like', $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%');
                });
            })
            ->orderByRaw("CASE WHEN scope = 'personal' THEN 0 ELSE 1 END")
            ->orderBy('shortcut')
            ->get()
            ->map(fn (ChatShortcut $shortcut) => [
                'id' => $shortcut->id,
                'shortcut' => $shortcut->shortcut,
                'title' => $shortcut->title,
                'content' => $shortcut->content,
                'scope' => $shortcut->scope,
                'can_delete' => (int) $shortcut->user_id === (int) auth()->id(),
                'can_edit' => (int) $shortcut->user_id === (int) auth()->id(),
            ]);

        return response()->json(['shortcuts' => $shortcuts]);
    }

    public function store(Request $request, int $chatId)
    {
        $chat = $this->authorizedChat($chatId);
        $validated = $request->validate([
            'shortcut' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_-]*$/i'],
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:5000',
            'scope' => 'required|in:personal,team',
        ]);
        $shortcutKey = strtolower($validated['shortcut']);

        $duplicate = ChatShortcut::query()
            ->where('shortcut', $shortcutKey)
            ->where('scope', $validated['scope'])
            ->when($validated['scope'] === 'team', fn ($query) => $query->where('brand_id', $chat->brand_id))
            ->when($validated['scope'] === 'personal', fn ($query) => $query->where('user_id', auth()->id()))
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'A shortcut with this name already exists in that scope.',
                'errors' => ['shortcut' => ['Choose a different shortcut name.']],
            ], 422);
        }

        $shortcut = ChatShortcut::create([
            'user_id' => auth()->id(),
            'brand_id' => $validated['scope'] === 'team' ? $chat->brand_id : null,
            'shortcut' => $shortcutKey,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'scope' => $validated['scope'],
        ]);

        return response()->json([
            'message' => 'Shortcut saved.',
            'shortcut' => [
                'id' => $shortcut->id,
                'shortcut' => $shortcut->shortcut,
                'title' => $shortcut->title,
                'content' => $shortcut->content,
                'scope' => $shortcut->scope,
                'can_delete' => true,
                'can_edit' => true,
            ],
        ], 201);
    }

    public function update(Request $request, int $chatId, int $shortcutId)
    {
        $chat = $this->authorizedChat($chatId);
        $shortcut = ChatShortcut::findOrFail($shortcutId);
        abort_unless((int) $shortcut->user_id === (int) auth()->id(), 403, 'Only the shortcut creator can edit it.');
        abort_unless(
            $shortcut->scope !== 'team' || (int) $shortcut->brand_id === (int) $chat->brand_id,
            403
        );

        $validated = $request->validate([
            'shortcut' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_-]*$/i'],
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:5000',
            'scope' => 'required|in:personal,team',
        ]);
        $shortcutKey = strtolower($validated['shortcut']);
        $brandId = $validated['scope'] === 'team' ? $chat->brand_id : null;

        $duplicate = ChatShortcut::query()
            ->where('id', '!=', $shortcut->id)
            ->where('shortcut', $shortcutKey)
            ->where('scope', $validated['scope'])
            ->when($validated['scope'] === 'team', fn ($query) => $query->where('brand_id', $brandId))
            ->when($validated['scope'] === 'personal', fn ($query) => $query->where('user_id', auth()->id()))
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'A shortcut with this name already exists in that scope.',
                'errors' => ['shortcut' => ['Choose a different shortcut name.']],
            ], 422);
        }

        $shortcut->update([
            'brand_id' => $brandId,
            'shortcut' => $shortcutKey,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'scope' => $validated['scope'],
        ]);

        return response()->json([
            'message' => 'Shortcut updated.',
            'shortcut' => [
                'id' => $shortcut->id,
                'shortcut' => $shortcut->shortcut,
                'title' => $shortcut->title,
                'content' => $shortcut->content,
                'scope' => $shortcut->scope,
                'can_delete' => true,
                'can_edit' => true,
            ],
        ]);
    }

    public function destroy(int $shortcutId)
    {
        $shortcut = $this->ownedShortcut($shortcutId);
        if ($shortcut->scope === 'team') {
            abort_unless(
                auth()->user()->auth_brands()->where('brand.id', $shortcut->brand_id)->exists(),
                403
            );
        }

        $shortcut->delete();

        return response()->json(['message' => 'Shortcut deleted.']);
    }

    private function authorizedChat(int $chatId): Chat
    {
        $this->authorizeAgent();

        $chat = Chat::findOrFail($chatId);
        abort_unless(
            auth()->user()->auth_brands()->where('brand.id', $chat->brand_id)->exists(),
            403
        );

        return $chat;
    }

    private function authorizeAgent(): void
    {
        abort_unless((int) auth()->user()->role === 2, 403);
    }

    private function ownedShortcut(int $shortcutId): ChatShortcut
    {
        $this->authorizeAgent();
        $shortcut = ChatShortcut::findOrFail($shortcutId);
        abort_unless((int) $shortcut->user_id === (int) auth()->id(), 403, 'Only the shortcut creator can manage it.');

        return $shortcut;
    }

    private function validateShortcut(Request $request): array
    {
        return $request->validate([
            'shortcut' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_-]*$/i'],
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:5000',
            'scope' => 'required|in:personal,team',
            'brand_id' => 'nullable|integer',
        ]);
    }

    private function brandIdForScope(string $scope, mixed $brandId): ?int
    {
        if ($scope === 'personal') {
            return null;
        }

        abort_if(!$brandId, 422, 'Select a team brand for this shortcut.');
        abort_unless(
            auth()->user()->auth_brands()->where('brand.id', $brandId)->exists(),
            403,
            'You can only create team shortcuts for your assigned brands.'
        );

        return (int) $brandId;
    }

    private function ensureUniqueShortcut(string $shortcut, string $scope, ?int $brandId, ?int $ignoreId = null): void
    {
        $duplicate = ChatShortcut::query()
            ->where('id', '!=', $ignoreId ?? 0)
            ->where('shortcut', strtolower($shortcut))
            ->where('scope', $scope)
            ->when($scope === 'team', fn ($query) => $query->where('brand_id', $brandId))
            ->when($scope === 'personal', fn ($query) => $query->where('user_id', auth()->id()))
            ->exists();

        if ($duplicate) {
            abort(response()->json([
                'message' => 'A shortcut with this name already exists in that scope.',
                'errors' => ['shortcut' => ['Choose a different shortcut name.']],
            ], 422));
        }
    }
}
