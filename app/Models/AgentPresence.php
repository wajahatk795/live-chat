<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentPresence extends Model
{
    protected $fillable = ['user_id', 'tab_id', 'last_seen_at'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];
}
