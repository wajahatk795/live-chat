<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const ONLINE_TIMEOUT_SECONDS = 15;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'about',
        'address',
        'image',
        'visitor_id',
        'is_online',
        'last_seen',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    public function get_chat()
    {
        return $this->hasOne(Chat::class, 'visitor_id', 'visitor_id');
    }

    public function assignedChats()
    {
        return $this->hasMany(Chat::class, 'agent_id', 'id');
    }

    public function agentPresences()
    {
        return $this->hasMany(AgentPresence::class);
    }

    public function isRole()
    {
        $role = $this->role;
        if ($role == 1) {
            return 'Admin';
        } elseif ($role == 2) {
            return 'Agent';
        } else {
            return 'Visitor';
        }
    }

    public function brands()
    {
        return $this->hasMany(Brand::class, 'user_id');
    }

    public function brandss()
    {
        return $this->belongsToMany(Brand::class, 'brand_users', 'user_id', 'brand_id');
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'id');
    }

    public function auth_brands()
    {
        return $this->belongsToMany(Brand::class, 'brand_users', 'user_id', 'brand_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender');
    }
}
