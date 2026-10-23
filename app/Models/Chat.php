<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Chat extends Model
{
    protected $fillable = ['visitor_id', 'brand_id', 'agent_id', 'status', 'closed_reason', 'last_visitor_activity_at', 'last_agent_activity_at', 'warned_at',
        'system_message_at', 'closed_at', 'agent_warned', 'system_notified', 'is_handled_by_ai',
        'ai_pending', 'ai_pending_since', 'ai_greeted', 'ai_processing'];

    protected $casts = [
        'ai_pending_since' => 'datetime',
        'last_visitor_activity_at' => 'datetime',
        'last_agent_activity_at' => 'datetime',
        'warned_at' => 'datetime',
        'system_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->hasOne(User::class, 'id', 'agent_id');
    }

    public function visitor()
    {
        return $this->hasOne(User::class, 'visitor_id', 'visitor_id');
    }

    public function get_visitor()
    {
        return $this->belongsTo(User::class, 'visitor_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function get_brand()
    {
        return $this->hasOne(Brand::class, 'id', 'brand_id');
    }

    public function main_visitor()
    {
        return $this->hasOne(Visitor::class, 'id', 'visitor_id');
    }

    public function isPastConversation(?Carbon $cutoff = null): bool
    {
        $cutoff ??= now()->subWeek();
        $lastMessageAt = $this->getAttribute('messages_max_created_at')
            ?? $this->messages()->max('created_at');
        $activityTimestamps = collect([
            $this->last_visitor_activity_at,
            $this->last_agent_activity_at,
            $lastMessageAt,
        ])->filter()->map(fn ($timestamp) => Carbon::parse($timestamp));

        if ($activityTimestamps->isEmpty()) {
            $lastActivity = $this->closed_at ?? $this->updated_at;
            return !$lastActivity || $lastActivity->lte($cutoff);
        }

        return $activityTimestamps->max()->lte($cutoff);
    }
}
