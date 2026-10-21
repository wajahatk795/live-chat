<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatShortcut extends Model
{
    protected $fillable = [
        'user_id',
        'brand_id',
        'shortcut',
        'title',
        'content',
        'scope',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
