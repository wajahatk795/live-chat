<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brand';
    protected $fillable = [
        'name',
        'company_id',
        'user_id',
        'url',
        'logo',
        'email',
        'phone',
        'address',
        'status',
        'logo',
        'website',
        'domain',
        'verify_token',
        'is_verified',
        'verified_at',
        'allowed_ips'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'brand_users', 'brand_id', 'user_id');
    }

    public function chatSetting()
    {
        return $this->hasOne(ChatSetting::class, 'brand_id', 'id');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, 'brand_id', 'id');
    }

    public function formSubmissions()
    {
        return $this->hasMany(FormSubmission::class, 'brand_id', 'id');
    }
}
