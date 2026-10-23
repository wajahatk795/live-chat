<?php

namespace App\Models;

use App\Support\CountryCode;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    public const ONLINE_TIMEOUT_SECONDS = 10;

    protected $fillable = [
        'brand_id',
        'company_id',
        'session_id',
        'last_url',
        'source',
        'keyword',
        'ip_address',
        'country',
        'country_code',
        'city',
        'device',
        'browser',
        'os',
        'notes',
        'tags',
        'last_seen_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function getCountryCodeAttribute($value): ?string
    {
        return CountryCode::resolve($value, $this->attributes['country'] ?? null);
    }

    protected static function boot()
    {
        parent::boot();

        // Delete associated users when visitor is deleted
        static::deleting(function ($visitor) {
            $visitor->users()->delete();
        });
    }

    public function user()
    {
        return $this->hasOne(User::class, 'visitor_id', 'id');
    }

    /**
     * Relationship with Users (PLURAL - agar multiple users ho)
     */
    public function users()
    {
        return $this->hasMany(User::class, 'visitor_id', 'id');
    }

    /**
     * Relationship with Website
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
