<?php

namespace App\Services;

use App\Models\AgentPresence;
use App\Models\Brand;
use App\Models\User;

class AgentPresenceService
{
    public function update(User $user, string $tabId, bool $online): void
    {
        if ($online) {
            AgentPresence::updateOrCreate(
                ['user_id' => $user->id, 'tab_id' => $tabId],
                ['last_seen_at' => now()]
            );
        } else {
            AgentPresence::where('user_id', $user->id)
                ->where('tab_id', $tabId)
                ->delete();
        }

        $hasActiveTab = $this->hasActivePresence($user);
        $user->update([
            'is_online' => $hasActiveTab,
            'last_seen' => now(),
        ]);
    }

    public function hasOnlineAgent(Brand $brand): bool
    {
        return $brand->users()
            ->where('users.role', 2)
            ->whereHas('agentPresences', function ($query) {
                $query->where('last_seen_at', '>=', now()->subSeconds(User::ONLINE_TIMEOUT_SECONDS));
            })
            ->exists();
    }

    public function clear(User $user): void
    {
        AgentPresence::where('user_id', $user->id)->delete();
        $user->update(['is_online' => false]);
    }

    private function hasActivePresence(User $user): bool
    {
        return AgentPresence::where('user_id', $user->id)
            ->where('last_seen_at', '>=', now()->subSeconds(User::ONLINE_TIMEOUT_SECONDS))
            ->exists();
    }
}
