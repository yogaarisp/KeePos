<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
        'is_active',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function profile()
    {
        return $this->hasOne(TenantProfile::class);
    }

    /**
     * Get or create profile for this tenant
     */
    public function getOrCreateProfile()
    {
        if (!$this->profile) {
            return TenantProfile::create([
                'tenant_id' => $this->id,
                'shop_name' => $this->name,
                'shop_email' => $this->email,
                'shop_phone' => $this->phone,
                'shop_address' => $this->address,
                'shop_tagline' => 'Smart POS System',
            ]);
        }
        
        return $this->profile;
    }
}
