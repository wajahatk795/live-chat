<?php

namespace App\Services\Ai;

use App\Jobs\ProcessAIResponseJob;
use App\Models\Brand;
use App\Models\Chat;
use App\Models\ChatSetting;
use App\Services\AgentPresenceService;

class OfflineAgentAiHandoff
{
    public function __construct(private AgentPresenceService $presence)
    {
    }

    public function hasOnlineAgent(Chat $chat): bool
    {
        $brand = Brand::find($chat->brand_id);

        return $brand && $this->presence->hasOnlineAgent($brand);
    }

    public function triggerIfNoOnlineAgents(Chat $chat): bool
    {
        if ($chat->status !== 'open' || !$chat->ai_pending || !$chat->ai_pending_since || $chat->ai_processing) {
            return false;
        }

        $brand = Brand::find($chat->brand_id);
        $settings = $brand
            ? ($brand->chatSetting ?: ChatSetting::where('brand_id', $brand->id)->first())
            : null;

        if (!$brand || !$settings || !$settings->ai_enabled || $this->presence->hasOnlineAgent($brand)) {
            return false;
        }

        ProcessAIResponseJob::dispatchSync(
            $chat,
            $chat->ai_pending_since->toDateTimeString(),
            true
        );

        return true;
    }
}
