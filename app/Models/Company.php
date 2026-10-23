<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'email', 'domain', 'subscription_plan', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function visitors()
    {
        return $this->hasMany(Visitor::class);
    }
}
